<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\AdminMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f6jUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

test('kelas AdminMiddleware sudah dihapus (digantikan RoleMiddleware dengan role:admin)', function () {
    // Bukti: alias 'admin' di bootstrap/app.php memetakan ke
    // RoleMiddleware, bukan ke kelas ini; tidak ada pemakai kelas ini
    // di route, controller, atau view mana pun (grep 26 Nov 2026).
    // Dicek lewat file_exists + class_exists tanpa autoload: class_exists()
    // biasa memicu autoloader, dan classmap composer yang belum
    // di-regenerate masih menyimpan path file yang sudah dihapus
    // sehingga include-nya error (bukan karena kelasnya masih ada).
    expect(file_exists(app_path('Http/Middleware/AdminMiddleware.php')))->toBeFalse();
    expect(class_exists(AdminMiddleware::class, false))->toBeFalse();
});

test('method adminUpdate sudah dihapus dari AuthController', function () {
    // Bukti: tidak ada route dan tidak ada pemanggil method ini
    // (grep 26 Nov 2026); fungsinya sudah tercakup method update()
    // (route admin.user.update, PUT /user/profile/{user}) yang
    // diperbaiki Fase 0 dengan cek kepemilikan, plus
    // AdminController@updateUser untuk ganti password oleh admin.
    expect(method_exists(AuthController::class, 'adminUpdate'))->toBeFalse();
});

test('update profil lewat route yang ada tetap bisa dilakukan admin untuk user lain', function () {
    $admin = f6jUser('admin');
    $target = f6jUser('pengaju', ['email' => 'lama@example.com']);

    $response = $this->actingAs($admin)->put(route('admin.user.update', $target->id), [
        'email' => 'baru@example.com',
        'nidn' => '123456',
        'fakultas' => 'Fakultas Teknologi Informasi',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'id' => $target->id,
        'email' => 'baru@example.com',
    ]);
});

test('user biasa tidak bisa mengubah profil user lain lewat route update', function () {
    $pengajuA = f6jUser('pengaju');
    $pengajuB = f6jUser('pengaju', ['email' => 'korban@example.com']);

    $response = $this->actingAs($pengajuA)->put(route('admin.user.update', $pengajuB->id), [
        'email' => 'diubah@example.com',
    ]);

    $response->assertSessionHas('error', 'Akses ditolak.');
    $this->assertDatabaseHas('users', [
        'id' => $pengajuB->id,
        'email' => 'korban@example.com',
    ]);
});
