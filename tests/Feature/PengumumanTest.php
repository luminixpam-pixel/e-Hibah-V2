<?php

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f6bBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6bBuatPengumuman(array $attrs = []): Announcement
{
    return Announcement::create(array_merge([
        'judul' => 'Penerimaan Proposal Hibah 2026',
        'isi' => 'Pendaftaran proposal hibah dibuka sampai akhir bulan.',
        'kategori' => 'eksternal',
        'published_at' => now()->subDay(),
    ], $attrs));
}

test('lppm bisa melihat daftar pengumuman dan membuat pengumuman baru', function () {
    $lppm = f6bBuatUser('lppm');
    f6bBuatPengumuman(['judul' => 'Pengumuman lama']);

    $this->actingAs($lppm)->get(route('admin.pengumuman.index'))
        ->assertOk()
        ->assertSee('Pengumuman lama');

    $response = $this->actingAs($lppm)->post(route('admin.pengumuman.store'), [
        'judul' => 'Jadwal Baru Hibah',
        'isi' => 'Jadwal pengajuan diperpanjang.',
        'kategori' => 'internal',
    ]);

    $response->assertRedirect(route('admin.pengumuman.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('announcements', [
        'judul' => 'Jadwal Baru Hibah',
        'kategori' => 'internal',
        'created_by' => $lppm->id,
    ]);

    // published_at diisi otomatis now() bila kosong.
    $baru = Announcement::where('judul', 'Jadwal Baru Hibah')->firstOrFail();
    expect($baru->published_at)->not->toBeNull();
});

test('admin bisa mengubah dan menghapus pengumuman beserta filenya', function () {
    Storage::fake('public');
    $admin = f6bBuatUser('admin');
    $pengumuman = f6bBuatPengumuman(['file' => 'pengumuman/lama.pdf']);
    Storage::disk('public')->put('pengumuman/lama.pdf', 'isi');

    $response = $this->actingAs($admin)->put(route('admin.pengumuman.update', $pengumuman), [
        'judul' => 'Judul diperbarui',
        'isi' => 'Isi diperbarui.',
        'kategori' => 'eksternal',
        'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('admin.pengumuman.index'));
    expect($pengumuman->fresh()->judul)->toBe('Judul diperbarui');
    Storage::disk('public')->assertMissing('pengumuman/lama.pdf');
    $fileBaru = $pengumuman->fresh()->file;
    Storage::disk('public')->assertExists($fileBaru);

    $response = $this->actingAs($admin)->delete(route('admin.pengumuman.destroy', $pengumuman));
    $response->assertRedirect(route('admin.pengumuman.index'));
    $this->assertDatabaseMissing('announcements', ['id' => $pengumuman->id]);
    Storage::disk('public')->assertMissing($fileBaru);
});

test('pengaju ditolak 403 di semua CRUD pengumuman admin', function () {
    $pengaju = f6bBuatUser('pengaju');
    $pengumuman = f6bBuatPengumuman();

    $this->actingAs($pengaju)->get(route('admin.pengumuman.index'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('admin.pengumuman.create'))->assertForbidden();
    $this->actingAs($pengaju)->post(route('admin.pengumuman.store'), [
        'judul' => 'Menyusup',
        'isi' => 'Isi.',
        'kategori' => 'internal',
    ])->assertForbidden();
    $this->actingAs($pengaju)->get(route('admin.pengumuman.edit', $pengumuman))->assertForbidden();
    $this->actingAs($pengaju)->put(route('admin.pengumuman.update', $pengumuman), [
        'judul' => 'Diubah',
        'isi' => 'Isi.',
        'kategori' => 'internal',
    ])->assertForbidden();
    $this->actingAs($pengaju)->delete(route('admin.pengumuman.destroy', $pengumuman))->assertForbidden();

    $this->assertDatabaseCount('announcements', 1);
    expect($pengumuman->fresh()->judul)->toBe('Penerimaan Proposal Hibah 2026');
});

test('validasi pengumuman gagal bila judul, isi, atau kategori tidak valid', function () {
    $lppm = f6bBuatUser('lppm');

    $this->actingAs($lppm)->post(route('admin.pengumuman.store'), [
        'isi' => 'Tanpa judul.',
        'kategori' => 'internal',
    ])->assertSessionHasErrors('judul');

    $this->actingAs($lppm)->post(route('admin.pengumuman.store'), [
        'judul' => 'Tanpa isi',
        'kategori' => 'internal',
    ])->assertSessionHasErrors('isi');

    $this->actingAs($lppm)->post(route('admin.pengumuman.store'), [
        'judul' => 'Kategori salah',
        'isi' => 'Isi.',
        'kategori' => 'rahasia',
    ])->assertSessionHasErrors('kategori');

    $this->actingAs($lppm)->post(route('admin.pengumuman.store'), [
        'judul' => 'File salah',
        'isi' => 'Isi.',
        'kategori' => 'internal',
        'file' => UploadedFile::fake()->create('file.txt', 100, 'text/plain'),
    ])->assertSessionHasErrors('file');

    $this->assertDatabaseCount('announcements', 0);
});

test('landing page menampilkan pengumuman yang sudah terbit dan menyembunyikan yang belum terbit', function () {
    f6bBuatPengumuman(['judul' => 'Sudah terbit', 'published_at' => now()->subDay()]);
    f6bBuatPengumuman(['judul' => 'Belum terbit', 'published_at' => null]);
    f6bBuatPengumuman(['judul' => 'Terbit nanti', 'published_at' => now()->addDay()]);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Sudah terbit')
        ->assertDontSee('Belum terbit')
        ->assertDontSee('Terbit nanti')
        ->assertDontSee('Belum ada pengumuman.');
});

test('landing page menampilkan state kosong bila belum ada pengumuman', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Belum ada pengumuman.');
});

test('landing page menampilkan maksimal 5 pengumuman terbaru', function () {
    for ($i = 1; $i <= 6; $i++) {
        f6bBuatPengumuman([
            'judul' => "Pengumuman ke-{$i}",
            'published_at' => now()->subDays(7 - $i),
        ]);
    }

    $response = $this->get(route('landing'))->assertOk();

    // Yang terbaru (ke-6 s.d. ke-2) tampil; yang tertua (ke-1) tidak.
    $response->assertSee('Pengumuman ke-6');
    $response->assertSee('Pengumuman ke-2');
    $response->assertDontSee('Pengumuman ke-1');
});
