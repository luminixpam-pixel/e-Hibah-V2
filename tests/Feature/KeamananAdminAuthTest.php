<?php

// Audit + perbaikan keamanan ADMIN + AUTH + BAWAAN LARAVEL, 28 Nov 2026.
// Membuktikan setiap celah yang ditutup di area ini. Helper berprefix
// ka* agar tidak bentrok dengan helper test lain (pu4*, pb*, f6j*).
// PHP TIDAK ada di VM — test ini ditulis di sini dan diverifikasi
// oleh user dengan `php artisan test --filter=KeamananAdminAuthTest`.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function kaUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function kaSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'bidang' => 'Kesehatan',
        'plafon' => 15000000,
        'kuota' => 2,
    ], $attrs));
}

function kaFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function kaProposal(User $user, ?Skema $skema = null, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji keamanan',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => kaFakultas()->id,
        'skema_id' => ($skema ?? kaSkema())->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

// --- A.1: pengaju tidak bisa mengubah role / menyusupi is_blacklisted ---

test('ka: pengaju tidak bisa mengubah data user lewat manajemen user admin', function () {
    $pengaju = kaUser('pengaju');
    $korban = kaUser('pengaju', ['name' => 'Korban']);

    $this->actingAs($pengaju)
        ->put(route('admin.users.update', $korban), [
            'name' => 'Korban',
            'username' => $korban->username,
            'role' => 'admin',
        ])
        ->assertForbidden();

    expect($korban->fresh()->role)->toBe('pengaju');
});

test('ka: pengaju tidak bisa mengubah role lewat update-role', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)
        ->post(route('admin.updateRole', $pengaju), ['role' => 'admin'])
        ->assertForbidden();

    expect($pengaju->fresh()->role)->toBe('pengaju');
});

test('ka: profil sendiri tidak bisa disusupi role admin atau is_blacklisted', function () {
    $pengaju = kaUser('pengaju', ['is_blacklisted' => true]);

    $this->actingAs($pengaju)
        ->post(route('profile.update'), [
            'name' => $pengaju->name,
            'email' => $pengaju->email,
            'role' => 'admin',
            'is_blacklisted' => false,
        ])
        ->assertRedirect(route('profile.show'));

    $segar = $pengaju->fresh();
    expect($segar->role)->toBe('pengaju')
        ->and($segar->is_blacklisted)->toBeTrue();
});

test('ka: user biasa tidak bisa mengubah profil user lain lewat ID di URL', function () {
    $pengaju = kaUser('pengaju');
    $korban = kaUser('pengaju', ['email' => 'korban@yarsi.ac.id']);

    $this->actingAs($pengaju)
        ->put(route('admin.user.update', $korban), [
            'email' => 'diubah@yarsi.ac.id',
        ])
        ->assertRedirect();

    expect($korban->fresh()->email)->toBe('korban@yarsi.ac.id');
});

// --- A.1: admin tidak bisa mengubah role dirinya sendiri ---

test('ka: admin tidak bisa mengubah role dirinya sendiri lewat manajemen user', function () {
    $admin = kaUser('admin');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => 'pengaju',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');

    expect($admin->fresh()->role)->toBe('admin');
});

test('ka: admin tidak bisa mengubah role dirinya sendiri lewat update-role', function () {
    $admin = kaUser('admin');

    $this->actingAs($admin)
        ->post(route('admin.updateRole', $admin), ['role' => 'pengaju'])
        ->assertRedirect()
        ->assertSessionHas('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');

    expect($admin->fresh()->role)->toBe('admin');
});

test('ka: update-role menolak nilai role yang tidak dikenal', function () {
    $admin = kaUser('admin');
    $pengaju = kaUser('pengaju');

    $this->actingAs($admin)
        ->post(route('admin.updateRole', $pengaju), ['role' => 'superadmin'])
        ->assertSessionHasErrors('role');

    expect($pengaju->fresh()->role)->toBe('pengaju');
});

// --- A.1: reviewer ---

test('ka: admin tidak bisa menjadikan dirinya sendiri reviewer', function () {
    $admin = kaUser('admin');

    $this->actingAs($admin)
        ->post(route('admin.reviewer.set', $admin))
        ->assertRedirect()
        ->assertSessionHas('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');

    expect($admin->fresh()->role)->toBe('admin');
});

test('ka: hanya pengaju yang bisa dijadikan reviewer', function () {
    $admin = kaUser('admin');
    $lppm = kaUser('lppm');

    $this->actingAs($admin)
        ->post(route('admin.reviewer.set', $lppm))
        ->assertRedirect()
        ->assertSessionHas('error', 'Hanya user dengan role Pengaju yang bisa dijadikan reviewer.');

    expect($lppm->fresh()->role)->toBe('lppm');
});

test('ka: reviewer dengan tugas review yang belum dinilai tidak bisa dihentikan', function () {
    $admin = kaUser('admin');
    $reviewer = kaUser('reviewer');
    $proposal = kaProposal(kaUser('pengaju'));

    DB::table('proposal_reviewers')->insert([
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
    ]);

    $response = $this->actingAs($admin)
        ->post(route('admin.reviewer.remove', $reviewer));

    $response->assertRedirect()
        ->assertSessionHas('error', 'Reviewer ini masih memiliki 1 tugas review yang belum dinilai. Selesaikan atau cabut penugasannya terlebih dahulu.');
    expect($reviewer->fresh()->role)->toBe('reviewer');
});

test('ka: reviewer tanpa tugas bisa dihentikan menjadi pengaju', function () {
    $admin = kaUser('admin');
    $reviewer = kaUser('reviewer');

    $this->actingAs($admin)
        ->post(route('admin.reviewer.remove', $reviewer))
        ->assertRedirect();

    expect($reviewer->fresh()->role)->toBe('pengaju');
});

// --- A.2: hapus data master yang masih dipakai ditolak ---

test('ka: hapus skema yang dipakai proposal ditolak', function () {
    $admin = kaUser('admin');
    $skema = kaSkema();
    kaProposal(kaUser('pengaju'), $skema);

    $this->actingAs($admin)
        ->delete(route('admin.skema.destroy', $skema))
        ->assertRedirect(route('admin.skema.index'))
        ->assertSessionHas('error');

    expect(Skema::find($skema->id))->not->toBeNull();
});

test('ka: hapus periode yang dipakai proposal ditolak', function () {
    $admin = kaUser('admin');
    $periode = HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
    kaProposal(kaUser('pengaju'), null, ['periode_id' => $periode->id]);

    $this->actingAs($admin)
        ->delete(route('admin.periode.destroy', $periode))
        ->assertRedirect(route('admin.periode.index'))
        ->assertSessionHas('error');

    expect(HibahPeriod::find($periode->id))->not->toBeNull();
});

test('ka: hapus fakultas yang dipakai proposal atau user ditolak', function () {
    $admin = kaUser('admin');
    $fakultas = kaFakultas();
    kaProposal(kaUser('pengaju'), null, ['fakultas_prodi' => $fakultas->id]);

    $this->actingAs($admin)
        ->delete(route('admin.fakultas.destroy', $fakultas))
        ->assertRedirect(route('admin.fakultas.index'))
        ->assertSessionHas('error');

    expect(Fakultas::find($fakultas->id))->not->toBeNull();
});

// --- A.3: notifikasi & laporan error ---

test('ka: mark-all-read hanya menandai notifikasi milik sendiri', function () {
    $a = kaUser('pengaju');
    $b = kaUser('pengaju');

    Notification::create(['user_id' => $a->id, 'title' => 'Untuk A', 'message' => 'x', 'type' => 'info', 'is_read' => false]);
    Notification::create(['user_id' => $b->id, 'title' => 'Untuk B', 'message' => 'y', 'type' => 'info', 'is_read' => false]);

    $this->actingAs($a)->post(route('notifications.markAllAsRead'))->assertOk();

    expect(Notification::where('user_id', $a->id)->where('is_read', false)->count())->toBe(0)
        ->and(Notification::where('user_id', $b->id)->where('is_read', false)->count())->toBe(1);
});

test('ka: fetch notifikasi hanya mengembalikan notifikasi milik sendiri', function () {
    $a = kaUser('pengaju');
    $b = kaUser('pengaju');

    Notification::create(['user_id' => $a->id, 'title' => 'Judul Milik A', 'message' => 'x', 'type' => 'info']);
    Notification::create(['user_id' => $b->id, 'title' => 'Judul Milik B', 'message' => 'y', 'type' => 'info']);

    $this->actingAs($a)->get(route('notifications.fetch'))
        ->assertOk()
        ->assertJsonFragment(['title' => 'Judul Milik A'])
        ->assertJsonMissing(['title' => 'Judul Milik B']);
});

test('ka: pengaju tidak bisa melihat daftar laporan error admin', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)->get(route('admin.error-report.index'))->assertForbidden();
});

test('ka: laporan error tidak punya route hapus', function () {
    $admin = kaUser('admin');
    $laporan = \App\Models\ErrorReport::create([
        'user_id' => kaUser('pengaju')->id,
        'judul' => 'Error uji',
        'deskripsi' => 'Deskripsi uji',
        'status' => 'Open',
    ]);

    // Tidak ada route DELETE untuk laporan error (hanya GET + PATCH):
    // DELETE ke URI yang sama dijawab 405 Method Not Allowed.
    $this->actingAs($admin)->delete(route('admin.error-report.show', $laporan))->assertStatus(405);
    expect(\App\Models\ErrorReport::find($laporan->id))->not->toBeNull();
});

// --- A.4/A.5: kalender, audit log, login throttle, registrasi ---

test('ka: pengaju tidak bisa mengubah periode lewat kalender', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)
        ->post(route('monitoring.kalender.periode'), [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ])
        ->assertForbidden();
});

test('ka: pengaju tidak bisa membuka audit log', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)->get(route('admin.audit-log.index'))->assertForbidden();
});

test('ka: login dibatasi setelah 5 kali gagal berulang', function () {
    $user = kaUser('pengaju'); // password factory: 'password'

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['username' => $user->username, 'password' => 'salah']);
        $this->assertGuest();
    }

    // Percobaan ke-6 — bahkan dengan kata sandi yang benar — ditolak.
    $this->post('/login', ['username' => $user->username, 'password' => 'password'])
        ->assertSessionHasErrors('login');

    $this->assertGuest();
});

test('ka: login berhasil setelah beberapa kali gagal membersihkan hitungan', function () {
    $user = kaUser('pengaju');

    $this->post('/login', ['username' => $user->username, 'password' => 'salah']);
    $this->post('/login', ['username' => $user->username, 'password' => 'salah']);

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->assertAuthenticatedAs($user);
});

test('ka: registrasi publik tetap mati', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Penyusup',
        'email' => 'penyusup@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();
});

// --- C: halaman error kustom & pesan Indonesia ---

test('ka: halaman 403 kustom tampil dengan bahasa Indonesia', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)->get(route('admin.users.index'))
        ->assertForbidden()
        ->assertSee('Akses Ditolak');
});

test('ka: halaman 404 kustom tampil dengan bahasa Indonesia', function () {
    $this->get('/rute-yang-jelas-tidak-ada-12345')
        ->assertNotFound()
        ->assertSee('Halaman Tidak Ditemukan');
});

test('ka: pesan validasi login berbahasa Indonesia', function () {
    $this->post('/login', ['username' => '', 'password' => ''])
        ->assertSessionHasErrors([
            'username' => 'Username wajib diisi.',
            'password' => 'Kata sandi wajib diisi.',
        ]);
});

test('ka: pesan validasi skema berbahasa Indonesia', function () {
    $admin = kaUser('admin');

    $this->actingAs($admin)
        ->post(route('admin.skema.store'), [
            'kategori' => 'Pembinaan',
            'bidang' => 'Kesehatan',
            'plafon' => -1,
            'kuota' => 2,
        ])
        ->assertSessionHasErrors([
            'plafon' => 'Plafon tidak boleh kurang dari 0.',
        ]);
});
