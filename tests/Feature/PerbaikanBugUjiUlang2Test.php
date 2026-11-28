<?php

// Perbaikan bug uji ulang web 27 Nov 2026 malam (Bagian B): bug #7
// (plafon draft edit + berkas hilang) dan bug #1 (form verifikasi tinjau).
// Helper berprefix pu2* agar tidak bentrok dengan bb* / f3* / at*.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pu2User(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pu2Skema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pu2Proposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

function pu2RabDiAtasPlafon(): array
{
    // Plafon skema uji Rp25.000.000; total RAB Rp30.000.000.
    return [
        ['nama_komponen' => 'Honorarium', 'jumlah' => 30000000, 'keterangan' => ''],
    ];
}

function pu2PeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

// --- Bug #1: form verifikasi tinjau ---

test('pu2: verifikasi Lolos berhasil lewat HTTP', function () {
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = pu2User('verifikator', ['name' => 'Veri']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), [
            'hasil' => 'Lolos',
            'catatan' => 'Berkas lengkap.',
        ]);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseHas('verifications', [
        'proposal_id' => $proposal->id,
        'verifikator_id' => $verifikator->id,
        'hasil' => 'Lolos',
    ]);
});

test('pu2: verifikasi Revisi dengan alasan berhasil lewat HTTP', function () {
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = pu2User('verifikator', ['name' => 'Veri']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), [
            'hasil' => 'Revisi',
            'catatan' => 'File RAB belum dilampirkan.',
        ]);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Revisi Administrasi');
    $this->assertDatabaseHas('verifications', [
        'proposal_id' => $proposal->id,
        'hasil' => 'Revisi',
        'catatan' => 'File RAB belum dilampirkan.',
    ]);
});

test('pu2: verifikasi Revisi tanpa alasan ditolak validasi', function () {
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = pu2User('verifikator', ['name' => 'Veri']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Revisi']);

    $response->assertSessionHasErrors('catatan');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    $this->assertDatabaseCount('verifications', 0);
});

// --- Bug #7: plafon hanya dicek saat kirim, bukan saat simpan draft ---

test('pu2: simpan draft di form EDIT dengan RAB di atas plafon diizinkan', function () {
    pu2PeriodeAktif();
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema, [
        'judul' => 'Proposal draft edit',
        'status' => 'Draft',
        'file_path' => 'proposal_files/lama.pdf',
    ]);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => 'Proposal draft edit',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $proposal->fakultas_prodi,
            'skema_id' => $skema->id,
            'rab' => pu2RabDiAtasPlafon(),
            'anggota' => [],
        ]);

    $response->assertRedirect(route('monitoring.proposalDikirim'));
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Draft')
        ->and((float) $proposal->fresh()->biaya)->toBe(30000000.0);
});

test('pu2: simpan draft di form CREATE dengan RAB di atas plafon diizinkan', function () {
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu2Skema();
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), [
            'aksi' => 'draft',
            'judul' => 'Draft baru di atas plafon',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'skema_id' => $skema->id,
            'rab' => pu2RabDiAtasPlafon(),
            'anggota' => [],
        ]);

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('proposals', [
        'judul' => 'Draft baru di atas plafon',
        'status' => 'Draft',
        'biaya' => 30000000,
    ]);
});

test('pu2: kirim dari form CREATE dengan RAB di atas plafon tetap ditolak', function () {
    Storage::fake('public');
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu2Skema();
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), [
            'aksi' => 'submit',
            'judul' => 'Proposal kirim di atas plafon',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'skema_id' => $skema->id,
            'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
            'rab' => pu2RabDiAtasPlafon(),
            'anggota' => [],
        ]);

    $response->assertSessionHas('error', 'Total RAB Rp 30.000.000 melebihi plafon skema Pembinaan (Rp 25.000.000).');
    $this->assertDatabaseMissing('proposals', ['judul' => 'Proposal kirim di atas plafon']);
});

test('pu2: kirim dari form EDIT dengan RAB di atas plafon tetap ditolak', function () {
    pu2PeriodeAktif();
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema, [
        'judul' => 'Proposal draft kirim',
        'status' => 'Draft',
        'file_path' => 'proposal_files/lama.pdf',
    ]);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'submit',
            'judul' => 'Proposal draft kirim',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $proposal->fakultas_prodi,
            'skema_id' => $skema->id,
            'rab' => pu2RabDiAtasPlafon(),
            'anggota' => [],
        ]);

    $response->assertSessionHas('error', 'Total RAB Rp 30.000.000 melebihi plafon skema Pembinaan (Rp 25.000.000).');
    expect($proposal->fresh()->status)->toBe('Draft')
        ->and((float) $proposal->fresh()->biaya)->toBe(1000000.0);
});

// --- Bug #7: berkas lama tidak hilang saat validasi gagal ---

test('pu2: berkas proposal tidak hilang saat simpan draft edit gagal validasi', function () {
    Storage::fake('public');
    Storage::disk('public')->put('proposal_files/lama.pdf', 'isi berkas lama');
    pu2PeriodeAktif();
    $pengaju = pu2User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu2Skema();
    $proposal = pu2Proposal($pengaju, $skema, [
        'judul' => 'Proposal berberkas',
        'status' => 'Draft',
        'file_path' => 'proposal_files/lama.pdf',
    ]);

    // Judul kosong: validasi gagal sebelum alur apa pun menyentuh berkas.
    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => '',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $proposal->fakultas_prodi,
            'skema_id' => $skema->id,
            'rab' => pu2RabDiAtasPlafon(),
            'anggota' => [],
        ]);

    $response->assertSessionHasErrors('judul');
    expect($proposal->fresh()->file_path)->toBe('proposal_files/lama.pdf');
    Storage::disk('public')->assertExists('proposal_files/lama.pdf');

    // Tampilan edit tetap menunjukkan berkas tersimpan (bukan input lama).
    // Halaman edit mensyaratkan masa hibah aktif.
    HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);

    $this->actingAs($pengaju)
        ->get(route('proposal.edit', $proposal->id))
        ->assertOk()
        ->assertSee('lama.pdf');
});
