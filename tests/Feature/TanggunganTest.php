<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f5tgBuatPengaju(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'pengaju'], $attrs));
}

function f5tgBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f5tgBuatPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function f5tgBuatFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function f5tgPayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
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

function f5tgBuatProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal disetujui lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => f5tgBuatFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function f5tgBuatTemuan(Proposal $proposal, string $statusTemuan): MonevFinding
{
    // uraian_temuan + rekomendasi NOT NULL di migrasi monev_findings.
    return MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan Monev contoh.',
        'rekomendasi' => 'Perbaiki sesuai rekomendasi.',
        'status_temuan' => $statusTemuan,
    ]);
}

test('submit ditolak bila pengaju punya proposal Disetujui tanpa laporan akhir', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju();
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    f5tgBuatProposalDisetujui($user, $skema);

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('submit ditolak bila ada temuan Monev berstatus Open', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju();
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    $lama = f5tgBuatProposalDisetujui($user, $skema, ['file_laporan_akhir' => 'laporan_akhir/laporan.pdf']);
    f5tgBuatTemuan($lama, 'Open');

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('submit ditolak bila ada temuan Monev berstatus In_Review', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju();
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    $lama = f5tgBuatProposalDisetujui($user, $skema, ['file_laporan_akhir' => 'laporan_akhir/laporan.pdf']);
    f5tgBuatTemuan($lama, 'In_Review');

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('submit boleh bila laporan akhir sudah diunggah dan tidak ada temuan terbuka', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju(['name' => 'Dr. Andi']);
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    f5tgBuatProposalDisetujui($user, $skema, ['file_laporan_akhir' => 'laporan_akhir/laporan.pdf']);

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(1);
});

test('submit boleh bila temuan Monev sudah Closed', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju(['name' => 'Dr. Andi']);
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    $lama = f5tgBuatProposalDisetujui($user, $skema, ['file_laporan_akhir' => 'laporan_akhir/laporan.pdf']);
    f5tgBuatTemuan($lama, 'Closed');

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(1);
});

test('simpan draft tetap boleh walau pengaju bertanggungan', function () {
    $user = f5tgBuatPengaju();
    $skema = f5tgBuatSkema();
    f5tgBuatPeriodeAktif();

    f5tgBuatProposalDisetujui($user, $skema);

    $response = $this->actingAs($user)->post(route('proposal.store'), [
        'aksi' => 'draft',
        'judul' => 'Draft baru walau bertanggungan',
    ]);

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('proposals', [
        'judul' => 'Draft baru walau bertanggungan',
        'status' => 'Draft',
        'user_id' => $user->id,
    ]);
});

test('kirim ulang draft ditolak bila pengaju bertanggungan', function () {
    Storage::fake('public');
    $user = f5tgBuatPengaju(['name' => 'Dr. Andi']);
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    f5tgBuatProposalDisetujui($user, $skema);

    $draft = Proposal::create([
        'user_id' => $user->id,
        'judul' => 'Draft lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'file_path' => 'proposal_files/draft.pdf',
        'status' => 'Draft',
        'biaya' => 1000000,
    ]);

    $response = $this->actingAs($user)->put(route('proposal.update', $draft->id), [
        'aksi' => 'submit',
        'judul' => 'Draft lama',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ]);

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');
    expect($draft->fresh()->status)->toBe('Draft');
});

test('anggota dengan tanggungan diblokir dengan pesannya sendiri', function () {
    Storage::fake('public');
    $ketua = f5tgBuatPengaju(['name' => 'Dr. Andi']);
    $anggota = f5tgBuatPengaju(['name' => 'Budi Santoso']);
    $skema = f5tgBuatSkema();
    $fakultas = f5tgBuatFakultas();
    f5tgBuatPeriodeAktif();

    f5tgBuatProposalDisetujui($anggota, $skema, ['judul' => 'Proposal disetujui Budi']);

    $response = $this->actingAs($ketua)
        ->post(route('proposal.store'), f5tgPayloadSubmit($skema, $fakultas, [
            'anggota' => ['Budi Santoso'],
        ]));

    $response->assertSessionHas('error', 'Anggota Budi Santoso masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat diikutsertakan.');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});
