<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f6aBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6aBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f6aBuatPeriode(array $attrs = []): HibahPeriod
{
    return HibahPeriod::create(array_merge([
        'nama' => '2026 Ganjil',
        'start_date' => '2026-01-01',
        'end_date' => '2026-06-30',
    ], $attrs));
}

function f6aBuatFakultas(array $attrs = []): Fakultas
{
    return Fakultas::create(array_merge([
        'nama_fakultas' => 'Fakultas Teknologi Informasi',
    ], $attrs));
}

function f6aDataSkema(array $attrs = []): array
{
    return array_merge([
        'kategori' => 'Kompetisi',
        'plafon' => 50000000,
        'kuota' => 2,
        'deskripsi' => 'Skema kompetisi.',
        'syarat' => 'Dosen aktif.',
    ], $attrs);
}

function f6aDataPeriode(array $attrs = []): array
{
    return array_merge([
        'nama' => '2026 Genap',
        'start_date' => '2026-07-01',
        'end_date' => '2026-12-31',
    ], $attrs);
}

test('admin bisa melihat daftar master skema, periode, dan fakultas', function () {
    $admin = f6aBuatUser('admin');
    f6aBuatSkema();
    f6aBuatPeriode();
    f6aBuatFakultas();

    $this->actingAs($admin)->get(route('admin.skema.index'))
        ->assertOk()
        ->assertSee('Pembinaan');
    $this->actingAs($admin)->get(route('admin.periode.index'))
        ->assertOk()
        ->assertSee('2026 Ganjil');
    $this->actingAs($admin)->get(route('admin.fakultas.index'))
        ->assertOk()
        ->assertSee('Fakultas Teknologi Informasi');
});

test('lppm bisa melihat daftar master skema, periode, dan fakultas', function () {
    $lppm = f6aBuatUser('lppm');
    f6aBuatSkema();
    f6aBuatPeriode();
    f6aBuatFakultas();

    $this->actingAs($lppm)->get(route('admin.skema.index'))->assertOk();
    $this->actingAs($lppm)->get(route('admin.periode.index'))->assertOk();
    $this->actingAs($lppm)->get(route('admin.fakultas.index'))->assertOk();
});

test('pengaju tidak bisa membuka master data (403)', function () {
    $pengaju = f6aBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('admin.skema.index'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('admin.periode.index'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('admin.fakultas.index'))->assertForbidden();
    $this->actingAs($pengaju)->post(route('admin.skema.store'), f6aDataSkema())->assertForbidden();
});

test('admin bisa tambah, ubah, dan hapus skema', function () {
    $admin = f6aBuatUser('admin');

    $this->actingAs($admin)->post(route('admin.skema.store'), f6aDataSkema())
        ->assertRedirect(route('admin.skema.index'));
    $this->assertDatabaseHas('skemas', ['kategori' => 'Kompetisi', 'plafon' => 50000000, 'kuota' => 2]);

    $skema = Skema::where('kategori', 'Kompetisi')->first();
    $this->actingAs($admin)->put(route('admin.skema.update', $skema), f6aDataSkema(['plafon' => 60000000]))
        ->assertRedirect(route('admin.skema.index'));
    $this->assertDatabaseHas('skemas', ['kategori' => 'Kompetisi', 'plafon' => 60000000]);

    $this->actingAs($admin)->delete(route('admin.skema.destroy', $skema))
        ->assertRedirect(route('admin.skema.index'));
    $this->assertDatabaseMissing('skemas', ['kategori' => 'Kompetisi']);
});

test('lppm bisa mengubah nilai dummy skema', function () {
    $lppm = f6aBuatUser('lppm');
    // Nilai sama seperti data dummy SkemaSeeder (Pembinaan 25jt, kuota 2).
    $skema = f6aBuatSkema(['kategori' => 'Pembinaan', 'plafon' => 25000000, 'kuota' => 2]);

    $this->actingAs($lppm)->put(route('admin.skema.update', $skema), [
        'kategori' => 'Pembinaan',
        'plafon' => 30000000,
        'kuota' => 3,
        'deskripsi' => 'Nilai resmi dari LPPM.',
        'syarat' => 'Dosen aktif.',
    ])->assertRedirect(route('admin.skema.index'));

    $this->assertDatabaseHas('skemas', ['kategori' => 'Pembinaan', 'plafon' => 30000000, 'kuota' => 3]);
});

test('kategori skema tidak boleh duplikat', function () {
    $admin = f6aBuatUser('admin');
    f6aBuatSkema(['kategori' => 'Kompetisi']);

    $this->actingAs($admin)->post(route('admin.skema.store'), f6aDataSkema(['kategori' => 'Kompetisi']))
        ->assertSessionHasErrors('kategori');
    $this->assertEquals(1, Skema::where('kategori', 'Kompetisi')->count());
});

test('plafon skema tidak boleh negatif', function () {
    $admin = f6aBuatUser('admin');

    $this->actingAs($admin)->post(route('admin.skema.store'), f6aDataSkema(['plafon' => -1000]))
        ->assertSessionHasErrors('plafon');
    $this->assertDatabaseMissing('skemas', ['kategori' => 'Kompetisi']);
});

test('ubah skema tanpa mengganti kategori tidak kena error duplikat', function () {
    $admin = f6aBuatUser('admin');
    $skema = f6aBuatSkema(['kategori' => 'Pembinaan', 'plafon' => 25000000]);

    $this->actingAs($admin)->put(route('admin.skema.update', $skema), [
        'kategori' => 'Pembinaan',
        'plafon' => 26000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ])->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.skema.index'));

    $this->assertDatabaseHas('skemas', ['kategori' => 'Pembinaan', 'plafon' => 26000000]);
});

test('lppm bisa CRUD periode', function () {
    $lppm = f6aBuatUser('lppm');

    $this->actingAs($lppm)->post(route('admin.periode.store'), f6aDataPeriode())
        ->assertRedirect(route('admin.periode.index'));
    $this->assertDatabaseHas('hibah_periods', ['nama' => '2026 Genap']);

    $periode = HibahPeriod::where('nama', '2026 Genap')->first();
    $this->actingAs($lppm)->put(route('admin.periode.update', $periode), f6aDataPeriode(['nama' => '2026 Genap Revisi']))
        ->assertRedirect(route('admin.periode.index'));
    $this->assertDatabaseHas('hibah_periods', ['nama' => '2026 Genap Revisi']);

    $this->actingAs($lppm)->delete(route('admin.periode.destroy', $periode))
        ->assertRedirect(route('admin.periode.index'));
    $this->assertDatabaseMissing('hibah_periods', ['nama' => '2026 Genap Revisi']);
});

test('tanggal selesai periode tidak boleh lebih awal dari tanggal mulai', function () {
    $admin = f6aBuatUser('admin');

    $this->actingAs($admin)->post(route('admin.periode.store'), f6aDataPeriode([
        'start_date' => '2026-07-01',
        'end_date' => '2026-06-30',
    ]))->assertSessionHasErrors('end_date');

    $this->assertDatabaseMissing('hibah_periods', ['nama' => '2026 Genap']);
});

test('nama periode wajib diisi', function () {
    $admin = f6aBuatUser('admin');

    $this->actingAs($admin)->post(route('admin.periode.store'), f6aDataPeriode(['nama' => '']))
        ->assertSessionHasErrors('nama');
});

test('lppm bisa CRUD fakultas', function () {
    $lppm = f6aBuatUser('lppm');

    $this->actingAs($lppm)->post(route('admin.fakultas.store'), ['nama_fakultas' => 'Fakultas Hukum'])
        ->assertRedirect(route('admin.fakultas.index'));
    $this->assertDatabaseHas('fakultas', ['nama_fakultas' => 'Fakultas Hukum']);

    $fakultas = Fakultas::where('nama_fakultas', 'Fakultas Hukum')->first();
    $this->actingAs($lppm)->put(route('admin.fakultas.update', $fakultas), ['nama_fakultas' => 'Fakultas Hukum dan Syariah'])
        ->assertRedirect(route('admin.fakultas.index'));
    $this->assertDatabaseHas('fakultas', ['nama_fakultas' => 'Fakultas Hukum dan Syariah']);

    $this->actingAs($lppm)->delete(route('admin.fakultas.destroy', $fakultas))
        ->assertRedirect(route('admin.fakultas.index'));
    $this->assertDatabaseMissing('fakultas', ['nama_fakultas' => 'Fakultas Hukum dan Syariah']);
});

test('nama fakultas wajib diisi dan tidak boleh duplikat', function () {
    $admin = f6aBuatUser('admin');
    f6aBuatFakultas(['nama_fakultas' => 'Fakultas Kedokteran']);

    $this->actingAs($admin)->post(route('admin.fakultas.store'), ['nama_fakultas' => ''])
        ->assertSessionHasErrors('nama_fakultas');

    $this->actingAs($admin)->post(route('admin.fakultas.store'), ['nama_fakultas' => 'Fakultas Kedokteran'])
        ->assertSessionHasErrors('nama_fakultas');

    $this->assertEquals(1, Fakultas::where('nama_fakultas', 'Fakultas Kedokteran')->count());
});
