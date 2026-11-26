<?php

use App\Models\Fakultas;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Semua helper di file ini diawali f5mon dan didefinisikan sendiri
// (jangan memakai helper dari file test lain — urutan load tidak dijamin).

function f5monBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f5monBuatProposalDisetujui(User $user, array $attrs = []): Proposal
{
    // fakultas_prodi punya FK ke tabel fakultas — pakai baris yang ada atau buat satu.
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal disetujui',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function f5monBuatTemuan(Proposal $proposal, array $attrs = []): MonevFinding
{
    return MonevFinding::create(array_merge([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Laporan kemajuan terlambat.',
        'rekomendasi' => 'Segera unggah laporan kemajuan.',
        'status_temuan' => 'Open',
    ], $attrs));
}

function f5monPayloadRtl(): array
{
    return [
        'file_rtl' => UploadedFile::fake()->create('rtl.pdf', 100, 'application/pdf'),
        'bukti_perbaikan' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ];
}

function f5monTemuanInReview(Proposal $proposal, User $pengaju): MonevFinding
{
    $finding = f5monBuatTemuan($proposal);

    test()->actingAs($pengaju)->post(route('monev.kirimRtl', $finding), f5monPayloadRtl());

    return $finding->fresh();
}

test('lppm membuat temuan: status awal Open dan pengaju menerima notifikasi', function () {
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);

    $response = $this->actingAs($lppm)->post(route('monev.store', $proposal), [
        'uraian_temuan' => 'Laporan kemajuan terlambat.',
        'rekomendasi' => 'Segera unggah laporan kemajuan.',
    ]);

    $response->assertRedirect(route('monev.show', $proposal));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('monev_findings', [
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Laporan kemajuan terlambat.',
        'rekomendasi' => 'Segera unggah laporan kemajuan.',
        'status_temuan' => 'Open',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $pengaju->id,
        'proposal_id' => $proposal->id,
        'title' => 'Temuan Monev Baru',
    ]);
});

test('pengaju melihat temuannya sendiri dan tidak melihat temuan pengaju lain', function () {
    $pengaju = f5monBuatUser('pengaju');
    $pengajuLain = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $proposalLain = f5monBuatProposalDisetujui($pengajuLain, ['judul' => 'Proposal lain']);
    f5monBuatTemuan($proposal, ['uraian_temuan' => 'Temuan milik saya.']);
    f5monBuatTemuan($proposalLain, ['uraian_temuan' => 'Temuan milik orang lain.']);

    $response = $this->actingAs($pengaju)->get(route('monev.index'));

    $response->assertOk();
    $response->assertSee('Temuan milik saya.');
    $response->assertDontSee('Temuan milik orang lain.');

    // Halaman detail proposal sendiri juga bisa dibuka.
    $this->actingAs($pengaju)->get(route('monev.show', $proposal))->assertOk();
});

test('pengaju membuat temuan ditolak 403', function () {
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);

    $this->actingAs($pengaju)
        ->post(route('monev.store', $proposal), [
            'uraian_temuan' => 'Mencoba membuat temuan.',
            'rekomendasi' => 'Rekomendasi.',
        ])
        ->assertForbidden();

    $this->assertDatabaseCount('monev_findings', 0);
});

test('reviewer dan verifikator tidak boleh melihat daftar maupun detail temuan', function () {
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    f5monBuatTemuan($proposal);

    $this->actingAs(f5monBuatUser('reviewer'))->get(route('monev.index'))->assertForbidden();
    $this->actingAs(f5monBuatUser('reviewer'))->get(route('monev.show', $proposal))->assertForbidden();
    $this->actingAs(f5monBuatUser('verifikator'))->get(route('monev.index'))->assertForbidden();
});

test('pengaju mengirim RTL: status jadi In_Review, file tersimpan, lppm menerima notifikasi', function () {
    Storage::fake('public');
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monBuatTemuan($proposal);

    $response = $this->actingAs($pengaju)
        ->post(route('monev.kirimRtl', $finding), f5monPayloadRtl());

    $response->assertRedirect(route('monev.show', $proposal));
    $response->assertSessionHas('success');

    $finding = $finding->fresh();
    expect($finding->status_temuan)->toBe('In_Review');
    expect($finding->file_rtl)->not->toBeNull();
    expect($finding->bukti_perbaikan)->not->toBeNull();
    Storage::disk('public')->assertExists($finding->file_rtl);
    Storage::disk('public')->assertExists($finding->bukti_perbaikan);

    $this->assertDatabaseHas('notifications', [
        'user_id' => $lppm->id,
        'proposal_id' => $proposal->id,
        'title' => 'RTL Dikirim',
    ]);
});

test('lppm menutup temuan In_Review: status jadi Closed dan pengaju menerima notifikasi', function () {
    Storage::fake('public');
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monTemuanInReview($proposal, $pengaju);

    expect($finding->status_temuan)->toBe('In_Review');

    $response = $this->actingAs($lppm)
        ->post(route('monev.verifikasi', $finding), ['keputusan' => 'tutup']);

    $response->assertRedirect(route('monev.show', $proposal));
    $response->assertSessionHas('success');
    expect($finding->fresh()->status_temuan)->toBe('Closed');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $pengaju->id,
        'proposal_id' => $proposal->id,
        'title' => 'Temuan Monev Ditutup',
    ]);
});

test('lppm mengembalikan temuan In_Review: status kembali Open dan pengaju menerima notifikasi', function () {
    Storage::fake('public');
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monTemuanInReview($proposal, $pengaju);

    $response = $this->actingAs($lppm)
        ->post(route('monev.verifikasi', $finding), ['keputusan' => 'kembalikan']);

    $response->assertRedirect(route('monev.show', $proposal));
    $response->assertSessionHas('success');
    expect($finding->fresh()->status_temuan)->toBe('Open');

    $this->assertDatabaseHas('notifications', [
        'user_id' => $pengaju->id,
        'proposal_id' => $proposal->id,
        'title' => 'RTL Dikembalikan',
    ]);
});

test('kirim RTL oleh bukan pemilik proposal ditolak 403', function () {
    Storage::fake('public');
    $pengaju = f5monBuatUser('pengaju');
    $orangLain = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monBuatTemuan($proposal);

    $this->actingAs($orangLain)
        ->post(route('monev.kirimRtl', $finding), f5monPayloadRtl())
        ->assertForbidden();

    expect($finding->fresh()->status_temuan)->toBe('Open');
});

test('kirim RTL pada temuan Closed ditolak dan status tetap Closed', function () {
    Storage::fake('public');
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monTemuanInReview($proposal, $pengaju);

    $this->actingAs($lppm)->post(route('monev.verifikasi', $finding), ['keputusan' => 'tutup']);
    expect($finding->fresh()->status_temuan)->toBe('Closed');

    $response = $this->actingAs($pengaju)
        ->post(route('monev.kirimRtl', $finding), f5monPayloadRtl());

    $response->assertSessionHas('error');
    expect($finding->fresh()->status_temuan)->toBe('Closed');
});

test('lppm menutup temuan yang masih Open ditolak dan status tetap Open', function () {
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monBuatTemuan($proposal);

    $response = $this->actingAs($lppm)
        ->post(route('monev.verifikasi', $finding), ['keputusan' => 'tutup']);

    $response->assertSessionHas('error');
    expect($finding->fresh()->status_temuan)->toBe('Open');
});

test('validasi gagal: temuan tanpa uraian, RTL tanpa file atau bukan PDF, verifikasi tanpa keputusan', function () {
    Storage::fake('public');
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);

    // Temuan tanpa uraian_temuan dan rekomendasi.
    $this->actingAs($lppm)
        ->post(route('monev.store', $proposal), ['uraian_temuan' => '', 'rekomendasi' => ''])
        ->assertSessionHasErrors(['uraian_temuan', 'rekomendasi']);
    $this->assertDatabaseCount('monev_findings', 0);

    $finding = f5monBuatTemuan($proposal);

    // RTL tanpa file sama sekali.
    $this->actingAs($pengaju)
        ->post(route('monev.kirimRtl', $finding), [])
        ->assertSessionHasErrors(['file_rtl', 'bukti_perbaikan']);

    // RTL dengan file bukan PDF.
    $this->actingAs($pengaju)
        ->post(route('monev.kirimRtl', $finding), [
            'file_rtl' => UploadedFile::fake()->create('rtl.txt', 100, 'text/plain'),
            'bukti_perbaikan' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['file_rtl']);
    expect($finding->fresh()->status_temuan)->toBe('Open');

    // Verifikasi tanpa keputusan.
    $this->actingAs($lppm)
        ->post(route('monev.verifikasi', $finding), [])
        ->assertSessionHasErrors(['keputusan']);
});

test('temuan hanya untuk proposal Disetujui: buat dan lihat pada proposal Dikirim ditolak', function () {
    $lppm = f5monBuatUser('lppm');
    $pengaju = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju, [
        'status' => 'Dikirim',
        'status_pendanaan' => null,
    ]);

    $response = $this->actingAs($lppm)->post(route('monev.store', $proposal), [
        'uraian_temuan' => 'Temuan.',
        'rekomendasi' => 'Rekomendasi.',
    ]);
    $response->assertSessionHas('error');
    $this->assertDatabaseCount('monev_findings', 0);

    $this->actingAs($lppm)->get(route('monev.show', $proposal))->assertSessionHas('error');
});

test('dekan hanya melihat temuan di fakultasnya, admin dan kepala lppm melihat semua', function () {
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas A']);
    $fakultasB = Fakultas::create(['nama_fakultas' => 'Fakultas B']);
    $pengajuA = f5monBuatUser('pengaju');
    $pengajuB = f5monBuatUser('pengaju');
    $proposalA = f5monBuatProposalDisetujui($pengajuA, ['fakultas_prodi' => $fakultasA->id, 'judul' => 'Proposal A']);
    $proposalB = f5monBuatProposalDisetujui($pengajuB, ['fakultas_prodi' => $fakultasB->id, 'judul' => 'Proposal B']);
    f5monBuatTemuan($proposalA, ['uraian_temuan' => 'Temuan fakultas A.']);
    f5monBuatTemuan($proposalB, ['uraian_temuan' => 'Temuan fakultas B.']);

    $dekanA = f5monBuatUser('dekan', ['fakultas_id' => $fakultasA->id]);

    $response = $this->actingAs($dekanA)->get(route('monev.index'));
    $response->assertOk();
    $response->assertSee('Temuan fakultas A.');
    $response->assertDontSee('Temuan fakultas B.');
    $this->actingAs($dekanA)->get(route('monev.show', $proposalB))->assertForbidden();

    $response = $this->actingAs(f5monBuatUser('admin'))->get(route('monev.index'));
    $response->assertOk();
    $response->assertSee('Temuan fakultas A.');
    $response->assertSee('Temuan fakultas B.');

    $response = $this->actingAs(f5monBuatUser('kepala_lppm'))->get(route('monev.index'));
    $response->assertOk();
    $response->assertSee('Temuan fakultas A.');
    $response->assertSee('Temuan fakultas B.');
});

test('pengaju pemilik bisa mengunduh RTL, pengaju lain ditolak 403', function () {
    Storage::fake('public');
    $pengaju = f5monBuatUser('pengaju');
    $orangLain = f5monBuatUser('pengaju');
    $proposal = f5monBuatProposalDisetujui($pengaju);
    $finding = f5monTemuanInReview($proposal, $pengaju);

    $this->actingAs($pengaju)->get(route('monev.download', [$finding, 'rtl']))->assertOk();
    $this->actingAs($pengaju)->get(route('monev.download', [$finding, 'bukti']))->assertOk();
    $this->actingAs($orangLain)->get(route('monev.download', [$finding, 'rtl']))->assertForbidden();
});
