<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Helper file ini diawali vlp (verifikasi laporan peran) dan
// didefinisikan sendiri (jangan memakai helper dari file test lain
// — urutan load tidak dijamin). Pola setup mengikuti
// KeputusanAuditLaporanTest (kaBuatUser, kaBuatProposal,
// kaUnggahKemajuan).

function vlpBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function vlpBuatProposal(User $user, array $attrs = []): Proposal
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

function vlpFilePdf(string $nama = 'laporan.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($nama, 100, 'application/pdf');
}

/** Unggah laporan kemajuan sebagai ketua (status jadi Menunggu Verifikasi). */
function vlpUnggahKemajuan(User $pengaju, Proposal $proposal): void
{
    $response = test()->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => vlpFilePdf('kemajuan.pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
}

/** Unggah laporan akhir sebagai ketua (wajib sudah ada kemajuan; status jadi Menunggu Verifikasi). */
function vlpUnggahAkhir(User $pengaju, Proposal $proposal): void
{
    $response = test()->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => vlpFilePdf('akhir.pdf'),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
}

/** Siapkan proposal yang sudah punya laporan kemajuan + laporan akhir, keduanya Menunggu Verifikasi. */
function vlpSiapkanLaporan(User $pengaju, Proposal $proposal): void
{
    vlpUnggahKemajuan($pengaju, $proposal);
    vlpUnggahAkhir($pengaju, $proposal);
}

/*
 |----------------------------------------------------------------------
 | Keputusan 28 Nov 2026: verifikasi laporan kemajuan/akhir boleh oleh
 | LPPM, admin, atau Kepala LPPM (sebelumnya hanya LPPM).
 |----------------------------------------------------------------------
 */

test('admin bisa memverifikasi laporan kemajuan (terverifikasi)', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpUnggahKemajuan($pengaju, $proposal);

    $response = $this->actingAs(vlpBuatUser('admin'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Terverifikasi');
});

test('admin bisa memverifikasi laporan akhir (terverifikasi)', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpSiapkanLaporan($pengaju, $proposal);

    $response = $this->actingAs(vlpBuatUser('admin'))->post(route('laporan.akhir.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Terverifikasi');
});

test('kepala_lppm bisa memverifikasi laporan kemajuan (terverifikasi)', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpUnggahKemajuan($pengaju, $proposal);

    $response = $this->actingAs(vlpBuatUser('kepala_lppm'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Terverifikasi');
});

test('kepala_lppm bisa memverifikasi laporan akhir (terverifikasi)', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpSiapkanLaporan($pengaju, $proposal);

    $response = $this->actingAs(vlpBuatUser('kepala_lppm'))->post(route('laporan.akhir.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Terverifikasi');
});

test('reviewer mendapat 403 saat memverifikasi laporan kemajuan', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpUnggahKemajuan($pengaju, $proposal);

    $this->actingAs(vlpBuatUser('reviewer'))->post(route('laporan.kemajuan.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertForbidden();

    expect($proposal->fresh()->status_laporan_kemajuan)->toBe('Menunggu Verifikasi');
});

test('pengaju mendapat 403 saat memverifikasi laporan akhir', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpSiapkanLaporan($pengaju, $proposal);

    $this->actingAs($pengaju)->post(route('laporan.akhir.verifikasi', $proposal->id), [
        'keputusan' => 'terverifikasi',
    ])->assertForbidden();

    expect($proposal->fresh()->status_laporan_akhir)->toBe('Menunggu Verifikasi');
});

test('lppm tetap bisa memverifikasi laporan akhir (revisi)', function () {
    Storage::fake('public');
    $pengaju = vlpBuatUser('pengaju');
    $proposal = vlpBuatProposal($pengaju);
    vlpSiapkanLaporan($pengaju, $proposal);

    $response = $this->actingAs(vlpBuatUser('lppm'))->post(route('laporan.akhir.verifikasi', $proposal->id), [
        'keputusan' => 'revisi',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_laporan_akhir)->toBe('Revisi');
});
