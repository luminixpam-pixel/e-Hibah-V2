<?php

// Perbaikan bug uji manual web ke-4, 28 Nov 2026 (Bagian B — PREVIEW +
// PLOTTING + LAPORAN):
// - #10: /laporan-kemajuan — form unggah vs tabel tidak sepakat soal
//   "Disetujui"; kolom Reviewer selalu "Belum Ditentukan".
// - #11: /laporan-akhir — nama reviewer tanpa pemisah.
// Helper berprefix pu4b* agar tidak bentrok dengan pu4* (pekerja lain).
//
// CATATAN: bug #8 (tab unduhan DOCX) dan #9 (klik pertama dropdown /
// lonceng) adalah perilaku browser (pemblokir popup, waktu pasang
// handler) sehingga tidak ada test HTTP di sini — verifikasinya lewat
// uji web manual.

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pu4bUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pu4bSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pu4bProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'status_pendanaan' => 'Proses',
        'biaya' => 1000000,
    ], $attrs));
}

function pu4bPlottedProposal(User $pengaju, Skema $skema, array $attrs = []): Proposal
{
    $proposal = pu4bProposal($pengaju, $skema, $attrs);
    $reviewer1 = pu4bUser('reviewer', ['name' => 'Reviewer Proposal']);
    $reviewer2 = pu4bUser('reviewer', ['name' => 'Reviewer Proposal 2']);
    $proposal->reviewers()->sync([$reviewer1->id, $reviewer2->id]);

    return $proposal;
}

// --- Bug #10: form unggah memakai definisi "Disetujui pendanaan" ---

test('pu4b: laporan kemajuan menampilkan proposal yang Disetujui pendanaannya di dropdown', function () {
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    $proposal = pu4bProposal($pengaju, $skema, [
        'judul' => 'Proposal didanai uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);

    $response = $this->actingAs($pengaju)->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertDontSee('Belum ada proposal yang disetujui pendanaannya.');
    $response->assertSee('<option value="'.$proposal->id.'"', false);
});

test('pu4b: laporan kemajuan tidak menampilkan proposal yang baru lolos review tapi belum diputuskan pendanaannya', function () {
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    pu4bProposal($pengaju, $skema, [
        'judul' => 'Proposal lolos review uji',
        'status' => 'Review Selesai',
        'status_pendanaan' => 'Proses',
    ]);

    $response = $this->actingAs($pengaju)->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('Belum ada proposal yang disetujui pendanaannya.');
    $response->assertSee('>Proses<', false);
});

test('pu4b: dropdown laporan kemajuan memakai status_pendanaan, bukan status alur', function () {
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    $proposal = pu4bProposal($pengaju, $skema, [
        'judul' => 'Proposal status menyimpang uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Proses',
    ]);

    $response = $this->actingAs($pengaju)->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('Belum ada proposal yang disetujui pendanaannya.');
    $response->assertDontSee('<option value="'.$proposal->id.'"', false);
});

// --- Bug #10: kolom Reviewer + kolom Status Pendanaan di tabel ---

test('pu4b: laporan kemajuan menampilkan nama reviewer yang sudah di-plotting', function () {
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    pu4bPlottedProposal($pengaju, $skema, [
        'judul' => 'Proposal ter-plotting uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);

    $response = $this->actingAs($pengaju)->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('Reviewer Proposal, Reviewer Proposal 2');
    $response->assertDontSee('Belum Ditentukan');
    $response->assertSee('Status Pendanaan');
    $response->assertSee('>Disetujui<', false);
});

// --- Bug #10: guard store sepakat dengan dropdown ---

test('pu4b: unggah laporan kemajuan ditolak bila pendanaan belum disetujui', function () {
    Storage::fake('public');
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    $proposal = pu4bProposal($pengaju, $skema, [
        'status' => 'Review Selesai',
        'status_pendanaan' => 'Proses',
    ]);

    $response = $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        'keterangan' => 'Progres 50%.',
    ]);

    $response->assertSessionHas('error', 'Laporan kemajuan hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
    expect($proposal->fresh()->file_laporan)->toBeNull();
});

test('pu4b: unggah laporan kemajuan diterima bila pendanaan sudah disetujui', function () {
    Storage::fake('public');
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    $proposal = pu4bProposal($pengaju, $skema, [
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);

    $response = $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
        'keterangan' => 'Progres 50%.',
    ]);

    $response->assertSessionHas('success', 'Laporan berhasil diunggah!');
    expect($proposal->fresh()->file_laporan)->not->toBeNull();
});

// --- Bug #11: pemisah nama reviewer di /laporan-akhir ---

test('pu4b: laporan akhir memisah nama reviewer dengan koma', function () {
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    pu4bPlottedProposal($pengaju, $skema, [
        'judul' => 'Proposal akhir ter-plotting uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);

    $response = $this->actingAs($pengaju)->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('Reviewer Proposal, Reviewer Proposal 2');
    $response->assertDontSee('Reviewer Proposal Reviewer Proposal 2');
});

// --- Bug #11 (konsistensi): guard store laporan akhir sepakat dengan dropdown ---

test('pu4b: unggah laporan akhir ditolak bila pendanaan belum disetujui', function () {
    Storage::fake('public');
    $pengaju = pu4bUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4bSkema();
    $proposal = pu4bProposal($pengaju, $skema, [
        'status' => 'Review Selesai',
        'status_pendanaan' => 'Proses',
    ]);

    $response = $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => UploadedFile::fake()->create('laporan-akhir.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHas('error', 'Laporan akhir hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});
