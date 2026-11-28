<?php

// Audit + perbaikan keamanan dan logika alur proposal inti, 28 Nov 2026.
// Helper berprefix kp* agar tidak bentrok dengan test lain.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function kpUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function kpSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function kpFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function kpPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function kpProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji keamanan',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => kpFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
        'file_path' => 'proposal_files/rahasia.pdf',
    ], $attrs));
}

function kpSimpanBerkas(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('proposal_files/rahasia.pdf', '%PDF-1.4 isi-berkas-rahasia');
}

function kpPayloadUpdate(Proposal $proposal, Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'draft',
        'judul' => 'Proposal uji keamanan',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $proposal->fakultas_prodi,
        'skema_id' => $skema->id,
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

function kpPayloadSubmit(Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Proposal baru',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => kpFakultas()->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

function kpProposalSiapDiputuskan(User $user, Skema $skema): Proposal
{
    $proposal = kpProposal($user, $skema, ['status' => 'Review Selesai']);
    $reviewer1 = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = kpUser('reviewer', ['name' => 'Dewi']);
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

// --- A1: download / preview berkas proposal ---

test('kp: pengaju lain tidak bisa download berkas proposal orang lain (403)', function () {
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $orangLain = kpUser('pengaju', ['name' => 'Citra Lestari']);
    $proposal = kpProposal($ketua, kpSkema());

    $this->actingAs($orangLain)
        ->get(route('proposal.download', $proposal->id))
        ->assertForbidden();
});

test('kp: pengaju lain tidak bisa preview berkas proposal orang lain (403)', function () {
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $orangLain = kpUser('pengaju', ['name' => 'Citra Lestari']);
    $proposal = kpProposal($ketua, kpSkema());

    $this->actingAs($orangLain)
        ->get(route('proposal.preview', $proposal->id))
        ->assertForbidden();
});

test('kp: ketua, anggota diterima, reviewer yang ditugaskan, dan verifikator boleh download', function () {
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $anggota = kpUser('pengaju', ['name' => 'Budi Santoso']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewerLain = kpUser('reviewer', ['name' => 'Dewi']);
    $verifikator = kpUser('verifikator', ['name' => 'Veri']);
    $proposal = kpProposal($ketua, kpSkema());

    ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Diterima',
    ]);
    $proposal->reviewers()->sync([$reviewer->id]);

    $this->actingAs($ketua)->get(route('proposal.download', $proposal->id))->assertOk();
    $this->actingAs($anggota)->get(route('proposal.download', $proposal->id))->assertOk();
    $this->actingAs($reviewer)->get(route('proposal.download', $proposal->id))->assertOk();
    $this->actingAs($verifikator)->get(route('proposal.download', $proposal->id))->assertOk();

    // Reviewer yang tidak ditugaskan tetap ditolak.
    $this->actingAs($reviewerLain)
        ->get(route('proposal.download', $proposal->id))
        ->assertForbidden();
});

test('kp: anggota yang menolak undangan tidak bisa download berkas proposal', function () {
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $anggota = kpUser('pengaju', ['name' => 'Budi Santoso']);
    $proposal = kpProposal($ketua, kpSkema());

    ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Ditolak',
    ]);

    $this->actingAs($anggota)
        ->get(route('proposal.download', $proposal->id))
        ->assertForbidden();
});

// --- A2: tinjau / edit / update / destroy ---

test('kp: pengaju lain tidak bisa membuka halaman tinjau proposal orang lain (403)', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $orangLain = kpUser('pengaju', ['name' => 'Citra Lestari']);
    $proposal = kpProposal($ketua, kpSkema());

    $this->actingAs($orangLain)
        ->get(route('proposal.tinjau', $proposal->id))
        ->assertForbidden();
    $this->actingAs($ketua)
        ->get(route('proposal.tinjau', $proposal->id))
        ->assertOk();
});

test('kp: edit dan update proposal yang sudah Dikirim ditolak, isi tidak berubah', function () {
    kpPeriodeAktif();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Dikirim']);

    $response = $this->actingAs($ketua)->get(route('proposal.edit', $proposal->id));
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $response = $this->actingAs($ketua)
        ->from(route('proposal.index'))
        ->put(route('proposal.update', $proposal->id), kpPayloadUpdate($proposal, $skema, [
            'judul' => 'Judul diubah diam-diam',
        ]));
    $response->assertSessionHas('error');
    expect($proposal->fresh()->judul)->toBe('Proposal uji keamanan');
});

test('kp: edit dan update proposal Revisi Administrasi tetap diizinkan', function () {
    kpPeriodeAktif();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Revisi Administrasi']);

    $this->actingAs($ketua)
        ->get(route('proposal.edit', $proposal->id))
        ->assertOk();

    $response = $this->actingAs($ketua)
        ->put(route('proposal.update', $proposal->id), kpPayloadUpdate($proposal, $skema, [
            'aksi' => 'draft',
            'judul' => 'Judul revisi',
        ]));
    $response->assertSessionHas('success');
    expect($proposal->fresh()->judul)->toBe('Judul revisi');
});

test('kp: orang lain tidak bisa edit, update, atau hapus proposal (403 / ditolak)', function () {
    kpPeriodeAktif();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $orangLain = kpUser('pengaju', ['name' => 'Citra Lestari']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Draft']);

    $this->actingAs($orangLain)
        ->get(route('proposal.edit', $proposal->id))
        ->assertForbidden();
    $this->actingAs($orangLain)
        ->put(route('proposal.update', $proposal->id), kpPayloadUpdate($proposal, $skema))
        ->assertForbidden();
    $this->actingAs($orangLain)
        ->delete(route('proposal.destroy', $proposal->id));
    expect($proposal->fresh())->not->toBeNull();
});

test('kp: hapus proposal yang sudah Dikirim ditolak, hapus Draft diizinkan', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();
    $dikirim = kpProposal($ketua, $skema, ['judul' => 'Sudah dikirim', 'status' => 'Dikirim']);
    $draft = kpProposal($ketua, $skema, ['judul' => 'Masih draft', 'status' => 'Draft']);

    $response = $this->actingAs($ketua)->delete(route('proposal.destroy', $dikirim->id));
    $response->assertSessionHas('error');
    expect($dikirim->fresh())->not->toBeNull();

    $this->actingAs($ketua)->delete(route('proposal.destroy', $draft->id));
    expect($draft->fresh())->toBeNull();
});

test('kp: admin tidak bisa menghapus proposal yang sudah Disetujui', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Disetujui']);

    $response = $this->actingAs($admin)->delete(route('proposal.destroy', $proposal->id));
    $response->assertSessionHas('error');
    expect($proposal->fresh())->not->toBeNull();
});

// --- B: kirim ulang / double submit ---

test('kp: kirim ulang proposal yang sudah Dikirim ditolak', function () {
    kpPeriodeAktif();
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Dikirim']);

    $response = $this->actingAs($ketua)->post(route('proposal.kirim', $proposal->id));
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Dikirim');
});

test('kp: kirim dua kali berurutan — yang kedua ditolak dan tidak ada pengiriman ganda', function () {
    kpPeriodeAktif();
    kpSimpanBerkas();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Draft']);

    $this->actingAs($ketua)->post(route('proposal.kirim', $proposal->id));
    expect($proposal->fresh()->status)->toBe('Dikirim');

    $response = $this->actingAs($ketua)->post(route('proposal.kirim', $proposal->id));
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    expect($proposal->fresh()->no_pengajuan)->not->toBeNull();
});

// --- B: verifikasi ---

test('kp: verifikasi pada proposal Draft ditolak dan tidak ada baris verifikasi', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = kpUser('verifikator', ['name' => 'Veri']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Draft']);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Lolos', 'catatan' => 'OK.']);
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Draft');
    $this->assertDatabaseCount('verifications', 0);
});

// --- B: plotting ---

test('kp: plotting proposal yang masih Dikirim ditolak', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $lppm = kpUser('lppm', ['name' => 'Lppm']);
    $reviewer1 = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = kpUser('reviewer', ['name' => 'Dewi']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Dikirim']);

    $response = $this->actingAs($lppm)
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $reviewer1->id,
            'reviewer_2' => $reviewer2->id,
            'review_deadline' => now()->addWeek()->format('Y-m-d\\TH:i'),
        ]);
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

test('kp: plotting dengan tenggat di masa lalu ditolak validasi', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $lppm = kpUser('lppm', ['name' => 'Lppm']);
    $reviewer1 = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = kpUser('reviewer', ['name' => 'Dewi']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Lolos Administrasi']);

    $response = $this->actingAs($lppm)
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $reviewer1->id,
            'reviewer_2' => $reviewer2->id,
            'review_deadline' => now()->subDay()->format('Y-m-d'),
        ]);
    $response->assertSessionHasErrors('review_deadline');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

test('kp: plotting menugaskan pengaju (bukan reviewer) ditolak', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $lppm = kpUser('lppm', ['name' => 'Lppm']);
    $bukanReviewer = kpUser('pengaju', ['name' => 'Budi Santoso']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Lolos Administrasi']);

    $response = $this->actingAs($lppm)
        ->post(route('proposal.assignReviewer', $proposal->id), [
            'reviewer_1' => $bukanReviewer->id,
            'reviewer_2' => $reviewer->id,
            'review_deadline' => now()->addWeek()->format('Y-m-d\\TH:i'),
        ]);
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Lolos Administrasi');
    $this->assertDatabaseCount('proposal_reviewers', 0);
});

// --- B: keputusan pendanaan ---

test('kp: keputusan pendanaan sebelum review selesai ditolak', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Sedang Direview']);

    $response = $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Sedang Direview');
    expect($proposal->fresh()->status_pendanaan)->not->toBe('Disetujui');
});

test('kp: keputusan ditolak bila baru 1 dari 2 reviewer mengirim review', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Review Selesai']);
    $reviewer1 = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = kpUser('reviewer', ['name' => 'Dewi']);
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

    $response = $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);
    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Review Selesai');
});

test('kp: keputusan diizinkan bila review selesai dan kedua reviewer sudah submit', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $proposal = kpProposalSiapDiputuskan($ketua, kpSkema());

    $response = $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Disetujui']);
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Disetujui');
    expect($proposal->fresh()->status_pendanaan)->toBe('Disetujui');
});

test('kp: nilai status_pendanaan Review Selesai tidak lagi diterima sebagai keputusan', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $proposal = kpProposalSiapDiputuskan($ketua, kpSkema());

    $response = $this->actingAs($admin)
        ->patch(route('proposal.keputusan', $proposal->id), ['status_pendanaan' => 'Review Selesai']);
    $response->assertSessionHasErrors('status_pendanaan');
    expect($proposal->fresh()->status)->toBe('Review Selesai');
});

// --- B: set-review manual ---

test('kp: pengaju tidak bisa memindahkan proposal ke tahap review; admin hanya dari Lolos Administrasi', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin Utama']);
    $dikirim = kpProposal($ketua, kpSkema(), ['judul' => 'Masih dikirim', 'status' => 'Dikirim']);
    $lolos = kpProposal($ketua, kpSkema(), ['judul' => 'Sudah lolos', 'status' => 'Lolos Administrasi']);

    $this->actingAs($ketua)
        ->patch(route('proposal.set-review', $lolos->id))
        ->assertForbidden();
    expect($lolos->fresh()->status)->toBe('Lolos Administrasi');

    $response = $this->actingAs($admin)->patch(route('proposal.set-review', $dikirim->id));
    $response->assertSessionHas('error');
    expect($dikirim->fresh()->status)->toBe('Dikirim');

    $response = $this->actingAs($admin)->patch(route('proposal.set-review', $lolos->id));
    $response->assertSessionHas('success');
    expect($lolos->fresh()->status)->toBe('Sedang Direview');
});

// --- A3: mass assignment ---

test('kp: injeksi status/user_id/file_path/biaya lewat store tidak berpengaruh', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $korban = kpUser('pengaju', ['name' => 'Korban']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'status' => 'Disetujui',
        'user_id' => $korban->id,
        'file_path' => 'proposal_files/evil.pdf',
        'biaya' => 999,
        'review_deadline' => now()->addYear()->format('Y-m-d'),
    ]));

    $response->assertSessionHas('success');
    $proposal = Proposal::firstOrFail();
    expect($proposal->status)->toBe('Dikirim')
        ->and($proposal->user_id)->toBe($ketua->id)
        ->and($proposal->biaya)->toBe(6000000)
        ->and($proposal->file_path)->not->toBe('proposal_files/evil.pdf')
        ->and($proposal->review_deadline)->toBeNull();
});

test('kp: injeksi status/user_id lewat update tidak berpengaruh', function () {
    kpPeriodeAktif();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $korban = kpUser('pengaju', ['name' => 'Korban']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($ketua)
        ->put(route('proposal.update', $proposal->id), kpPayloadUpdate($proposal, $skema, [
            'judul' => 'Judul boleh berubah',
            'status' => 'Disetujui',
            'user_id' => $korban->id,
        ]));

    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Draft')
        ->and($proposal->fresh()->user_id)->toBe($ketua->id)
        ->and($proposal->fresh()->judul)->toBe('Judul boleh berubah');
});

// --- A4/A5 + B: validasi unggahan, RAB, skema, fakultas ---

test('kp: RAB negatif ditolak validasi saat submit', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => -5000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHasErrors('rab.0.jumlah');
    $this->assertDatabaseCount('proposals', 0);
});

test('kp: RAB total nol ditolak saat submit dan saat kirim draft', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 0, 'keterangan' => ''],
        ],
    ]));
    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);

    // Jalur kirim draft: RAB 0 yang tersimpan sebagai item tetap ditolak.
    $draft = kpProposal($ketua, $skema, ['status' => 'Draft', 'biaya' => 0]);
    $draft->rabItems()->create(['nama_komponen' => 'Honorarium', 'jumlah' => 0, 'keterangan' => null]);

    $response = $this->actingAs($ketua)->post(route('proposal.kirim', $draft->id));
    $response->assertSessionHas('error');
    expect($draft->fresh()->status)->toBe('Draft');
});

test('kp: skema_id yang tidak ada ditolak validasi', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'skema_id' => 999999,
    ]));

    $response->assertSessionHasErrors('skema_id');
    $this->assertDatabaseCount('proposals', 0);
});

test('kp: plafon tidak bisa dilewati lewat request langsung (bypass UI)', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema(['plafon' => 1000000]);

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 50000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);
});

test('kp: update dengan fakultas_prodi yang tidak ada ditolak validasi', function () {
    kpPeriodeAktif();
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();
    $proposal = kpProposal($ketua, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($ketua)
        ->put(route('proposal.update', $proposal->id), kpPayloadUpdate($proposal, $skema, [
            'fakultas_prodi' => 999999,
        ]));

    $response->assertSessionHasErrors('fakultas_prodi');
    expect($proposal->fresh()->fakultas_prodi)->not->toBe(999999);
});

test('kp: unggahan selain PDF/DOC/DOCX ditolak validasi', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'file' => UploadedFile::fake()->create('jahat.exe', 100, 'application/x-msdownload'),
    ]));

    $response->assertSessionHasErrors('file');
    $this->assertDatabaseCount('proposals', 0);
});

// --- B: anggota tim ---

test('kp: ketua tidak tercatat sebagai anggota dan nama anggota duplikat hanya tersimpan sekali', function () {
    kpPeriodeAktif();
    Storage::fake('public');
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $anggota = kpUser('pengaju', ['name' => 'Budi Santoso']);
    $skema = kpSkema();

    $response = $this->actingAs($ketua)->post(route('proposal.store'), kpPayloadSubmit($skema, [
        'anggota' => ['Dr. Andi', 'Budi Santoso', 'budi santoso', 'Dr. Andi'],
    ]));

    $response->assertSessionHas('success');
    $proposal = Proposal::firstOrFail();
    expect($proposal->members()->count())->toBe(1)
        ->and($proposal->members()->first()->user_id)->toBe($anggota->id)
        ->and($proposal->anggota)->toBe(['Budi Santoso']);
});

test('kp: anggota tidak bisa konfirmasi undangan bila proposal sudah Disetujui', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $anggota = kpUser('pengaju', ['name' => 'Budi Santoso']);
    $proposal = kpProposal($ketua, kpSkema(), ['status' => 'Disetujui']);

    $undangan = ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $anggota->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Menunggu',
    ]);

    $response = $this->actingAs($anggota)
        ->post(route('anggota.konfirmasi', $undangan->id), ['aksi' => 'setuju']);
    $response->assertSessionHas('error');
    expect($undangan->fresh()->status_konfirmasi)->toBe('Menunggu');
});

// --- A1: PDF hasil review ---

test('kp: pengaju lain tidak bisa mengunduh PDF hasil review proposal orang lain', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $orangLain = kpUser('pengaju', ['name' => 'Citra Lestari']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposal($ketua, kpSkema());
    $review = Review::create([
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

    $this->actingAs($orangLain)
        ->get(route('review.pdf', $review->id))
        ->assertForbidden();
    $this->actingAs($ketua)
        ->get(route('review.pdf', $review->id))
        ->assertOk();
});

// --- A5: pencarian reviewer ---

test('kp: pencarian reviewer menolak kata kunci yang terlalu panjang', function () {
    $lppm = kpUser('lppm', ['name' => 'Lppm']);
    kpUser('reviewer', ['name' => 'Sinta']);

    $response = $this->actingAs($lppm)
        ->getJson(route('admin.proposal.search-reviewer', ['q' => str_repeat('a', 101)]));

    $response->assertOk();
    $response->assertJsonCount(0);
});

// --- A6: penjaga submit review (koordinator, 28 Nov 2026) ---
// Celah yang ditutup: review ganda oleh reviewer yang sama, review
// pada proposal yang bukan "Sedang Direview", dan review setelah
// tenggat lewat. Admin tidak pernah sampai ke sini karena route
// sudah di dalam grup middleware role:reviewer (diuji juga di bawah).

function kpPayloadReview(array $override = []): array
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

function kpProposalDireview(User $pengaju, Skema $skema, array $reviewerIds, array $attrs = []): Proposal
{
    $proposal = kpProposal($pengaju, $skema, array_merge([
        'status' => 'Sedang Direview',
        'review_deadline' => now()->addWeek(),
    ], $attrs));
    $proposal->reviewers()->sync($reviewerIds);

    return $proposal;
}

test('kp: reviewer yang sama tidak bisa submit review dua kali', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer1 = kpUser('reviewer', ['name' => 'Sinta']);
    $reviewer2 = kpUser('reviewer', ['name' => 'Dewi']);
    $proposal = kpProposalDireview($ketua, kpSkema(), [$reviewer1->id, $reviewer2->id]);

    $this->actingAs($reviewer1)
        ->post(route('review.simpan', $proposal->id), kpPayloadReview())
        ->assertRedirect(route('proposal.index'))
        ->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Sedang Direview');

    $response = $this->actingAs($reviewer1)
        ->from(route('reviewer.isi-review', $proposal->id))
        ->post(route('review.simpan', $proposal->id), kpPayloadReview());

    $response->assertRedirect(route('reviewer.isi-review', $proposal->id));
    $response->assertSessionHas('error', 'Anda sudah mengirim review untuk proposal ini.');
    $this->assertDatabaseCount('reviews', 1);
    expect($proposal->fresh()->status)->toBe('Sedang Direview');
});

test('kp: submit review pada proposal yang bukan Sedang Direview ditolak', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposalDireview($ketua, kpSkema(), [$reviewer->id], ['status' => 'Dikirim']);

    $response = $this->actingAs($reviewer)
        ->from(route('reviewer.isi-review', $proposal->id))
        ->post(route('review.simpan', $proposal->id), kpPayloadReview());

    $response->assertSessionHas('error', 'Proposal ini sudah tidak dalam tahap review.');
    $this->assertDatabaseCount('reviews', 0);
});

test('kp: submit review setelah tenggat lewat ditolak', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposalDireview($ketua, kpSkema(), [$reviewer->id], ['review_deadline' => now()->subDay()]);

    $response = $this->actingAs($reviewer)
        ->from(route('reviewer.isi-review', $proposal->id))
        ->post(route('review.simpan', $proposal->id), kpPayloadReview());

    $response->assertSessionHas('error', 'Tenggat penilaian proposal ini sudah lewat.');
    $this->assertDatabaseCount('reviews', 0);
});

test('kp: form isi review tidak bisa dibuka lagi setelah reviewer mengirim review', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposalDireview($ketua, kpSkema(), [$reviewer->id]);
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
    ]);

    $response = $this->actingAs($reviewer)
        ->from(route('proposal.index'))
        ->get(route('reviewer.isi-review', $proposal->id));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('error', 'Anda sudah mengirim review untuk proposal ini.');
});

test('kp: admin tidak bisa submit review (route hanya role reviewer)', function () {
    $ketua = kpUser('pengaju', ['name' => 'Dr. Andi']);
    $admin = kpUser('admin', ['name' => 'Admin']);
    $reviewer = kpUser('reviewer', ['name' => 'Sinta']);
    $proposal = kpProposalDireview($ketua, kpSkema(), [$reviewer->id]);

    $this->actingAs($admin)
        ->post(route('review.simpan', $proposal->id), kpPayloadReview())
        ->assertForbidden();
    $this->assertDatabaseCount('reviews', 0);
});
