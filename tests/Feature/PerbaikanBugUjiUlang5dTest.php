<?php

// Perbaikan bug uji manual web ke-5, 28 Nov 2026 (Bagian D — LAPORAN +
// PERPANJANG TENGGAT):
// - Butir 7: sebagai LPPM, tombol Verifikasi/Revisi laporan dan daftar
//   "Riwayat versi" tidak terlihat, dan /laporan-akhir kosong padahal
//   laporan akhir EH-2026-0003 sudah diunggah. Penyebabnya ada di
//   LaporanAkhirController@index: hanya admin yang diistimewakan,
//   semua peran lain (termasuk LPPM) diperlakukan seperti pengaju
//   sehingga daftarnya kosong. Perbaikan: samakan pemetaan peran
//   dengan LaporanKemajuanController@index (pengaju = milik sendiri,
//   reviewer = yang di-plot, staf pemantau = semua proposal).
//   "Riwayat versi" sendiri BUKAN bug: baris versi baru dibuat saat
//   laporan diunggah ulang (arsipkanVersiLama), jadi unggahan pertama
//   memang tidak menampilkannya.
// - Butir 8: form "Perpanjang Tenggat" tidak terlihat sebagai LPPM.
//   Penyebabnya ada di view proposal-perlu-direview: blok manajemen
//   (plotting + perpanjang tenggat) hanya dirender untuk admin,
//   padahal endpoint perpanjangTenggat mengizinkan admin,lppm.
//   Perbaikan: lebarkan blok itu ke admin + LPPM. Endpoint tidak
//   diubah (sudah diuji PerpanjangTenggatTest); di sini yang diuji
//   hanya render view-nya.
// Helper berprefix ub5d* agar tidak bentrok dengan file test lain.

use App\Models\Fakultas;
use App\Models\LaporanVersiBerkas;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function ub5dUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function ub5dProposalDidanai(User $pengaju, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $pengaju->id,
        'judul' => 'Proposal didanai uji laporan',
        'nama_ketua' => $pengaju->name,
        'fakultas_prodi' => $fakultas->id,
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function ub5dUnggahKemajuan(User $pengaju, Proposal $proposal, string $nama = 'kemajuan.pdf'): void
{
    $response = test()->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => UploadedFile::fake()->create($nama, 100, 'application/pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
}

function ub5dUnggahAkhir(User $pengaju, Proposal $proposal, string $nama = 'akhir.pdf'): void
{
    $response = test()->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => UploadedFile::fake()->create($nama, 100, 'application/pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
}

/** Proposal didanai dengan laporan kemajuan + laporan akhir, keduanya Menunggu Verifikasi. */
function ub5dSiapkanLaporan(User $pengaju, array $attrs = []): Proposal
{
    $proposal = ub5dProposalDidanai($pengaju, $attrs);
    ub5dUnggahKemajuan($pengaju, $proposal);
    ub5dUnggahAkhir($pengaju, $proposal);

    return $proposal;
}

/** Proposal "Sedang Direview" dengan 2 reviewer + tenggat. */
function ub5dProposalDireview(User $pengaju, array $attrs = []): Proposal
{
    $reviewer1 = ub5dUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = ub5dUser('reviewer', ['name' => 'Dewi']);
    $proposal = ub5dProposalDidanai($pengaju, array_merge([
        'judul' => 'Proposal sedang direview uji',
        'status' => 'Sedang Direview',
        'status_pendanaan' => 'Proses',
        'review_deadline' => now()->addWeek(),
    ], $attrs));
    $proposal->reviewers()->sync([$reviewer1->id, $reviewer2->id]);

    return $proposal;
}

// --- Butir 7: visibilitas laporan akhir untuk LPPM ---

test('ub5d: lppm melihat laporan akhir proposal yang didanai beserta tombol verifikasi', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dSiapkanLaporan($pengaju, ['judul' => 'Proposal akhir LPPM uji']);

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('Proposal akhir LPPM uji');
    $response->assertSee('UNDUH LAPORAN AKHIR');
    $response->assertDontSee('Belum ada data laporan akhir.');
    // Tombol Verifikasi + Revisi: form ke route verifikasi laporan akhir.
    $response->assertSee(route('laporan.akhir.verifikasi', $proposal->id));
    $response->assertSee('Verifikasi');
    $response->assertSee('Revisi');
});

test('ub5d: tombol verifikasi laporan akhir hilang setelah laporan terverifikasi', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dSiapkanLaporan($pengaju);

    $this->actingAs(ub5dUser('lppm'))->post(route('laporan.akhir.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Terverifikasi');

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('Proposal didanai uji laporan');
    $response->assertSee('Terverifikasi');
    $response->assertDontSee(route('laporan.akhir.verifikasi', $proposal->id));
});

test('ub5d: tombol verifikasi laporan kemajuan muncul untuk lppm saat Menunggu Verifikasi', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dProposalDidanai($pengaju, ['judul' => 'Proposal kemajuan LPPM uji']);
    ub5dUnggahKemajuan($pengaju, $proposal);

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('Proposal kemajuan LPPM uji');
    $response->assertSee('UNDUH LAPORAN');
    $response->assertSee(route('laporan.kemajuan.verifikasi', $proposal->id));
    $response->assertSee('Verifikasi');
});

// --- Butir 7: riwayat versi (hanya muncul setelah unggah ulang) ---

test('ub5d: riwayat versi laporan akhir muncul setelah unggah ulang', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dProposalDidanai($pengaju);
    ub5dUnggahKemajuan($pengaju, $proposal);
    ub5dUnggahAkhir($pengaju, $proposal, 'akhir-v1.pdf');
    ub5dUnggahAkhir($pengaju, $proposal, 'akhir-v2.pdf');

    $versi = LaporanVersiBerkas::where('proposal_id', $proposal->id)->where('jenis', 'akhir')->first();
    expect($versi)->not->toBeNull()
        ->and($versi->nomor_versi)->toBe(1);

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('Riwayat versi:');
    $response->assertSee('Versi 1');
    $response->assertSee(route('laporan.versi.download', $versi));
});

test('ub5d: riwayat versi laporan kemajuan muncul setelah unggah ulang', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dProposalDidanai($pengaju);
    ub5dUnggahKemajuan($pengaju, $proposal, 'kemajuan-v1.pdf');
    ub5dUnggahKemajuan($pengaju, $proposal, 'kemajuan-v2.pdf');

    $versi = LaporanVersiBerkas::where('proposal_id', $proposal->id)->where('jenis', 'kemajuan')->first();
    expect($versi)->not->toBeNull()
        ->and($versi->nomor_versi)->toBe(1);

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('Riwayat versi:');
    $response->assertSee('Versi 1');
    $response->assertSee(route('laporan.versi.download', $versi));
});

test('ub5d: riwayat versi tidak muncul bila laporan baru diunggah sekali', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    ub5dSiapkanLaporan($pengaju);

    expect(LaporanVersiBerkas::count())->toBe(0);

    $this->actingAs(ub5dUser('lppm'))->get(route('laporan.akhir.index'))
        ->assertOk()
        ->assertDontSee('Riwayat versi:');
    $this->actingAs(ub5dUser('lppm'))->get(route('laporan.kemajuan.index'))
        ->assertOk()
        ->assertDontSee('Riwayat versi:');
});

// --- Butir 7: perbaikan tidak membuka daftar ke semua orang ---

test('ub5d: pengaju lain dan reviewer yang tidak di-plot tidak melihat laporan akhir orang lain', function () {
    Storage::fake('public');
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    ub5dSiapkanLaporan($pengaju, ['judul' => 'Proposal rahasia uji']);

    $this->actingAs(ub5dUser('pengaju', ['name' => 'Dr. Budi']))
        ->get(route('laporan.akhir.index'))
        ->assertOk()
        ->assertDontSee('Proposal rahasia uji');

    $this->actingAs(ub5dUser('reviewer', ['name' => 'Rina']))
        ->get(route('laporan.akhir.index'))
        ->assertOk()
        ->assertDontSee('Proposal rahasia uji');
});

// --- Butir 8: form Perpanjang Tenggat untuk LPPM (render view) ---

test('ub5d: form perpanjang tenggat muncul untuk lppm dan admin pada proposal Sedang Direview', function () {
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dProposalDireview($pengaju);

    foreach (['lppm', 'admin'] as $role) {
        $response = $this->actingAs(ub5dUser($role))->get(route('monitoring.proposalPerluDireview'));

        $response->assertOk();
        $response->assertSee('Proposal sedang direview uji');
        $response->assertSee('Manajemen Reviewer');
        $response->assertSee('Perpanjang Tenggat');
        $response->assertSee(route('proposal.perpanjangTenggat', $proposal->id));
        $response->assertSee('Simpan Tenggat Baru');
        // Form plotting (assignReviewer) tidak dirender untuk proposal
        // yang sedang direview — hanya form perpanjang tenggat.
        $response->assertDontSee(route('proposal.assignReviewer', $proposal->id));
    }
});

test('ub5d: form perpanjang tenggat tidak muncul untuk lppm pada proposal Lolos Administrasi', function () {
    $pengaju = ub5dUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = ub5dProposalDidanai($pengaju, [
        'judul' => 'Proposal lolos admin uji',
        'status' => 'Lolos Administrasi',
        'status_pendanaan' => 'Proses',
        'review_deadline' => now()->addWeek(),
    ]);

    $response = $this->actingAs(ub5dUser('lppm'))->get(route('monitoring.proposalPerluDireview'));

    $response->assertOk();
    $response->assertSee('Proposal lolos admin uji');
    $response->assertSee('Ubah Tenggat & Reviewer');
    $response->assertSee(route('proposal.assignReviewer', $proposal->id));
    $response->assertDontSee(route('proposal.perpanjangTenggat', $proposal->id));
    $response->assertDontSee('Simpan Tenggat Baru');
});
