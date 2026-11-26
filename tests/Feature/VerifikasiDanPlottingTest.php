<?php

use App\Http\Controllers\ProposalReviewerController;
use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f3User(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f3Skema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f3ProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
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

test('verifikator meloloskan proposal: status jadi Lolos Administrasi + notifikasi ke pengaju', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = f3User('verifikator', ['name' => 'Veri']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), [
            'hasil' => 'Lolos',
            'catatan' => 'Berkas lengkap.',
        ]);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');

    $this->assertDatabaseHas('verifications', [
        'proposal_id' => $proposal->id,
        'verifikator_id' => $verifikator->id,
        'hasil' => 'Lolos',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $pengaju->id,
        'proposal_id' => $proposal->id,
        'title' => 'Verifikasi Lolos Administrasi',
    ]);
});

test('verifikator mengembalikan proposal dengan alasan: status jadi Revisi Administrasi', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = f3User('verifikator', ['name' => 'Veri']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), [
            'hasil' => 'Revisi',
            'catatan' => 'File RAB belum dilampirkan.',
        ]);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Revisi Administrasi');

    $this->assertDatabaseHas('verifications', [
        'proposal_id' => $proposal->id,
        'hasil' => 'Revisi',
        'catatan' => 'File RAB belum dilampirkan.',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $pengaju->id,
        'proposal_id' => $proposal->id,
        'title' => 'Perlu Revisi Administrasi',
    ]);
});

test('pengembalian tanpa alasan ditolak validasi', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = f3User('verifikator', ['name' => 'Veri']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Revisi']);

    $response->assertSessionHasErrors('catatan');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    $this->assertDatabaseCount('verifications', 0);
});

test('pengaju tidak bisa memverifikasi proposal', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema);

    $this->actingAs($pengaju)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Lolos'])
        ->assertForbidden();
});

test('proposal yang sudah diverifikasi tidak bisa diverifikasi lagi', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = f3User('verifikator', ['name' => 'Veri']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema);

    $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Lolos']);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Revisi', 'catatan' => 'Terlambat.']);

    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('verifications', 1);
});

test('pengaju bisa kirim ulang proposal Revisi Administrasi tanpa makan kuota baru', function () {
    Storage::fake('public');
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $skema = f3Skema(['kuota' => 1]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $proposal = f3ProposalDikirim($pengaju, $skema, [
        'judul' => 'Proposal revisi admin',
        'status' => 'Revisi Administrasi',
        'file_path' => 'proposal_files/lama.pdf',
    ]);

    $response = $this->actingAs($pengaju)->put(route('proposal.update', $proposal->id), [
        'aksi' => 'submit',
        'judul' => 'Proposal revisi admin',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ]);

    $response->assertRedirect(route('monitoring.proposalDikirim'));
    expect($proposal->fresh()->status)->toBe('Dikirim');
});

test('halaman plotting LPPM menampilkan proposal lolos administrasi', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    $skema = f3Skema();
    f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal lolos', 'status' => 'Lolos Administrasi']);
    $dikirim = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal masih dikirim', 'status' => 'Dikirim']);

    $response = $this->actingAs($lppm)->get(route('lppm.plotting'));

    $response->assertOk();
    $response->assertSee('Proposal lolos');
    // Judul proposal hanya dirender ke script kalender layout pada halaman
    // kalender (monitoring.kalender), jadi di halaman plotting judul proposal
    // yang masih Dikirim tidak boleh muncul sama sekali.
    $response->assertDontSee('Proposal masih dikirim');
    $response->assertDontSee(route('proposal.assignReviewer', $dikirim->id));

    // Bukan LPPM tidak boleh membuka plotting.
    $this->actingAs($pengaju)->get(route('lppm.plotting'))->assertForbidden();
});

test('matching reviewer mengurutkan bidang cocok dulu dan mengeluarkan pengusul serta anggota', function () {
    $fakultasTI = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $fakultasKedokteran = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi', 'bidang_keahlian' => 'Sistem Informasi', 'fakultas_id' => $fakultasTI->id]);
    $anggota = f3User('pengaju', ['name' => 'Budi Santoso']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal matching', 'status' => 'Lolos Administrasi']);

    ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Diterima',
    ]);

    $cocok = f3User('reviewer', ['name' => 'Sinta', 'bidang_keahlian' => 'Sistem Informasi', 'fakultas_id' => $fakultasTI->id]);
    $tidakCocok = f3User('reviewer', ['name' => 'Dewi', 'bidang_keahlian' => 'Kedokteran', 'fakultas_id' => $fakultasKedokteran->id]);
    f3User('reviewer', ['name' => 'Agus']);

    $kandidat = (new ProposalReviewerController)->candidatesFor($proposal->fresh());

    expect($kandidat)->toHaveCount(3);
    expect($kandidat->first()['user']->id)->toBe($cocok->id)
        ->and($kandidat->first()['skor'])->toBe(3)
        ->and($kandidat->first()['alasan'])->toContain('Bidang cocok', 'Fakultas sama');
    expect($kandidat->pluck('user.id')->all())->not->toContain($pengaju->id, $anggota->id);
    expect($kandidat->firstWhere('user.id', $tidakCocok->id)['skor'])->toBe(0);
});

test('LPPM menugaskan 2 reviewer: status jadi Sedang Direview + reviewer diberi notifikasi', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    $reviewer1 = f3User('reviewer', ['name' => 'Sinta']);
    $reviewer2 = f3User('reviewer', ['name' => 'Dewi']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal plotting', 'status' => 'Lolos Administrasi']);

    $response = $this->actingAs($lppm)
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $reviewer1->id,
            'reviewer_2' => $reviewer2->id,
            'review_deadline' => now()->addWeek()->format('Y-m-d\\TH:i'),
        ]);

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Sedang Direview');

    $this->assertDatabaseHas('proposal_reviewers', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer1->id,
    ]);
    $this->assertDatabaseHas('proposal_reviewers', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer2->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $reviewer1->id,
        'proposal_id' => $proposal->id,
        'title' => 'Tugas Review Proposal',
    ]);
});

test('anggota tim tidak bisa ditugaskan sebagai reviewer proposalnya sendiri', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    $anggota = f3User('pengaju', ['name' => 'Budi Santoso']);
    $reviewer = f3User('reviewer', ['name' => 'Sinta']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal plotting', 'status' => 'Lolos Administrasi']);

    ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Diterima',
    ]);

    $response = $this->actingAs($lppm)
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $anggota->id,
            'reviewer_2' => $reviewer->id,
            'review_deadline' => now()->addWeek()->format('Y-m-d\\TH:i'),
        ]);

    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

test('dasbor LPPM menampilkan progres review 1 dari 2', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    $reviewer1 = f3User('reviewer', ['name' => 'Sinta']);
    $reviewer2 = f3User('reviewer', ['name' => 'Dewi']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal pantau', 'status' => 'Sedang Direview']);

    $proposal->reviewers()->sync([$reviewer1->id, $reviewer2->id]);

    Review::create([
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer1->id,
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

    $response = $this->actingAs($lppm)->get(route('dashboard.lppm'));

    $response->assertOk();
    $response->assertSee('Proposal pantau');
    $response->assertSee('1 dari 2');
    $response->assertSee('50%');
});

test('penugasan reviewer yang sama ditolak dan pesannya tampil di halaman plotting', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    $reviewer = f3User('reviewer', ['name' => 'Sinta']);
    $skema = f3Skema();
    $proposal = f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal plotting', 'status' => 'Lolos Administrasi']);

    // Sebelumnya penolakan ini tidak terlihat di halaman plotting (tidak ada
    // pesan error), sehingga penugasan tampak gagal diam-diam.
    $response = $this->actingAs($lppm)
        ->from(route('lppm.plotting'))
        ->followingRedirects()
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $reviewer->id,
            'reviewer_2' => $reviewer->id,
            'review_deadline' => now()->addWeek()->format('Y-m-d\\TH:i'),
        ]);

    $response->assertOk();
    $response->assertSee('Reviewer 1 dan 2 tidak boleh orang yang sama.');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

test('halaman plotting memperingatkan bila kandidat reviewer kurang dari 2', function () {
    $pengaju = f3User('pengaju', ['name' => 'Dr. Andi']);
    $lppm = f3User('lppm', ['name' => 'Lppm']);
    f3User('reviewer', ['name' => 'Sinta']);
    $skema = f3Skema();
    f3ProposalDikirim($pengaju, $skema, ['judul' => 'Proposal plotting', 'status' => 'Lolos Administrasi']);

    $response = $this->actingAs($lppm)->get(route('lppm.plotting'));

    $response->assertOk();
    $response->assertSee('Kandidat reviewer hanya 1 orang');
    $response->assertSee('penugasan butuh 2 reviewer berbeda');
});
