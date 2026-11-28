<?php

// Perbaikan bug uji manual web ke-5, 28 Nov 2026 (Bagian C):
// - Butir 3: /laporan-akhir tidak punya kolom "Status Pendanaan".
// - Butir 4: pemberitahuan pengganti hapus akun tidak terlihat
//   (hanya ada di delete-user-form yang tidak pernah di-render).
// - Butir 5: kartu statistik dashboard bukan tautan.
// - Butir 9: halaman error 405 kustom.
// - Butir 10: pagination /admin/reviewer (313 baris satu halaman).
// Helper berprefix ub5c* agar tidak bentrok dengan helper pekerja
// lain (pu4*, pu4b*, ub5* bila ada).
//
// CATATAN: tidak ada PHP di VM ini saat penulisan, jadi test belum
// dijalankan — jalankan `php artisan test --filter=ub5c` di mesin
// yang punya PHP.

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ub5cUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function ub5cSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function ub5cProposal(User $user, Skema $skema, array $attrs = []): Proposal
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

// --- Butir 3: kolom "Status Pendanaan" di /laporan-akhir ---

test('ub5c: laporan akhir menampilkan kolom Status Pendanaan dengan badge yang sama seperti laporan kemajuan', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    $pengaju = ub5cUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = ub5cSkema();
    ub5cProposal($pengaju, $skema, [
        'judul' => 'Proposal didanai uji',
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ]);
    ub5cProposal($pengaju, $skema, [
        'judul' => 'Proposal ditolak uji',
        'status' => 'Ditolak',
        'status_pendanaan' => 'Ditolak',
    ]);

    $response = $this->actingAs($admin)->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('Status Pendanaan', false);
    $response->assertSee('>Disetujui<', false);
    $response->assertSee('>Ditolak<', false);
});

test('ub5c: laporan akhir menampilkan Proses bila pendanaan belum diputuskan', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    $pengaju = ub5cUser('pengaju', ['name' => 'Dr. Andi']);
    ub5cProposal($pengaju, ub5cSkema(), ['judul' => 'Proposal proses uji']);

    $response = $this->actingAs($admin)->get(route('laporan.akhir.index'));

    $response->assertOk();
    $response->assertSee('>Proses<', false);
});

// --- Butir 4: pemberitahuan hapus akun di halaman profil ---

test('ub5c: profil admin menampilkan pemberitahuan akun admin tidak dapat dihapus', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);

    $response = $this->actingAs($admin)->get(route('profile.show'));

    $response->assertOk();
    $response->assertSee('Akun admin tidak dapat dihapus. Penghapusan akun hanya berlaku untuk user non-admin.');
});

test('ub5c: profil non-admin tidak menampilkan pemberitahuan hapus akun admin', function () {
    $pengaju = ub5cUser('pengaju', ['name' => 'Dr. Andi']);

    $response = $this->actingAs($pengaju)->get(route('profile.show'));

    $response->assertOk();
    $response->assertDontSee('Akun admin tidak dapat dihapus. Penghapusan akun hanya berlaku untuk user non-admin.');
});

// --- Butir 5: kartu statistik dashboard berupa tautan ---

test('ub5c: kartu statistik dashboard admin menautkan ke halaman yang sesuai', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);

    $response = $this->actingAs($admin)->get(route('dashboard.admin'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('admin.users.index').'" class="admin-stat-card stat-users">', false);
    $response->assertSee('<a href="'.route('proposal.index').'" class="admin-stat-card stat-proposals">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDisetujui').'" class="admin-stat-card stat-approved">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDirevisi').'" class="admin-stat-card stat-revision">', false);
});

test('ub5c: kartu statistik dashboard lppm menautkan ke halaman yang sesuai', function () {
    $lppm = ub5cUser('lppm', ['name' => 'Staf LPPM']);

    $response = $this->actingAs($lppm)->get(route('dashboard.lppm'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('lppm.plotting').'" class="lppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalSedangDireview').'" class="lppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.reviewSelesai').'" class="lppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDisetujui').'" class="lppm-stat-card">', false);
});

test('ub5c: dashboard dekan tetap 3 kartu statistik dan ketiganya berupa tautan', function () {
    $dekan = ub5cUser('dekan', ['name' => 'Dekan Uji']);

    $response = $this->actingAs($dekan)->get(route('dashboard.dekan'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('proposal.index').'" class="card text-decoration-none text-dark">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDisetujui').'" class="card text-decoration-none text-dark">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDitolak').'" class="card text-decoration-none text-dark">', false);
});

test('ub5c: kartu statistik dashboard kepala lppm menautkan ke halaman yang sesuai', function () {
    $kepala = ub5cUser('kepala_lppm', ['name' => 'Kepala LPPM']);

    $response = $this->actingAs($kepala)->get(route('dashboard.kepala-lppm'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('monitoring.reviewSelesai').'" class="klppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDisetujui').'" class="klppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDitolak').'" class="klppm-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDirevisi').'" class="klppm-stat-card">', false);
});

test('ub5c: kartu statistik dashboard pengaju menautkan ke daftar proposal', function () {
    $pengaju = ub5cUser('pengaju', ['name' => 'Dr. Andi']);

    $response = $this->actingAs($pengaju)->get(route('dashboard.pengaju'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('proposal.index').'" class="pengaju-stat-card stat-proposal">', false);
    $response->assertSee('<a href="'.route('proposal.index').'" class="pengaju-stat-card stat-draft">', false);
    $response->assertSee('<a href="'.route('proposal.index').'" class="pengaju-stat-card stat-sent">', false);
    $response->assertSee('<a href="'.route('proposal.index').'" class="pengaju-stat-card stat-result">', false);
});

test('ub5c: kartu statistik dashboard reviewer menautkan ke daftar tugas review', function () {
    $reviewer = ub5cUser('reviewer', ['name' => 'Reviewer Uji']);

    $response = $this->actingAs($reviewer)->get(route('dashboard.reviewer'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('monitoring.proposalPerluDireview').'" class="review-stat-card stat-total">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalPerluDireview').'" class="review-stat-card stat-pending">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalPerluDireview').'" class="review-stat-card stat-completed">', false);
});

test('ub5c: kartu statistik dashboard verifikator menautkan ke halaman yang sesuai', function () {
    $verifikator = ub5cUser('verifikator', ['name' => 'Verifikator Uji']);

    $response = $this->actingAs($verifikator)->get(route('dashboard.verifikator'));

    $response->assertOk();
    $response->assertSee('<a href="'.route('proposal.index').'" class="verif-stat-card">', false);
    $response->assertSee('<a href="'.route('monitoring.proposalDirevisi').'" class="verif-stat-card">', false);
});

// --- Butir 9: halaman error 405 kustom ---

test('ub5c: metode yang tidak diizinkan mengembalikan halaman error 405 kustom', function () {
    // GET /dashboard tidak punya pasangan POST: Laravel melempar
    // MethodNotAllowedHttpException yang dirender lewat
    // resources/views/errors/405.blade.php.
    $response = $this->post(route('dashboard'));

    $response->assertStatus(405);
    $response->assertSee('Metode Tidak Diizinkan');
    $response->assertSee('Kembali ke Dashboard');
});

// --- Butir 10: pagination /admin/reviewer ---

test('ub5c: admin reviewer hanya menampilkan 20 baris di halaman pertama beserta tautan pagination', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    for ($i = 1; $i <= 25; $i++) {
        ub5cUser('pengaju', ['name' => 'Pengguna '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
    }

    $response = $this->actingAs($admin)->get(route('admin.reviewer.index'));

    $response->assertOk();
    $response->assertSee('Pengguna 01');
    $response->assertDontSee('Pengguna 20');
    $response->assertSee('?page=2', false);
});

test('ub5c: admin reviewer halaman kedua menampilkan baris berikutnya', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    for ($i = 1; $i <= 25; $i++) {
        ub5cUser('pengaju', ['name' => 'Pengguna '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
    }

    $response = $this->actingAs($admin)->get(route('admin.reviewer.index', ['page' => 2]));

    $response->assertOk();
    $response->assertSee('Pengguna 20');
    $response->assertDontSee('Pengguna 01');
});

test('ub5c: tombol Jadikan Reviewer tetap berfungsi dengan pagination', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    $pengaju = ub5cUser('pengaju', ['name' => 'Calon Reviewer']);

    $response = $this->actingAs($admin)->post(route('admin.reviewer.set', $pengaju));

    $response->assertRedirect();
    $this->assertSame('reviewer', $pengaju->fresh()->role);
});

test('ub5c: tombol Hapus Reviewer tetap berfungsi dengan pagination', function () {
    $admin = ub5cUser('admin', ['name' => 'Admin Uji']);
    $reviewer = ub5cUser('reviewer', ['name' => 'Reviewer Lama']);

    $response = $this->actingAs($admin)->post(route('admin.reviewer.remove', $reviewer));

    $response->assertRedirect();
    $this->assertSame('pengaju', $reviewer->fresh()->role);
});
