<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f4fUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f4fSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f4fProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

test('pengaju tidak boleh memutuskan proposal (403) dan status tidak berubah', function () {
    $pengaju = f4fUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f4fSkema();
    $proposal = f4fProposalDikirim($pengaju, $skema, ['judul' => 'Proposal pengaju']);

    $response = $this->actingAs($pengaju)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);

    $response->assertForbidden();
    expect($proposal->fresh()->status)->toBe('Dikirim');
    // status_pendanaan tidak berubah (tetap default kolom 'Proses',
    // bukan null — kolom ini NOT NULL).
    expect($proposal->fresh()->status_pendanaan)->toBe('Proses');
});

test('kepala lppm tidak boleh memutuskan proposal lewat route ini (403)', function () {
    $pengaju = f4fUser('pengaju', ['name' => 'Dr. Andi']);
    $kepala = f4fUser('kepala_lppm', ['name' => 'Kepala LPPM']);
    $skema = f4fSkema();
    $proposal = f4fProposalDikirim($pengaju, $skema, ['judul' => 'Proposal kepala']);

    $response = $this->actingAs($kepala)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);

    $response->assertForbidden();
    expect($proposal->fresh()->status)->toBe('Dikirim');
});
