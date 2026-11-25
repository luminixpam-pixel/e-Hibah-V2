<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function buatPengaju(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'pengaju'], $attrs));
}

function buatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function buatPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function payloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Pengembangan Sistem E-Hibah',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

function buatProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
{
    // fakultas_prodi punya FK ke tabel fakultas — pakai baris yang ada atau buat satu.
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

test('draft proposal bisa disimpan tanpa file, skema, dan RAB', function () {
    $user = buatPengaju();

    $response = $this->actingAs($user)->post(route('proposal.store'), [
        'aksi' => 'draft',
        'judul' => 'Draft proposal saya',
    ]);

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('proposals', [
        'judul' => 'Draft proposal saya',
        'status' => 'Draft',
        'user_id' => $user->id,
        'no_pengajuan' => null,
    ]);
});

test('submit proposal berhasil: nomor pengajuan dibuat, biaya sama dengan total RAB', function () {
    Storage::fake('public');
    $user = buatPengaju(['name' => 'Dr. Andi']);
    $skema = buatSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), payloadSubmit($skema, $fakultas));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');

    $proposal = Proposal::where('judul', 'Pengembangan Sistem E-Hibah')->firstOrFail();
    expect($proposal->status)->toBe('Dikirim')
        ->and($proposal->no_pengajuan)->toBe('EH-2026-0001')
        ->and((float) $proposal->biaya)->toBe(6000000.0)
        ->and($proposal->rabItems)->toHaveCount(2);
});

test('submit proposal dengan anggota terdaftar membuat undangan dan notifikasi', function () {
    Storage::fake('public');
    $user = buatPengaju(['name' => 'Dr. Andi']);
    $anggota = buatPengaju(['name' => 'Budi Santoso']);
    $skema = buatSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    $this->actingAs($user)->post(route('proposal.store'), payloadSubmit($skema, $fakultas, [
        'anggota' => ['Budi Santoso'],
    ]));

    $proposal = Proposal::where('judul', 'Pengembangan Sistem E-Hibah')->firstOrFail();

    $this->assertDatabaseHas('proposal_members', [
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'status_konfirmasi' => 'Menunggu',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $anggota->id,
        'proposal_id' => $proposal->id,
        'title' => 'Undangan Anggota Tim Proposal',
    ]);
});

test('submit ditolak bila total RAB melebihi plafon skema', function () {
    Storage::fake('public');
    $user = buatPengaju();
    $skema = buatSkema(['plafon' => 5000000]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), payloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);
});

test('submit ditolak bila pengaju masuk blacklist', function () {
    Storage::fake('public');
    $user = buatPengaju(['is_blacklisted' => true]);
    $skema = buatSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), payloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);
});

test('submit ditolak bila kuota skema sudah habis, draft tidak menghabiskan kuota', function () {
    Storage::fake('public');
    $user = buatPengaju();
    $skema = buatSkema(['kuota' => 2]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    buatProposalDikirim($user, $skema, ['judul' => 'Proposal 1']);
    buatProposalDikirim($user, $skema, ['judul' => 'Proposal 2']);

    // Draft ketiga masih boleh disimpan.
    $draft = $this->actingAs($user)->post(route('proposal.store'), [
        'aksi' => 'draft',
        'judul' => 'Draft ketiga',
    ]);
    $draft->assertSessionHasNoErrors();

    // Submit ketiga ditolak karena kuota habis.
    $response = $this->actingAs($user)
        ->post(route('proposal.store'), payloadSubmit($skema, $fakultas));
    $response->assertSessionHas('error');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(2);
});

test('anggota bisa konfirmasi Setuju, orang lain tidak bisa', function () {
    Storage::fake('public');
    $ketua = buatPengaju(['name' => 'Dr. Andi']);
    $anggota = buatPengaju(['name' => 'Budi Santoso']);
    $orangLain = buatPengaju(['name' => 'Citra Lestari']);
    $skema = buatSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    buatPeriodeAktif();

    $this->actingAs($ketua)->post(route('proposal.store'), payloadSubmit($skema, $fakultas, [
        'anggota' => ['Budi Santoso'],
    ]));

    $undangan = ProposalMember::where('user_id', $anggota->id)->firstOrFail();

    // Orang lain mencoba konfirmasi undangan Budi -> 403.
    $this->actingAs($orangLain)
        ->post(route('anggota.konfirmasi', $undangan->id), ['aksi' => 'setuju'])
        ->assertForbidden();

    // Budi sendiri konfirmasi Setuju.
    $response = $this->actingAs($anggota)
        ->post(route('anggota.konfirmasi', $undangan->id), ['aksi' => 'setuju']);

    $response->assertRedirect(route('anggota.undangan'));
    expect($undangan->fresh()->status_konfirmasi)->toBe('Diterima');
});

test('cek kuota menampilkan hitungan per kategori skema', function () {
    $user = buatPengaju();
    $skema = buatSkema(['kategori' => 'Pembinaan', 'kuota' => 2]);
    buatProposalDikirim($user, $skema, ['judul' => 'Proposal 1']);

    $response = $this->actingAs($user)->get(route('dosen.cek-kuota'));

    $response->assertOk();
    $response->assertSee('Pembinaan');
    $response->assertSee('1');
});
