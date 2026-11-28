<?php

// Perpanjang tenggat review proposal yang Sedang Direview, 28 Nov 2026.
// Tanpa endpoint ini, proposal yang review_deadline-nya lewat mentok di
// "Sedang Direview" karena ReviewerController menolak isi-review/submit,
// dan tidak ada jalur berfungsi untuk memperpanjangnya (assignReviewer
// hanya menerima "Lolos Administrasi", proposal.update hanya Draft /
// Revisi Administrasi). Helper berprefix pt* agar tidak bentrok dengan
// test lain.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ptUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function ptSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function ptFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function ptPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function ptProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji perpanjang tenggat',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => ptFakultas()->id,
        'skema_id' => $skema->id,
        'periode_id' => ptPeriodeAktif()->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
        'file_path' => 'proposal_files/uji.pdf',
    ], $attrs));
}

/**
 * Proposal "Sedang Direview" dengan 2 reviewer + tenggat.
 * Mengembalikan [$proposal, $reviewer1, $reviewer2].
 */
function ptProposalDireview(User $pengaju, Skema $skema, array $attrs = []): array
{
    $reviewer1 = ptUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = ptUser('reviewer', ['name' => 'Dewi']);
    $proposal = ptProposal($pengaju, $skema, array_merge([
        'status' => 'Sedang Direview',
        'review_deadline' => now()->addWeek(),
    ], $attrs));
    $proposal->reviewers()->sync([$reviewer1->id, $reviewer2->id]);

    return [$proposal, $reviewer1, $reviewer2];
}

function ptPayloadReview(array $override = []): array
{
    return array_merge([
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
    ], $override);
}

// --- Perpanjang berhasil ---

test('pt: admin bisa memperpanjang tenggat review', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal, $reviewer1, $reviewer2] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline;
    $tenggatBaru = now()->addWeeks(2)->format('Y-m-d\TH:i');

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), ['review_deadline' => $tenggatBaru]);

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');

    $proposal = $proposal->fresh();
    expect($proposal->review_deadline->greaterThan($tenggatLama))->toBeTrue()
        ->and($proposal->status)->toBe('Sedang Direview')
        ->and($proposal->reviewers->pluck('id')->sort()->values()->all())->toBe(
            collect([$reviewer1->id, $reviewer2->id])->sort()->values()->all()
        );

    // Notifikasi terkirim ke kedua reviewer yang ditugaskan.
    $this->assertDatabaseHas('notifications', [
        'user_id' => $reviewer1->id,
        'proposal_id' => $proposal->id,
        'title' => 'Tenggat Review Diperpanjang',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $reviewer2->id,
        'proposal_id' => $proposal->id,
        'title' => 'Tenggat Review Diperpanjang',
    ]);
});

test('pt: lppm bisa memperpanjang tenggat review', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $lppm = ptUser('lppm', ['name' => 'Staf LPPM']);
    [$proposal, $reviewer1, $reviewer2] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline;

    $response = $this->actingAs($lppm)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]);

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    expect($proposal->fresh()->review_deadline->greaterThan($tenggatLama))->toBeTrue();
    $this->assertDatabaseHas('notifications', [
        'user_id' => $reviewer1->id,
        'title' => 'Tenggat Review Diperpanjang',
    ]);
});

// --- Validasi tenggat baru ---

test('pt: tenggat baru di masa lalu ditolak, tenggat tidak berubah', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->subDay()->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHasErrors('review_deadline');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

test('pt: tenggat baru yang lebih awal dari tenggat saat ini ditolak', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addDays(3)->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHas('error', 'Tenggat baru harus lebih lambat dari tenggat saat ini.');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

test('pt: tenggat baru yang sama dengan tenggat saat ini ditolak', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => $tenggatLama->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHas('error', 'Tenggat baru harus lebih lambat dari tenggat saat ini.');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

// --- Otorisasi peran ---

test('pt: pengaju tidak bisa memperpanjang tenggat (403)', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    [$proposal] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline->copy();

    $this->actingAs($ketua)
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
});

test('pt: reviewer tidak bisa memperpanjang tenggat (403)', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    [$proposal, $reviewer1] = ptProposalDireview($ketua, ptSkema());
    $tenggatLama = $proposal->review_deadline->copy();

    $this->actingAs($reviewer1)
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ])
        ->assertForbidden();
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
});

// --- Penjaga status ---

test('pt: perpanjangan pada proposal Draft ditolak, tenggat tidak berubah', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    $proposal = ptProposal($ketua, ptSkema(), [
        'status' => 'Draft',
        'review_deadline' => now()->addWeek(),
    ]);
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHas('error');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

test('pt: perpanjangan pada proposal Lolos Administrasi ditolak dan diarahkan ke plotting ulang', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    $proposal = ptProposal($ketua, ptSkema(), [
        'status' => 'Lolos Administrasi',
        'review_deadline' => now()->addWeek(),
    ]);
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHas('error', 'Proposal yang Lolos Administrasi belum ditugaskan reviewer — gunakan plotting ulang (assignReviewer) untuk menetapkan tenggat.');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

test('pt: perpanjangan pada proposal Review Selesai ditolak, tenggat tidak berubah', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    $proposal = ptProposal($ketua, ptSkema(), [
        'status' => 'Review Selesai',
        'review_deadline' => now()->addWeek(),
    ]);
    $tenggatLama = $proposal->review_deadline->copy();

    $response = $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ]);

    $response->assertSessionHas('error');
    expect($proposal->fresh()->review_deadline->equalTo($tenggatLama))->toBeTrue();
    $this->assertDatabaseCount('notifications', 0);
});

// --- Efek perpanjangan pada alur review ---

test('pt: reviewer yang tenggatnya sudah lewat bisa isi-review dan submit setelah diperpanjang', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal, $reviewer1] = ptProposalDireview($ketua, ptSkema(), [
        'review_deadline' => now()->subDay(),
    ]);

    // Sebelum diperpanjang: form dan submit sama-sama ditolak penjaga tenggat.
    $this->actingAs($reviewer1)
        ->from(route('proposal.index'))
        ->get(route('reviewer.isi-review', $proposal->id))
        ->assertRedirect(route('proposal.index'))
        ->assertSessionHas('error', 'Tenggat penilaian proposal ini sudah lewat.');

    $this->actingAs($reviewer1)
        ->from(route('reviewer.isi-review', $proposal->id))
        ->post(route('review.simpan', $proposal->id), ptPayloadReview())
        ->assertSessionHas('error', 'Tenggat penilaian proposal ini sudah lewat.');
    $this->assertDatabaseCount('reviews', 0);

    // Admin memperpanjang tenggat.
    $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeek()->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHas('success');

    // Sesudah diperpanjang: penjaga tenggat memakai tenggat baru.
    $this->actingAs($reviewer1)
        ->get(route('reviewer.isi-review', $proposal->id))
        ->assertOk();

    $this->actingAs($reviewer1)
        ->from(route('reviewer.isi-review', $proposal->id))
        ->post(route('review.simpan', $proposal->id), ptPayloadReview())
        ->assertRedirect(route('proposal.index'))
        ->assertSessionHas('success');
    $this->assertDatabaseCount('reviews', 1);
});

test('pt: review yang sudah disubmit reviewer pertama tetap ada setelah perpanjangan', function () {
    $ketua = ptUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = ptUser('admin', ['name' => 'Admin']);
    [$proposal, $reviewer1, $reviewer2] = ptProposalDireview($ketua, ptSkema());

    // Reviewer pertama sudah submit sebelum perpanjangan.
    $this->actingAs($reviewer1)
        ->post(route('review.simpan', $proposal->id), ptPayloadReview())
        ->assertSessionHas('success');
    $this->assertDatabaseCount('reviews', 1);

    // Admin memperpanjang tenggat.
    $this->actingAs($admin)
        ->from(route('proposal.index'))
        ->post(route('proposal.perpanjangTenggat', $proposal->id), [
            'review_deadline' => now()->addWeeks(2)->format('Y-m-d\TH:i'),
        ])
        ->assertSessionHas('success');

    $proposal = $proposal->fresh();
    expect($proposal->status)->toBe('Sedang Direview')
        ->and($proposal->reviewers->pluck('id')->sort()->values()->all())->toBe(
            collect([$reviewer1->id, $reviewer2->id])->sort()->values()->all()
        );

    // Review pertama tidak ter-reset.
    $this->assertDatabaseHas('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer1->id,
    ]);
    $this->assertDatabaseCount('reviews', 1);
});
