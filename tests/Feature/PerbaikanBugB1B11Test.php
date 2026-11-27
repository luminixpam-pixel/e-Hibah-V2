<?php

// Perbaikan bug uji ulang web 27 Nov 2026 (B1–B11, Bagian A).
// Helper berprefix bb* agar tidak bentrok dengan pb* / f3* / at*.

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bbUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function bbSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function bbProposal(User $user, Skema $skema, array $attrs = []): Proposal
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

// --- B1: kirim draft dari daftar ---

test('bb1: kirim draft dari daftar berhasil dan pesan sukses terlihat', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, [
        'judul' => 'Proposal draft siap kirim',
        'status' => 'Draft',
        'file_path' => 'proposal_files/draft.pdf',
    ]);

    $response = $this->actingAs($pengaju)
        ->followingRedirects()
        ->post(route('proposal.kirim', $proposal->id));

    $response->assertOk();
    $response->assertSee('Proposal berhasil diajukan!');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    expect($proposal->fresh()->no_pengajuan)->not->toBeNull();
});

// --- B2: plotting reviewer sama ---

test('bb2: penugasan reviewer yang sama menampilkan pesan error dan isian dipertahankan', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $lppm = bbUser('lppm', ['name' => 'Lppm']);
    $reviewer = bbUser('reviewer', ['name' => 'Sinta']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, ['judul' => 'Proposal plotting', 'status' => 'Lolos Administrasi']);
    $deadline = now()->addWeek()->format('Y-m-d\\TH:i');

    $response = $this->actingAs($lppm)
        ->from(route('lppm.plotting'))
        ->followingRedirects()
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $reviewer->id,
            'reviewer_2' => $reviewer->id,
            'review_deadline' => $deadline,
        ]);

    $response->assertOk();
    $response->assertSee('Reviewer 1 dan 2 tidak boleh orang yang sama.');

    // Isian dipertahankan (old()): kedua dropdown tetap menunjuk reviewer
    // yang dipilih (input hidden + item aktif) dan tenggat tetap terisi.
    // (Sejak commit cc3f7d58, <select> native diganti dropdown Bootstrap,
    // jadi tidak ada lagi atribut `selected` di markup.)
    $response->assertSee('name="reviewer_1" value="'.$reviewer->id.'"', false);
    $response->assertSee('name="reviewer_2" value="'.$reviewer->id.'"', false);
    $response->assertSee('plotting-reviewer-item active" data-value="'.$reviewer->id.'"', false);
    $response->assertSee('value="'.$deadline.'"', false);

    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

// --- B4: konsistensi angka biaya di tinjau ---

test('bb4: halaman tinjau menampilkan total RAB dan rekomendasi biaya reviewer secara terpisah', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = bbUser('reviewer', ['name' => 'Sinta']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, [
        'judul' => 'Proposal biaya',
        'status' => 'Sedang Direview',
        'biaya' => 1000000,
    ]);

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
        'rekomendasi_biaya' => 4500000,
    ]);

    $response = $this->actingAs($pengaju)->get(route('proposal.tinjau', $proposal->id));

    $response->assertOk();
    // Total RAB pengusul (1jt) berlabel jelas, bukan sekadar "Biaya:".
    $response->assertSee('Total RAB (Biaya Diajukan)');
    $response->assertSee('Rp 1.000.000');
    // Rekomendasi reviewer (4,5jt) tampil terpisah dengan label sendiri.
    $response->assertSee('Rekomendasi Biaya Reviewer');
    $response->assertSee('Rp 4.500.000');
});

// --- B5: proposal disetujui tampil untuk verifikator ---

test('bb5: proposal berstatus Disetujui tampil di halaman proposal disetujui untuk verifikator', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = bbUser('verifikator', ['name' => 'Verifikator']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, [
        'judul' => 'Proposal disetujui uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);

    $response = $this->actingAs($verifikator)->get(route('monitoring.proposalDisetujui'));

    $response->assertOk();
    $response->assertSee('Proposal disetujui uji');
    $response->assertDontSee('Belum ada proposal yang disetujui.');
});

// --- B6: flash keputusan pendanaan ---

test('bb6: keputusan pendanaan menampilkan pesan sukses di halaman proposal selesai', function () {
    $admin = bbUser('admin', ['name' => 'Admin']);
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, [
        'judul' => 'Proposal selesai uji',
        'status' => 'Review Selesai',
    ]);

    $response = $this->actingAs($admin)
        ->from(route('monitoring.reviewSelesai'))
        ->followingRedirects()
        ->patch(route('proposal.keputusan', $proposal->id), [
            'status_pendanaan' => 'Disetujui',
        ]);

    $response->assertOk();
    // Alert sukses inline (menetap), bukan hanya toast yang menutup sendiri.
    $response->assertSee('alert alert-success', false);
    $response->assertSee('Keputusan berhasil disimpan!');
    expect($proposal->fresh()->status)->toBe('Disetujui');
});

// --- B7: flash laporan error ---

test('bb7: laporan error menampilkan pesan sukses di halaman form', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);

    $response = $this->actingAs($pengaju)
        ->followingRedirects()
        ->post(route('error-report.store'), [
            'judul' => 'Tombol kirim tidak berfungsi',
            'deskripsi' => 'Tombol kirim di daftar proposal tidak merespons.',
        ]);

    $response->assertOk();
    $response->assertSee('alert alert-success', false);
    $response->assertSee('Laporan error berhasil dikirim');
    $this->assertDatabaseHas('error_reports', [
        'judul' => 'Tombol kirim tidak berfungsi',
        'status' => 'Open',
    ]);
});

// --- B8: ketua sebagai anggota ---

test('bb8: ketua yang dipilih sebagai anggota diberi peringatan dan tidak tercatat sebagai anggota', function () {
    $pengaju = bbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bbSkema();
    $proposal = bbProposal($pengaju, $skema, [
        'judul' => 'Proposal anggota uji',
        'status' => 'Draft',
    ]);
    $fakultasId = $proposal->fakultas_prodi;

    $response = $this->actingAs($pengaju)
        ->followingRedirects()
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => 'Proposal anggota uji',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultasId,
            'anggota' => ['Dr. Andi'],
        ]);

    $response->assertOk();
    $response->assertSee('Catatan:');
    $response->assertSee('tidak dicatat sebagai anggota tim');

    $this->assertDatabaseMissing('proposal_members', [
        'proposal_id' => $proposal->id,
        'user_id' => $pengaju->id,
    ]);
    expect($proposal->fresh()->anggota ?? [])->not->toContain('Dr. Andi');
});
