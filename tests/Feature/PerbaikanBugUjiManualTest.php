<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Skema;
use App\Models\User;
use Database\Seeders\AkunRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pbUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pbSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pbProposal(User $user, Skema $skema, array $attrs = []): Proposal
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

// --- Bug 1: /admin/reviewer 500 karena nama route tidak cocok ---

test('bug 1: halaman manajemen reviewer terbuka dan hapus reviewer mengembalikan role ke pengaju', function () {
    $admin = pbUser('admin', ['name' => 'Admin']);
    $reviewer = pbUser('reviewer', ['name' => 'Sinta']);

    $this->actingAs($admin)->get(route('admin.reviewer.index'))
        ->assertOk()
        ->assertSee('Manajemen Reviewer');

    $response = $this->actingAs($admin)
        ->post(route('admin.reviewer.remove', $reviewer->id));

    $response->assertRedirect();
    expect($reviewer->fresh()->role)->toBe('pengaju');
});

test('bug 1: bukan admin tidak bisa membuka manajemen reviewer', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);

    $this->actingAs($pengaju)->get(route('admin.reviewer.index'))->assertForbidden();
});

// --- Bug 3: /admin/hasil-review kosong saat belum ada review ---

test('bug 3: halaman hasil review admin menampilkan judul dan pesan kosong bila belum ada review', function () {
    $admin = pbUser('admin', ['name' => 'Admin']);

    $this->actingAs($admin)->get(route('admin.hasil-review'))
        ->assertOk()
        ->assertSee('Hasil Review Proposal')
        ->assertSee('Belum ada data hasil review');
});

test('bug 3: halaman hasil review admin menampilkan review yang ada', function () {
    $admin = pbUser('admin', ['name' => 'Admin']);
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = pbUser('reviewer', ['name' => 'Sinta']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, ['judul' => 'Proposal sudah direview']);

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

    $this->actingAs($admin)->get(route('admin.hasil-review'))
        ->assertOk()
        ->assertSee('Hasil Review Proposal')
        ->assertSee('Proposal sudah direview');
});

// --- Bug 4: pesan validasi revisi tidak tampil di halaman tinjau ---

test('bug 4: pengembalian tanpa alasan menampilkan pesan validasi di halaman tinjau', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = pbUser('verifikator', ['name' => 'Veri']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->from(route('proposal.tinjau', $proposal->id))
        ->followingRedirects()
        ->post(route('verifikasi.store', $proposal->id), ['hasil' => 'Revisi']);

    $response->assertOk();
    $response->assertSee('Alasan pengembalian wajib diisi bila proposal dikembalikan untuk revisi.');
    expect($proposal->fresh()->status)->toBe('Dikirim');
});

// --- Bug 5: kuota 0 tampil "Penuh" padahal belum ditetapkan ---

test('bug 5: skema berkuota 0 tampil Belum ditetapkan, bukan Penuh', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    pbSkema(['kategori' => 'Pembinaan', 'kuota' => 0, 'plafon' => 0]);
    pbSkema(['kategori' => 'Kompetisi', 'kuota' => 2, 'plafon' => 50000000]);

    $response = $this->actingAs($pengaju)->get(route('dosen.cek-kuota'));

    $response->assertOk();
    $response->assertSee('Belum ditetapkan');
    $response->assertSee('2 lagi');
    $response->assertDontSee('Penuh');
});

test('bug 5: skema berkuota yang sudah habis tetap tampil Penuh', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema(['kategori' => 'Pembinaan', 'kuota' => 1]);
    pbProposal($pengaju, $skema, ['judul' => 'Proposal terhitung kuota', 'status' => 'Dikirim']);

    $this->actingAs($pengaju)->get(route('dosen.cek-kuota'))
        ->assertOk()
        ->assertSee('Penuh');
});

// --- Bug 6: dropdown laporan kemajuan menampilkan proposal belum Disetujui ---

test('bug 6: dropdown laporan kemajuan hanya menampilkan proposal Disetujui', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $draft = pbProposal($pengaju, $skema, ['judul' => 'Proposal draft', 'status' => 'Draft']);
    $lolos = pbProposal($pengaju, $skema, ['judul' => 'Proposal lolos', 'status' => 'Lolos Administrasi']);
    $disetujui = pbProposal($pengaju, $skema, ['judul' => 'Proposal disetujui', 'status' => 'Disetujui', 'status_pendanaan' => 'Disetujui']);

    $response = $this->actingAs($pengaju)->get(route('laporan.kemajuan.index'));

    $response->assertOk();
    $response->assertSee('<option value="'.$disetujui->id.'"', false);
    $response->assertDontSee('<option value="'.$draft->id.'"', false);
    $response->assertDontSee('<option value="'.$lolos->id.'"', false);
});

test('bug 6: dropdown laporan akhir hanya menampilkan proposal Disetujui', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $draft = pbProposal($pengaju, $skema, ['judul' => 'Proposal draft', 'status' => 'Draft']);
    $disetujui = pbProposal($pengaju, $skema, ['judul' => 'Proposal disetujui', 'status' => 'Disetujui', 'status_pendanaan' => 'Disetujui']);

    $response = $this->actingAs($pengaju)->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('<option value="'.$disetujui->id.'"', false);
    $response->assertDontSee('<option value="'.$draft->id.'"', false);
});

test('bug 6: unggah laporan kemajuan untuk proposal belum Disetujui ditolak', function () {
    Storage::fake('public');
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $draft = pbProposal($pengaju, $skema, ['judul' => 'Proposal draft', 'status' => 'Draft']);

    $response = $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $draft->id,
        'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHas('error');
    expect($draft->fresh()->file_laporan)->toBeNull();
});

test('bug 6: unggah laporan kemajuan untuk proposal Disetujui berhasil', function () {
    Storage::fake('public');
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $disetujui = pbProposal($pengaju, $skema, ['judul' => 'Proposal disetujui', 'status' => 'Disetujui', 'status_pendanaan' => 'Disetujui']);

    $response = $this->actingAs($pengaju)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $disetujui->id,
        'file' => UploadedFile::fake()->create('laporan.pdf', 100, 'application/pdf'),
    ]);

    $response->assertSessionHas('success');
    expect($disetujui->fresh()->file_laporan)->not->toBeNull();
});

// --- Bug 2 (seeder): AkunRoleSeeder membuat reviewer kedua ---

test('bug 2: seeder membuat akun reviewer kedua agar plotting 2 reviewer berbeda mungkin', function () {
    Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    (new AkunRoleSeeder)->run();

    $this->assertDatabaseHas('users', ['username' => 'reviewer', 'role' => 'reviewer']);
    $this->assertDatabaseHas('users', ['username' => 'reviewer2', 'role' => 'reviewer']);
    expect(User::where('role', 'reviewer')->count())->toBe(2);
});

// --- Minor (a): kirim proposal langsung dari daftar ---

test('minor a: pemilik bisa mengirim draft dari daftar, nomor pengajuan terbit', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, [
        'judul' => 'Proposal draft siap kirim',
        'status' => 'Draft',
        'file_path' => 'proposal_files/draft.pdf',
        'biaya' => 1000000,
    ]);

    $response = $this->actingAs($pengaju)->post(route('proposal.kirim', $proposal->id));

    $response->assertRedirect(route('monitoring.proposalDikirim'));
    $response->assertSessionHas('success');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    expect($proposal->fresh()->no_pengajuan)->toBe(sprintf('EH-%d-%04d', now()->year, $proposal->id));
});

test('minor a: bukan pemilik tidak bisa mengirim proposal orang lain', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $lain = pbUser('pengaju', ['name' => 'Budi']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, [
        'status' => 'Draft',
        'file_path' => 'proposal_files/draft.pdf',
    ]);

    $this->actingAs($lain)->post(route('proposal.kirim', $proposal->id))->assertForbidden();
    expect($proposal->fresh()->status)->toBe('Draft');
});

test('minor a: proposal yang sudah dikirim tidak bisa dikirim ulang dari daftar', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, ['status' => 'Dikirim', 'file_path' => 'proposal_files/draft.pdf']);

    $response = $this->actingAs($pengaju)->post(route('proposal.kirim', $proposal->id));

    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Dikirim');
    expect($proposal->fresh()->no_pengajuan)->toBeNull();
});

test('minor a: draft tanpa berkas tidak bisa dikirim dari daftar', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, ['status' => 'Draft', 'file_path' => null]);

    $response = $this->actingAs($pengaju)->post(route('proposal.kirim', $proposal->id));

    $response->assertSessionHas('error');
    expect($proposal->fresh()->status)->toBe('Draft');
});

// --- Minor (c): nomor pengajuan tampil di daftar proposal ---

test('minor c: daftar proposal menampilkan nomor pengajuan di kolom sendiri', function () {
    $pengaju = pbUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = pbSkema();
    $proposal = pbProposal($pengaju, $skema, [
        'status' => 'Dikirim',
        'no_pengajuan' => sprintf('EH-%d-%04d', now()->year, 1),
    ]);

    $this->actingAs($pengaju)->get(route('proposal.index'))
        ->assertOk()
        ->assertSee('No. Pengajuan')
        ->assertSee($proposal->no_pengajuan);
});

// --- Minor (d): daftar pengguna memakai pagination + pencarian ---

test('minor d: daftar pengguna dipaginasi 20 per halaman', function () {
    $admin = pbUser('admin', ['name' => 'Admin Utama']);
    User::factory()->count(25)->create(['role' => 'pengaju']);

    // Beri nama berurutan agar urutan halaman dapat diprediksi.
    User::where('role', 'pengaju')->orderBy('id')->get()->each(function ($user, $i) {
        $user->update(['name' => sprintf('Pengguna %02d', $i + 1)]);
    });

    $page1 = $this->actingAs($admin)->get(route('admin.users.index'));
    $page1->assertOk();
    $page1->assertSee('Pengguna 01');
    $page1->assertDontSee('Pengguna 25');

    $page2 = $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]));
    $page2->assertOk();
    $page2->assertSee('Pengguna 25');
});

test('minor d: pencarian pengguna memfilter berdasarkan nama', function () {
    $admin = pbUser('admin', ['name' => 'Admin Utama']);
    pbUser('pengaju', ['name' => 'Citra Lestari']);
    pbUser('pengaju', ['name' => 'Dedi Kurnia']);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Citra']));

    $response->assertOk();
    $response->assertSee('Citra Lestari');
    $response->assertDontSee('Dedi Kurnia');
});
