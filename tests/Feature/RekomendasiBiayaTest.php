<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f4bUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f4bSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f4bProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Sedang Direview',
        'biaya' => 1000000,
    ], $attrs));
}

function f4bPayloadReview(array $attrs = []): array
{
    return array_merge([
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
        'catatan' => 'Bagus.',
    ], $attrs);
}

test('reviewer yang ditugaskan submit review dengan rekomendasi biaya: tersimpan dan tampil di hasil review', function () {
    $pengaju = f4bUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = f4bUser('reviewer', ['name' => 'Sinta']);
    $admin = f4bUser('admin', ['name' => 'Admin']);
    $skema = f4bSkema();
    $proposal = f4bProposalDikirim($pengaju, $skema);

    $proposal->reviewers()->sync([$reviewer->id]);

    $response = $this->actingAs($reviewer)
        ->post(route('review.simpan', $proposal->id), f4bPayloadReview([
            'rekomendasi_biaya' => 4500000,
        ]));

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
        'rekomendasi_biaya' => 4500000,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.hasil-review'));

    $response->assertOk();
    $response->assertSee('Rp 4.500.000');
});

test('submit review tanpa rekomendasi biaya tetap berhasil dan nilainya null', function () {
    $pengaju = f4bUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = f4bUser('reviewer', ['name' => 'Sinta']);
    $skema = f4bSkema();
    $proposal = f4bProposalDikirim($pengaju, $skema);

    $proposal->reviewers()->sync([$reviewer->id]);

    $response = $this->actingAs($reviewer)
        ->post(route('review.simpan', $proposal->id), f4bPayloadReview());

    $response->assertSessionHas('success');

    $this->assertDatabaseHas('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
        'rekomendasi_biaya' => null,
    ]);
});
