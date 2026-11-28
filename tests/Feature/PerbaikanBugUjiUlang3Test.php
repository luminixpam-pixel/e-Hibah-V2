<?php

// Perbaikan bug uji manual web ke-3, 27 Nov 2026 (Bagian C):
// - kolom No. Pengajuan di halaman /proposal-disetujui,
// - pesan validasi judul (dan alur pengajuan/edit proposal) berbahasa
//   Indonesia.
// Helper berprefix pu3* agar tidak bentrok dengan bb* / pu2* / f3*.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function pu3User(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pu3Skema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pu3Proposal(User $user, Skema $skema, array $attrs = []): Proposal
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

function pu3PeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function pu3PayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
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

// --- Kolom No. Pengajuan di /proposal-disetujui ---
// CATATAN: kolom ini ditambahkan di view proposal-disetujui.blade.php
// (di luar ruang kerja bagian ini). Test ini mengunci perilaku yang
// diharapkan dan akan gagal sampai view tersebut diperbarui.

test('pu3: halaman proposal disetujui menampilkan kolom No. Pengajuan', function () {
    $pengaju = pu3User('pengaju', ['name' => 'Dr. Andi']);
    $admin = pu3User('admin', ['name' => 'Admin']);
    $skema = pu3Skema();
    $proposal = pu3Proposal($pengaju, $skema, [
        'judul' => 'Proposal disetujui uji',
        'status' => 'Disetujui',
        'no_pengajuan' => 'EH-2026-0001',
    ]);

    $response = $this->actingAs($admin)->get(route('monitoring.proposalDisetujui'));

    $response->assertOk();
    $response->assertSee('No. Pengajuan');
    $response->assertSee('EH-2026-0001');
    $response->assertSee('Proposal disetujui uji');
});

// --- Pesan validasi Bahasa Indonesia: pengajuan (store) ---

test('pu3: submit proposal tanpa judul menampilkan pesan validasi Bahasa Indonesia', function () {
    $pengaju = pu3User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu3Skema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu3PayloadSubmit($skema, $fakultas, ['judul' => '']));

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu3: submit proposal tanpa file menampilkan pesan validasi Bahasa Indonesia', function () {
    $pengaju = pu3User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu3Skema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    $payload = pu3PayloadSubmit($skema, $fakultas);
    unset($payload['file']);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertSessionHasErrors(['file' => 'File proposal wajib diunggah.']);
    $this->assertDatabaseCount('proposals', 0);
});

// --- Pesan validasi Bahasa Indonesia: edit (update) ---

test('pu3: edit proposal tanpa judul menampilkan pesan validasi Bahasa Indonesia', function () {
    pu3PeriodeAktif();
    $pengaju = pu3User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu3Skema();
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $proposal = pu3Proposal($pengaju, $skema, [
        'judul' => 'Judul lama',
        'status' => 'Draft',
    ]);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => '',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
        ]);

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    expect($proposal->fresh()->judul)->toBe('Judul lama');
});
