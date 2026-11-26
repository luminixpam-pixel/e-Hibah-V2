<?php

use App\Models\ErrorReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f6eBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6eBuatLaporan(User $user, array $attrs = []): ErrorReport
{
    return ErrorReport::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Gagal mengunggah proposal',
        'deskripsi' => 'Tombol unggah tidak merespons.',
        'status' => 'Open',
    ], $attrs));
}

test('user yang login bisa membuka form dan mengirim laporan error', function () {
    $pengaju = f6eBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('error-report.create'))
        ->assertOk()
        ->assertSee('Laporkan Error');

    $response = $this->actingAs($pengaju)->post(route('error-report.store'), [
        'judul' => 'Halaman tinjau kosong',
        'deskripsi' => 'Detail proposal tidak tampil saat dibuka.',
    ]);

    $response->assertRedirect(route('error-report.create'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('error_reports', [
        'user_id' => $pengaju->id,
        'judul' => 'Halaman tinjau kosong',
        'status' => 'Open',
    ]);
});

test('guest diarahkan ke login saat membuka form laporan error', function () {
    $this->get(route('error-report.create'))->assertRedirect(route('login'));
    $this->post(route('error-report.store'), [
        'judul' => 'Tanpa login',
        'deskripsi' => 'Tidak boleh masuk.',
    ])->assertRedirect(route('login'));

    $this->assertDatabaseCount('error_reports', 0);
});

test('validasi laporan error gagal bila judul atau deskripsi kosong', function () {
    $pengaju = f6eBuatUser('pengaju');

    $this->actingAs($pengaju)->post(route('error-report.store'), [
        'deskripsi' => 'Tanpa judul.',
    ])->assertSessionHasErrors('judul');

    $this->actingAs($pengaju)->post(route('error-report.store'), [
        'judul' => 'Tanpa deskripsi',
    ])->assertSessionHasErrors('deskripsi');

    $this->assertDatabaseCount('error_reports', 0);
});

test('admin dan lppm bisa melihat daftar dan detail laporan error', function () {
    $pengaju = f6eBuatUser('pengaju', ['name' => 'Dr. Andi']);
    f6eBuatLaporan($pengaju, ['judul' => 'Error unduh PDF']);

    $admin = f6eBuatUser('admin');
    $this->actingAs($admin)->get(route('admin.error-report.index'))
        ->assertOk()
        ->assertSee('Error unduh PDF')
        ->assertSee('Dr. Andi');

    $laporan = ErrorReport::firstOrFail();
    $this->actingAs($admin)->get(route('admin.error-report.show', $laporan))
        ->assertOk()
        ->assertSee('Tombol unggah tidak merespons.');

    $lppm = f6eBuatUser('lppm');
    $this->actingAs($lppm)->get(route('admin.error-report.index'))
        ->assertOk()
        ->assertSee('Error unduh PDF');
    $this->actingAs($lppm)->get(route('admin.error-report.show', $laporan))
        ->assertOk();
});

test('admin bisa mengubah status laporan tanpa mengubah isi laporan', function () {
    $pengaju = f6eBuatUser('pengaju');
    $laporan = f6eBuatLaporan($pengaju);

    $admin = f6eBuatUser('admin');
    $response = $this->actingAs($admin)->patch(route('admin.error-report.update', $laporan), [
        'status' => 'Diproses',
    ]);

    $response->assertRedirect(route('admin.error-report.show', $laporan));
    $response->assertSessionHas('success');

    expect($laporan->fresh()->status)->toBe('Diproses');
    expect($laporan->fresh()->judul)->toBe('Gagal mengunggah proposal');
    expect($laporan->fresh()->deskripsi)->toBe('Tombol unggah tidak merespons.');

    $this->actingAs($admin)->patch(route('admin.error-report.update', $laporan), [
        'status' => 'Selesai',
    ]);
    expect($laporan->fresh()->status)->toBe('Selesai');
});

test('pengaju ditolak 403 di halaman admin laporan error', function () {
    $pengaju = f6eBuatUser('pengaju');
    $laporan = f6eBuatLaporan($pengaju);

    $this->actingAs($pengaju)->get(route('admin.error-report.index'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('admin.error-report.show', $laporan))->assertForbidden();
    $this->actingAs($pengaju)->patch(route('admin.error-report.update', $laporan), [
        'status' => 'Selesai',
    ])->assertForbidden();

    expect($laporan->fresh()->status)->toBe('Open');
});

test('ubah status gagal bila status tidak valid', function () {
    $pengaju = f6eBuatUser('pengaju');
    $laporan = f6eBuatLaporan($pengaju);

    $admin = f6eBuatUser('admin');
    $this->actingAs($admin)->patch(route('admin.error-report.update', $laporan), [
        'status' => 'Dihapus',
    ])->assertSessionHasErrors('status');

    expect($laporan->fresh()->status)->toBe('Open');
});
