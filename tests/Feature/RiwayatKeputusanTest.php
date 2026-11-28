<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f4cUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f4cSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f4cProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
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

/**
 * Proposal yang sudah selesai direview oleh 2 reviewer — satu-satunya
 * keadaan sah untuk keputusan pendanaan (keputusan() menolak status
 * lain dan menolak bila belum semua reviewer mengirim review).
 */
function f4cProposalSiapDiputuskan(User $user, Skema $skema, array $attrs = []): Proposal
{
    $proposal = f4cProposalDikirim($user, $skema, array_merge(['status' => 'Review Selesai'], $attrs));

    $reviewer1 = f4cUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = f4cUser('reviewer', ['name' => 'Dewi']);
    $proposal->reviewers()->sync([$reviewer1->id, $reviewer2->id]);

    foreach ([$reviewer1, $reviewer2] as $reviewer) {
        Review::create([
            'proposal_id' => $proposal->id,
            'reviewer_id' => $reviewer->id,
            'nilai_1' => 4,
            'nilai_2' => 4,
            'nilai_3' => 4,
            'nilai_4' => 4,
            'nilai_5' => 4,
            'nilai_6' => 4,
            'nilai_7' => 4,
            'total_score' => 400,
            'catatan' => 'Bagus.',
        ]);
    }

    return $proposal;
}

test('keputusan yang dicatat admin tampil di halaman tinjau proposal', function () {
    $pengaju = f4cUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = f4cUser('admin', ['name' => 'Admin Utama']);
    $skema = f4cSkema();
    $proposal = f4cProposalSiapDiputuskan($pengaju, $skema, ['judul' => 'Proposal diputuskan']);

    $response = $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status_pendanaan)->toBe('Disetujui');

    $response = $this->actingAs($admin)->get(route('proposal.tinjau', $proposal->id));

    $response->assertOk();
    $response->assertSee('Riwayat Keputusan');
    $response->assertSee('Admin mengubah status proposal ID '.$proposal->id.' menjadi: Disetujui');
    $response->assertSee('Admin Utama');
});

test('keputusan proposal lain tidak muncul di halaman tinjau proposal pertama', function () {
    $pengaju = f4cUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = f4cUser('admin', ['name' => 'Admin Utama']);
    $skema = f4cSkema();
    $pertama = f4cProposalSiapDiputuskan($pengaju, $skema, ['judul' => 'Proposal pertama']);
    $kedua = f4cProposalSiapDiputuskan($pengaju, $skema, ['judul' => 'Proposal kedua']);

    $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $pertama->id), ['status_pendanaan' => 'Disetujui']);
    $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $kedua->id), ['status_pendanaan' => 'Ditolak']);

    $response = $this->actingAs($admin)->get(route('proposal.tinjau', $pertama->id));

    $response->assertOk();
    $response->assertSee('Admin mengubah status proposal ID '.$pertama->id.' menjadi: Disetujui');
    $response->assertDontSee('Admin mengubah status proposal ID '.$kedua->id.' menjadi: Ditolak');
});

test('proposal tanpa keputusan menampilkan state kosong di halaman tinjau', function () {
    $pengaju = f4cUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = f4cUser('admin', ['name' => 'Admin Utama']);
    $skema = f4cSkema();
    $proposal = f4cProposalDikirim($pengaju, $skema, ['judul' => 'Proposal belum diputuskan']);

    $response = $this->actingAs($admin)->get(route('proposal.tinjau', $proposal->id));

    $response->assertOk();
    $response->assertSee('Riwayat Keputusan');
    $response->assertSee('Belum ada keputusan pendanaan untuk proposal ini.');
});
