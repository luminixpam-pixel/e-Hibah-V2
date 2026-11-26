<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f4dUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f4dSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f4dPeriode(string $nama, string $startDate, string $endDate): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => $nama,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);
}

function f4dProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal analitik',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

test('dasbor kepala LPPM menampilkan analitik per periode dan per status', function () {
    $pengaju = f4dUser('pengaju', ['name' => 'Dr. Andi']);
    $kepalaLppm = f4dUser('kepala_lppm', ['name' => 'Kepala LPPM']);
    $skema = f4dSkema();
    $periode2025 = f4dPeriode('Periode 2025', '2025-01-01', '2025-12-31');
    $periode2026 = f4dPeriode('Periode 2026', '2026-01-01', '2026-12-31');

    f4dProposal($pengaju, $skema, ['judul' => 'Proposal draft', 'status' => 'Draft', 'periode_id' => $periode2025->id]);
    f4dProposal($pengaju, $skema, ['judul' => 'Proposal dikirim satu', 'status' => 'Dikirim', 'periode_id' => $periode2025->id]);
    f4dProposal($pengaju, $skema, ['judul' => 'Proposal dikirim dua', 'status' => 'Dikirim', 'periode_id' => $periode2025->id]);
    f4dProposal($pengaju, $skema, ['judul' => 'Proposal lolos', 'status' => 'Lolos Administrasi', 'periode_id' => $periode2026->id]);
    f4dProposal($pengaju, $skema, ['judul' => 'Proposal disetujui', 'status' => 'Disetujui', 'periode_id' => $periode2026->id]);
    f4dProposal($pengaju, $skema, ['judul' => 'Proposal tanpa periode', 'status' => 'Ditolak', 'periode_id' => null]);

    $response = $this->actingAs($kepalaLppm)->get(route('dashboard.kepala-lppm'));

    $response->assertOk();
    $response->assertSee('Analitik per Periode');
    $response->assertSee('Periode 2025');
    $response->assertSee('Periode 2026');
    $response->assertSee('Tanpa periode');
    $response->assertSee('Lolos Administrasi');
    $response->assertSee('Review Selesai');

    // Baris Periode 2025: total 3 (1 Draft, 2 Dikirim, sisanya 0).
    $response->assertSeeInOrder(['Periode 2025', '3', '1', '2', '0', '0', '0', '0', '0', '0', '0', '0']);
    // Baris Periode 2026: total 2 (1 Lolos Administrasi, 1 Disetujui, sisanya 0).
    $response->assertSeeInOrder(['Periode 2026', '2', '0', '0', '0', '1', '0', '0', '0', '1', '0', '0']);
    // Baris Tanpa periode: total 1 (1 Ditolak, sisanya 0).
    $response->assertSeeInOrder(['Tanpa periode', '1', '0', '0', '0', '0', '0', '0', '0', '0', '1', '0']);
});

test('dasbor kepala LPPM menampilkan state kosong bila belum ada data analitik', function () {
    $kepalaLppm = f4dUser('kepala_lppm', ['name' => 'Kepala LPPM']);

    $response = $this->actingAs($kepalaLppm)->get(route('dashboard.kepala-lppm'));

    $response->assertOk();
    $response->assertSee('Analitik per Periode');
    $response->assertSee('Belum ada data analitik');
    $response->assertDontSee('Tanpa periode');
});

test('pengaju tidak bisa membuka dasbor kepala LPPM', function () {
    $pengaju = f4dUser('pengaju', ['name' => 'Dr. Andi']);

    $this->actingAs($pengaju)->get(route('dashboard.kepala-lppm'))->assertForbidden();
});
