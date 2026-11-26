<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f6dBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6dBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f6dBuatProposal(User $pengaju, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $pengaju->id,
        'judul' => 'Proposal untuk direview',
        'nama_ketua' => $pengaju->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Sedang Direview',
        'biaya' => 1000000,
    ], $attrs));
}

function f6dPayloadReview(array $attrs = []): array
{
    return array_merge([
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
    ], $attrs);
}

test('reviewer yang tidak ditugaskan submit review: ditolak 403 dan review tidak tersimpan', function () {
    $pengaju = f6dBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewerDitugaskan = f6dBuatUser('reviewer', ['name' => 'Sinta']);
    $reviewerLain = f6dBuatUser('reviewer', ['name' => 'Budi']);
    $skema = f6dBuatSkema();
    $proposal = f6dBuatProposal($pengaju, $skema);

    $proposal->reviewers()->sync([$reviewerDitugaskan->id]);

    $response = $this->actingAs($reviewerLain)
        ->post(route('review.simpan', $proposal->id), f6dPayloadReview());

    $response->assertForbidden();
    $this->assertDatabaseMissing('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewerLain->id,
    ]);
    expect($proposal->fresh()->status)->toBe('Sedang Direview');
});

test('reviewer yang ditugaskan tetap bisa submit review', function () {
    $pengaju = f6dBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = f6dBuatUser('reviewer', ['name' => 'Sinta']);
    $skema = f6dBuatSkema();
    $proposal = f6dBuatProposal($pengaju, $skema);

    $proposal->reviewers()->sync([$reviewer->id]);

    $response = $this->actingAs($reviewer)
        ->post(route('review.simpan', $proposal->id), f6dPayloadReview([
            'catatan' => 'Bagus.',
            'rekomendasi_biaya' => 4500000,
        ]));

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
        'catatan' => 'Bagus.',
        'rekomendasi_biaya' => 4500000,
    ]);
});

test('komentar dan keputusan review dari form tersimpan (komentar ke kolom catatan)', function () {
    $pengaju = f6dBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = f6dBuatUser('reviewer', ['name' => 'Sinta']);
    $skema = f6dBuatSkema();
    $proposal = f6dBuatProposal($pengaju, $skema);

    $proposal->reviewers()->sync([$reviewer->id]);

    $response = $this->actingAs($reviewer)
        ->post(route('review.simpan', $proposal->id), f6dPayloadReview([
            'komentar' => 'Perlu perbaikan metode.',
            'status' => 'disetujui',
        ]));

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
        'catatan' => 'Perlu perbaikan metode.',
        'status' => 'disetujui',
    ]);
});
