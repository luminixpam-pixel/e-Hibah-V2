<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function atUser(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'pengaju'], $attrs));
}

function atSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function atProposal(User $user, Skema $skema, array $attrs = []): Proposal
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

function atUndangAnggota(Proposal $proposal, User $anggota, string $status = 'Diterima'): ProposalMember
{
    return ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => $status,
    ]);
}

// --- Dashboard: bagian "Proposal yang Saya Ikuti" ---

test('anggota yang diterima melihat proposal yang diikuti di dashboard', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($anggota)->get(route('dashboard.pengaju'));

    $response->assertOk();
    $response->assertSee('Proposal yang Saya Ikuti');
    $response->assertSee('Proposal yang Diikuti Budi');
    $response->assertSee('Diterima');
});

test('anggota yang masih menunggu juga melihat proposal yang diikuti di dashboard', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Menunggu');

    $response = $this->actingAs($anggota)->get(route('dashboard.pengaju'));

    $response->assertOk();
    $response->assertSee('Proposal yang Diikuti Budi');
    $response->assertSee('Menunggu konfirmasi');
    $response->assertSee('Jawab Undangan');
});

test('pengaju lain tidak melihat proposal itu di bagian yang diikuti', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $orangLain = atUser(['name' => 'Citra Lestari']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($orangLain)->get(route('dashboard.pengaju'));

    $response->assertOk();
    $response->assertSee('Proposal yang Saya Ikuti');
    $response->assertDontSee('Proposal yang Diikuti Budi');
});

test('anggota yang menolak tidak tampil di bagian yang diikuti', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Ditolak');

    $response = $this->actingAs($anggota)->get(route('dashboard.pengaju'));

    $response->assertOk();
    $response->assertSee('Proposal yang Saya Ikuti');
    $response->assertDontSee('Proposal yang Diikuti Budi');
});

// --- Detail proposal yang diikuti ---

test('anggota bisa membuka detail proposal yang diikuti', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($anggota)->get(route('anggota.proposal.show', $proposal->id));

    $response->assertOk();
    $response->assertSee('Detail Proposal');
    $response->assertSee('Proposal yang Diikuti Budi');
    $response->assertSee('Dr. Andi');
    // Anggota tidak melihat formulir verifikasi (khusus verifikator).
    $response->assertDontSee('Verifikasi Administrasi');
});

test('anggota yang masih menunggu bisa membuka detail sebelum menjawab undangan', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Menunggu');

    $response = $this->actingAs($anggota)->get(route('anggota.proposal.show', $proposal->id));

    $response->assertOk();
    $response->assertSee('Proposal yang Diikuti Budi');
});

test('pengaju yang bukan anggota tidak bisa membuka detail proposal', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $orangLain = atUser(['name' => 'Citra Lestari']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Diterima');

    $this->actingAs($orangLain)->get(route('anggota.proposal.show', $proposal->id))->assertForbidden();
});

test('anggota yang menolak tidak bisa membuka detail proposal', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Ditolak');

    $this->actingAs($anggota)->get(route('anggota.proposal.show', $proposal->id))->assertForbidden();
});

test('ketua pengusul tetap bisa membuka detail lewat jalur anggota', function () {
    $ketua = atUser(['name' => 'Dr. Andi']);
    $anggota = atUser(['name' => 'Budi Santoso']);
    $skema = atSkema();
    $proposal = atProposal($ketua, $skema, ['judul' => 'Proposal yang Diikuti Budi']);
    atUndangAnggota($proposal, $anggota, 'Diterima');

    $response = $this->actingAs($ketua)->get(route('anggota.proposal.show', $proposal->id));

    $response->assertOk();
    $response->assertSee('Proposal yang Diikuti Budi');
});
