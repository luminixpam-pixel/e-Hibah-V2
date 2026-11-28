<?php

use App\Models\Fakultas;
use App\Models\LaporanVersiBerkas;
use App\Models\Logbook;
use App\Models\Luaran;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Helper file ini diawali ka (keputusan audit) dan didefinisikan
// sendiri (jangan memakai helper dari file test lain — urutan load
// tidak dijamin).

function kaBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function kaBuatProposal(User $user, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal didanai',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function kaTambahAnggota(Proposal $proposal, User $user, string $status = 'Diterima'): ProposalMember
{
    return ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $user->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => $status,
    ]);
}

function kaFilePdf(string $nama = 'laporan.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($nama, 100, 'application/pdf');
}

/** Unggah laporan kemajuan sebagai ketua (syarat butir 1). */
function kaUnggahKemajuan(User $pengaju, Proposal $proposal): void
{
    $response = test()->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('kemajuan.pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
}

/*
 |----------------------------------------------------------------------
 | Butir 1 — laporan akhir wajib ada laporan kemajuan dulu
 |----------------------------------------------------------------------
 */

test('butir 1: laporan akhir tanpa laporan kemajuan ditolak untuk ketua', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);

    $response = $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

test('butir 1: laporan akhir dengan laporan kemajuan lolos untuk ketua', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    $response = $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();
});

test('butir 1: aturan wajib kemajuan juga berlaku untuk admin', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);

    $response = $this->actingAs(kaBuatUser('admin'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

/*
 |----------------------------------------------------------------------
 | Butir 3 — unggah ulang menyimpan riwayat versi
 |----------------------------------------------------------------------
 */

test('butir 3: unggah ulang laporan kemajuan mengarsipkan berkas lama sebagai versi 1', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    $lama = $proposal->fresh()->file_laporan;

    $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('kemajuan-v2.pdf'),
    ])->assertRedirect();

    // Berkas lama TIDAK dihapus (perubahan perilaku dari 4fb1ddef).
    Storage::disk('public')->assertExists($lama);
    $this->assertDatabaseHas('laporan_versi_berkas', [
        'proposal_id' => $proposal->id,
        'jenis' => 'kemajuan',
        'file_path' => $lama,
        'nomor_versi' => 1,
    ]);
    expect($proposal->fresh()->file_laporan)->not->toBe($lama);
});

test('butir 3: unggah ulang laporan akhir mengarsipkan berkas lama dan nomor versi bertambah', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir-v1.pdf'),
    ])->assertRedirect();
    $v1 = $proposal->fresh()->file_laporan_akhir;

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir-v2.pdf'),
    ])->assertRedirect();
    $v2 = $proposal->fresh()->file_laporan_akhir;

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir-v3.pdf'),
    ])->assertRedirect();

    Storage::disk('public')->assertExists($v1);
    Storage::disk('public')->assertExists($v2);
    $this->assertDatabaseHas('laporan_versi_berkas', [
        'proposal_id' => $proposal->id, 'jenis' => 'akhir', 'file_path' => $v1, 'nomor_versi' => 1,
    ]);
    $this->assertDatabaseHas('laporan_versi_berkas', [
        'proposal_id' => $proposal->id, 'jenis' => 'akhir', 'file_path' => $v2, 'nomor_versi' => 2,
    ]);
});

test('butir 3: berkas versi lama bisa diunduh yang berhak dan ditolak 403 bagi yang tidak berhak', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $orangLain = kaBuatUser('pengaju', ['name' => 'Orang Lain']);
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    $lama = $proposal->fresh()->file_laporan;

    $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('kemajuan-v2.pdf'),
    ])->assertRedirect();

    $versi = LaporanVersiBerkas::where('proposal_id', $proposal->id)->where('jenis', 'kemajuan')->firstOrFail();
    expect($versi->file_path)->toBe($lama);

    $this->actingAs($pengaju)->get(route('laporan.versi.download', $versi))->assertOk();
    $this->actingAs(kaBuatUser('lppm'))->get(route('laporan.versi.download', $versi))->assertOk();
    $this->actingAs($orangLain)->get(route('laporan.versi.download', $versi))->assertForbidden();
});

/*
 |----------------------------------------------------------------------
 | Butir 4 — status verifikasi + hapus laporan
 |----------------------------------------------------------------------
 */

test('butir 4: laporan yang baru diunggah berstatus Menunggu Verifikasi', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Menunggu Verifikasi');

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ])->assertRedirect();
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Menunggu Verifikasi');
});

test('butir 4: lppm bisa memverifikasi laporan, pengaju dan admin tidak bisa', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    // Pengaju (bukan LPPM): 403 via middleware role:lppm.
    $this->actingAs($pengaju)->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertForbidden();

    // Admin juga bukan LPPM: 403.
    $this->actingAs(kaBuatUser('admin'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertForbidden();

    // LPPM berhasil.
    $response = $this->actingAs(kaBuatUser('lppm'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Terverifikasi');
});

test('butir 4: lppm bisa meminta revisi dan verifikasi kedua ditolak', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    $lppm = kaBuatUser('lppm');
    $this->actingAs($lppm)->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'revisi',
    ])->assertRedirect();
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Revisi');

    // Sudah Revisi (bukan Menunggu Verifikasi): verifikasi ulang ditolak.
    $response = $this->actingAs($lppm)->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Revisi');
});

test('butir 4: hapus laporan oleh ketua berhasil, oleh pengaju lain 403, laporan Terverifikasi tidak bisa dihapus', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $orangLain = kaBuatUser('pengaju', ['name' => 'Orang Lain']);
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    $file = $proposal->fresh()->file_laporan;

    // Pengaju lain: 403.
    $this->actingAs($orangLain)->delete(route('laporan.kemajuan.destroy', $proposal->id))->assertForbidden();
    expect($proposal->fresh()->file_laporan)->not->toBeNull();

    // LPPM verifikasi dulu → hapus ditolak.
    $this->actingAs(kaBuatUser('lppm'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertRedirect();
    $response = $this->actingAs($pengaju)->delete(route('laporan.kemajuan.destroy', $proposal->id));
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan)->not->toBeNull();

    // Unggah ulang (status kembali Menunggu Verifikasi) lalu hapus berhasil.
    $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('kemajuan-v2.pdf'),
    ])->assertRedirect();
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Menunggu Verifikasi');

    $response = $this->actingAs($pengaju)->delete(route('laporan.kemajuan.destroy', $proposal->id));
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan)->toBeNull();
    expect($proposal->fresh()->status_laporan_kemajuan)->toBeNull();
    Storage::disk('public')->assertMissing($file);
    $this->assertDatabaseCount('laporan_versi_berkas', 0);
});

test('butir 4: hapus laporan kemajuan ditolak bila laporan akhir sudah diunggah', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ])->assertRedirect();

    $response = $this->actingAs($pengaju)->delete(route('laporan.kemajuan.destroy', $proposal->id));
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan)->not->toBeNull();
});

test('butir 4: hapus laporan akhir oleh admin berhasil dan tanggungan muncul kembali', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);
    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ])->assertRedirect();
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();

    $response = $this->actingAs(kaBuatUser('admin'))->delete(route('laporan.akhir.destroy', $proposal->id));
    $response->assertRedirect();
    $response->assertSessionHas('success');
    // Kolom dikosongkan → hasTanggunganLaporan() di ProposalController
    // kembali menganggap pengaju bertanggungan (file_laporan_akhir null).
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
    expect($proposal->fresh()->status_laporan_akhir)->toBeNull();
});

/*
 |----------------------------------------------------------------------
 | Butir 5 — admin boleh mengunggah laporan akhir atas nama pengaju
 |----------------------------------------------------------------------
 */

test('butir 5: admin bisa mengunggah laporan akhir bila sudah ada kemajuan', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    $response = $this->actingAs(kaBuatUser('admin'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir-oleh-admin.pdf'),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Menunggu Verifikasi');
});

test('butir 5: reviewer tetap ditolak 403 saat mengunggah laporan akhir', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposal = kaBuatProposal($pengaju);
    kaUnggahKemajuan($pengaju, $proposal);

    $this->actingAs(kaBuatUser('reviewer'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir.pdf'),
    ])->assertForbidden();

    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

/*
 |----------------------------------------------------------------------
 | Butir 6 — anggota tim Diterima boleh mengunggah laporan, logbook, luaran
 |----------------------------------------------------------------------
 */

test('butir 6: anggota Diterima bisa mengunggah laporan kemajuan dan laporan akhir', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $anggota = kaBuatUser('pengaju', ['name' => 'Anggota Tim']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($anggota)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('kemajuan-anggota.pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan)->not->toBeNull();

    $response = $this->actingAs($anggota)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf('akhir-anggota.pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();
});

test('butir 6: anggota Menunggu dan Ditolak tidak bisa mengunggah laporan', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $menunggu = kaBuatUser('pengaju', ['name' => 'Menunggu']);
    $ditolak = kaBuatUser('pengaju', ['name' => 'Ditolak']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $menunggu, 'Menunggu');
    kaTambahAnggota($proposal, $ditolak, 'Ditolak');

    // Laporan kemajuan: penolakan berupa redirect + error (gaya controller ini).
    $response = $this->actingAs($menunggu)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf(),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $response = $this->actingAs($ditolak)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf(),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');

    // Laporan akhir: penolakan berupa 403.
    $this->actingAs($menunggu)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf(),
    ])->assertForbidden();
    $this->actingAs($ditolak)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => kaFilePdf(),
    ])->assertForbidden();

    expect($proposal->fresh()->file_laporan)->toBeNull();
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

test('butir 6: anggota proposal lain tidak bisa mengunggah laporan', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $proposalA = kaBuatProposal($pengaju, ['judul' => 'Proposal A']);
    $proposalB = kaBuatProposal(kaBuatUser('pengaju', ['name' => 'Ketua B']), ['judul' => 'Proposal B']);
    $anggotaA = kaBuatUser('pengaju', ['name' => 'Anggota A']);
    kaTambahAnggota($proposalA, $anggotaA, 'Diterima');

    // Anggota A adalah anggota Proposal A, bukan Proposal B.
    $response = $this->actingAs($anggotaA)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposalB->id,
        'file' => kaFilePdf(),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposalB->fresh()->file_laporan)->toBeNull();

    $this->actingAs($anggotaA)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposalB->id,
        'file' => kaFilePdf(),
    ])->assertForbidden();
    expect($proposalB->fresh()->file_laporan_akhir)->toBeNull();
});

test('butir 6: anggota Diterima bisa menambah logbook dan luaran', function () {
    Storage::fake('public');
    $pengaju = kaBuatUser('pengaju');
    $anggota = kaBuatUser('pengaju', ['name' => 'Anggota Tim']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($anggota)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Kegiatan oleh anggota.',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseCount('logbooks', 1);

    $response = $this->actingAs($anggota)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Jurnal oleh anggota.',
        'status' => 'Draft',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseCount('luarans', 1);
});

test('butir 6: anggota Menunggu/Ditolak dan anggota proposal lain tidak bisa menambah logbook/luaran', function () {
    $pengaju = kaBuatUser('pengaju');
    $menunggu = kaBuatUser('pengaju', ['name' => 'Menunggu']);
    $ditolak = kaBuatUser('pengaju', ['name' => 'Ditolak']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $menunggu, 'Menunggu');
    kaTambahAnggota($proposal, $ditolak, 'Ditolak');

    $proposalB = kaBuatProposal(kaBuatUser('pengaju', ['name' => 'Ketua B']));
    $anggotaA = kaBuatUser('pengaju', ['name' => 'Anggota A']);
    kaTambahAnggota($proposal, $anggotaA, 'Diterima');

    foreach ([$menunggu, $ditolak] as $user) {
        $this->actingAs($user)->post(route('logbook.store', $proposal), [
            'tanggal' => '2026-11-20',
            'uraian_kegiatan' => 'Menyusup.',
        ])->assertForbidden();
        $this->actingAs($user)->post(route('luaran.store', $proposal), [
            'jenis' => 'Jurnal',
            'judul' => 'Menyusup.',
            'status' => 'Draft',
        ])->assertForbidden();
    }

    $this->actingAs($anggotaA)->post(route('logbook.store', $proposalB), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Menyusup ke proposal lain.',
    ])->assertForbidden();
    $this->actingAs($anggotaA)->post(route('luaran.store', $proposalB), [
        'jenis' => 'Jurnal',
        'judul' => 'Menyusup ke proposal lain.',
        'status' => 'Draft',
    ])->assertForbidden();

    $this->assertDatabaseCount('logbooks', 0);
    $this->assertDatabaseCount('luarans', 0);
});

test('butir 6: anggota Diterima hanya bisa ubah/hapus entri logbook buatannya sendiri, ketua bisa semua', function () {
    $pengaju = kaBuatUser('pengaju');
    $anggota = kaBuatUser('pengaju', ['name' => 'Anggota Tim']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $anggota, 'Diterima');

    $entriKetua = Logbook::create([
        'proposal_id' => $proposal->id,
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Entri ketua.',
        'created_by' => $pengaju->id,
    ]);
    $entriAnggota = Logbook::create([
        'proposal_id' => $proposal->id,
        'tanggal' => '2026-11-21',
        'uraian_kegiatan' => 'Entri anggota.',
        'created_by' => $anggota->id,
    ]);

    // Anggota tidak boleh mengubah entri ketua.
    $this->actingAs($anggota)->put(route('logbook.update', $entriKetua), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Diubah anggota.',
    ])->assertForbidden();
    expect($entriKetua->fresh()->uraian_kegiatan)->toBe('Entri ketua.');

    // Anggota boleh mengubah entrinya sendiri.
    $response = $this->actingAs($anggota)->put(route('logbook.update', $entriAnggota), [
        'tanggal' => '2026-11-21',
        'uraian_kegiatan' => 'Entri anggota diubah.',
    ]);
    $response->assertRedirect();
    expect($entriAnggota->fresh()->uraian_kegiatan)->toBe('Entri anggota diubah.');

    // Ketua boleh menghapus entri anggota.
    $response = $this->actingAs($pengaju)->delete(route('logbook.destroy', $entriAnggota));
    $response->assertRedirect();
    $this->assertDatabaseMissing('logbooks', ['id' => $entriAnggota->id]);

    // Anggota tidak boleh menghapus entri ketua.
    $this->actingAs($anggota)->delete(route('logbook.destroy', $entriKetua))->assertForbidden();
    $this->assertDatabaseHas('logbooks', ['id' => $entriKetua->id]);
});

test('butir 6: anggota Diterima hanya bisa ubah/hapus luaran buatannya sendiri, ketua bisa semua', function () {
    $pengaju = kaBuatUser('pengaju');
    $anggota = kaBuatUser('pengaju', ['name' => 'Anggota Tim']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $anggota, 'Diterima');

    $luaranKetua = Luaran::create([
        'proposal_id' => $proposal->id,
        'jenis' => 'Jurnal',
        'judul' => 'Luaran ketua.',
        'status' => 'Draft',
        'created_by' => $pengaju->id,
    ]);
    $luaranAnggota = Luaran::create([
        'proposal_id' => $proposal->id,
        'jenis' => 'Buku',
        'judul' => 'Luaran anggota.',
        'status' => 'Draft',
        'created_by' => $anggota->id,
    ]);

    // Anggota tidak boleh mengubah luaran ketua.
    $this->actingAs($anggota)->put(route('luaran.update', $luaranKetua), [
        'jenis' => 'Jurnal',
        'judul' => 'Diubah anggota.',
        'status' => 'Draft',
    ])->assertForbidden();
    expect($luaranKetua->fresh()->judul)->toBe('Luaran ketua.');

    // Anggota boleh mengubah luarannya sendiri.
    $response = $this->actingAs($anggota)->put(route('luaran.update', $luaranAnggota), [
        'jenis' => 'Buku',
        'judul' => 'Luaran anggota diubah.',
        'status' => 'Draft',
    ]);
    $response->assertRedirect();
    expect($luaranAnggota->fresh()->judul)->toBe('Luaran anggota diubah.');

    // Ketua boleh menghapus luaran anggota.
    $response = $this->actingAs($pengaju)->delete(route('luaran.destroy', $luaranAnggota));
    $response->assertRedirect();
    $this->assertDatabaseMissing('luarans', ['id' => $luaranAnggota->id]);

    // Anggota tidak boleh menghapus luaran ketua.
    $this->actingAs($anggota)->delete(route('luaran.destroy', $luaranKetua))->assertForbidden();
    $this->assertDatabaseHas('luarans', ['id' => $luaranKetua->id]);
});

test('butir 6: anggota Diterima bisa melihat halaman logbook dan luaran proposalnya', function () {
    $pengaju = kaBuatUser('pengaju');
    $anggota = kaBuatUser('pengaju', ['name' => 'Anggota Tim']);
    $proposal = kaBuatProposal($pengaju);
    kaTambahAnggota($proposal, $anggota, 'Diterima');

    $this->actingAs($anggota)->get(route('logbook.show', $proposal))->assertOk();
    $this->actingAs($anggota)->get(route('luaran.show', $proposal))->assertOk();
    $this->actingAs($anggota)->get(route('laporan.kemajuan.index'))->assertOk();
    $this->actingAs($anggota)->get(route('laporan.akhir.index'))->assertOk();
});
