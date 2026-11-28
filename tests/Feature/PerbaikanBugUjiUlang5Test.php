<?php

// Perbaikan bug uji manual web ke-5, 28 Nov 2026 (butir 1 + 6).
// Butir 1: keempat tabel daftar masih meluber horizontal di 1341px.
// Butir 6: /monev hanya punya tombol "Lihat", tanpa Preview/Unduh
// berkas RTL dan bukti perbaikan.
// Helper berprefix ub5* (jangan memakai helper dari file test lain —
// urutan load tidak dijamin). TIDAK ADA PHP di VM ini saat penulisan,
// jadi test ini BELUM dijalankan; jalankan dengan `php artisan test
// --filter=PerbaikanBugUjiUlang5Test` di mesin user.
// Catatan: lebar render (px) tidak bisa diuji dari sisi server —
// test di sini hanya mengunci struktur yang bisa diverifikasi
// server: class tabel padat + pembungkus scroll internal, badge
// status yang boleh melipat, dan tombol berkas monev.

use App\Models\Fakultas;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function ub5User(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function ub5Skema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 15000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function ub5Fakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function ub5Proposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji tabel',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => ub5Fakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

function ub5ProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    return ub5Proposal($user, $skema, array_merge([
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ], $attrs));
}

function ub5Temuan(Proposal $proposal, array $attrs = []): MonevFinding
{
    return MonevFinding::create(array_merge([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan uji tombol berkas.',
        'rekomendasi' => 'Segera perbaiki.',
        'status_temuan' => 'Open',
    ], $attrs));
}

function ub5TemuanDenganBerkas(Proposal $proposal, User $pengaju): MonevFinding
{
    $finding = ub5Temuan($proposal);

    test()->actingAs($pengaju)->post(route('monev.kirimRtl', $finding), [
        'file_rtl' => UploadedFile::fake()->create('rtl.pdf', 100, 'application/pdf'),
        'bukti_perbaikan' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ]);

    return $finding->fresh();
}

// --- Butir 1: struktur tabel (server-side) ---

test('ub5: keempat halaman daftar memakai tabel padat di dalam pembungkus scroll internal', function () {
    $pengaju = ub5User('pengaju');
    $skema = ub5Skema();
    ub5Proposal($pengaju, $skema);
    ub5ProposalDisetujui($pengaju, $skema, ['judul' => 'Proposal disetujui uji']);

    // /proposal
    $this->actingAs($pengaju)->get(route('proposal.index'))
        ->assertOk()
        ->assertSee('table-responsive', false)
        ->assertSee('table-compact', false)
        ->assertSee('table-padat', false);

    // /proposal-disetujui (admin melihat semua)
    $this->actingAs(ub5User('admin'))->get(route('monitoring.proposalDisetujui'))
        ->assertOk()
        ->assertSee('table-responsive', false)
        ->assertSee('table-compact', false)
        ->assertSee('table-padat', false);

    // /monev (lppm melihat semua temuan)
    ub5Temuan(ub5ProposalDisetujui($pengaju, $skema, ['judul' => 'Proposal monev uji']));
    $this->actingAs(ub5User('lppm'))->get(route('monev.index'))
        ->assertOk()
        ->assertSee('table-responsive', false)
        ->assertSee('table-compact', false)
        ->assertSee('table-padat', false);

    // /cek-kuota-dosen
    $this->actingAs($pengaju)->get(route('dosen.cek-kuota'))
        ->assertOk()
        ->assertSee('table-responsive', false)
        ->assertSee('table-compact', false)
        ->assertSee('table-padat', false);
});

test('ub5: badge status di daftar proposal boleh melipat (tanpa text-nowrap)', function () {
    $pengaju = ub5User('pengaju');
    $skema = ub5Skema();
    ub5Proposal($pengaju, $skema, ['status' => 'Sedang Direview']);

    // Dulu badge memakai class text-nowrap (!important Bootstrap)
    // sehingga "SEDANG DIREVIEW" memaksa kolom Status ~170px dan
    // mendorong tabel keluar layar. Kini class itu dihapus.
    $this->actingAs($pengaju)->get(route('proposal.index'))
        ->assertOk()
        ->assertSee('SEDANG DIREVIEW')
        ->assertDontSee('rounded-pill shadow-sm text-nowrap', false);
});

// --- Butir 6: tombol Preview + Unduh berkas monev di /monev ---

test('ub5: daftar monev menampilkan Preview dan Unduh RTL/Bukti bila berkas ada', function () {
    Storage::fake('public');
    $pengaju = ub5User('pengaju');
    $skema = ub5Skema();
    $proposal = ub5ProposalDisetujui($pengaju, $skema);
    $finding = ub5TemuanDenganBerkas($proposal, $pengaju);

    expect($finding->file_rtl)->not->toBeNull();
    expect($finding->bukti_perbaikan)->not->toBeNull();

    $this->actingAs(ub5User('lppm'))->get(route('monev.index'))
        ->assertOk()
        ->assertSee('Preview RTL')
        ->assertSee('Unduh RTL')
        ->assertSee('Preview Bukti')
        ->assertSee('Unduh Bukti')
        ->assertSee(route('monev.preview', [$finding, 'rtl']), false)
        ->assertSee(route('monev.download', [$finding, 'rtl']), false)
        ->assertSee(route('monev.preview', [$finding, 'bukti']), false)
        ->assertSee(route('monev.download', [$finding, 'bukti']), false);
});

test('ub5: daftar monev tidak menampilkan tombol berkas bila berkas belum ada', function () {
    $pengaju = ub5User('pengaju');
    $skema = ub5Skema();
    $proposal = ub5ProposalDisetujui($pengaju, $skema);
    $finding = ub5Temuan($proposal);

    $this->actingAs(ub5User('lppm'))->get(route('monev.index'))
        ->assertOk()
        ->assertSee('Lihat')
        ->assertDontSee('Preview RTL')
        ->assertDontSee('Unduh RTL')
        ->assertDontSee('Preview Bukti')
        ->assertDontSee('Unduh Bukti')
        ->assertDontSee(route('monev.preview', [$finding, 'rtl']), false)
        ->assertDontSee(route('monev.download', [$finding, 'rtl']), false);
});

test('ub5: pengaju pemilik melihat tombol berkas di daftar monev; pengaju lain tidak melihat temuannya', function () {
    Storage::fake('public');
    $pengaju = ub5User('pengaju');
    $pengajuLain = ub5User('pengaju');
    $skema = ub5Skema();
    $proposal = ub5ProposalDisetujui($pengaju, $skema);
    $finding = ub5TemuanDenganBerkas($proposal, $pengaju);

    $this->actingAs($pengaju)->get(route('monev.index'))
        ->assertOk()
        ->assertSee('Temuan uji tombol berkas.')
        ->assertSee('Preview RTL')
        ->assertSee(route('monev.preview', [$finding, 'rtl']), false);

    $this->actingAs($pengajuLain)->get(route('monev.index'))
        ->assertOk()
        ->assertDontSee('Temuan uji tombol berkas.');
});

test('ub5: preview berkas monev memakai otorisasi yang sama dengan Lihat (pengaju lain 403)', function () {
    Storage::fake('public');
    $pengaju = ub5User('pengaju');
    $pengajuLain = ub5User('pengaju');
    $skema = ub5Skema();
    $proposal = ub5ProposalDisetujui($pengaju, $skema);
    $finding = ub5TemuanDenganBerkas($proposal, $pengaju);

    // Pemilik dan LPPM boleh preview; pengaju lain tidak (sama seperti "Lihat").
    $this->actingAs($pengaju)->get(route('monev.preview', [$finding, 'rtl']))->assertOk();
    $this->actingAs($pengaju)->get(route('monev.preview', [$finding, 'bukti']))->assertOk();
    $this->actingAs(ub5User('lppm'))->get(route('monev.preview', [$finding, 'rtl']))->assertOk();
    $this->actingAs($pengajuLain)->get(route('monev.preview', [$finding, 'rtl']))->assertForbidden();
    $this->actingAs($pengajuLain)->get(route('monev.preview', [$finding, 'bukti']))->assertForbidden();
});
