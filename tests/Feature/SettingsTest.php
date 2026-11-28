<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Setting;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f6cUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6cSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f6cPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function f6cPayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Pengembangan Sistem E-Hibah',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

test('admin bisa membuka halaman setelan', function () {
    $admin = f6cUser('admin');

    $response = $this->actingAs($admin)->get(route('admin.settings.index'));

    $response->assertOk();
    $response->assertSee('Setelan Admin');
});

test('lppm tidak bisa membuka halaman setelan', function () {
    $lppm = f6cUser('lppm');

    $response = $this->actingAs($lppm)->get(route('admin.settings.index'));

    $response->assertForbidden();
});

test('pengaju tidak bisa membuka halaman setelan', function () {
    $pengaju = f6cUser('pengaju');

    $response = $this->actingAs($pengaju)->get(route('admin.settings.index'));

    $response->assertForbidden();
});

test('admin bisa menambah setelan', function () {
    $admin = f6cUser('admin');

    $response = $this->actingAs($admin)->post(route('admin.settings.store'), [
        'key' => 'batas_umum',
        'value' => '3',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('settings', ['key' => 'batas_umum', 'value' => '3']);
});

test('pengaju tidak bisa menambah setelan', function () {
    $pengaju = f6cUser('pengaju');

    $response = $this->actingAs($pengaju)->post(route('admin.settings.store'), [
        'key' => 'batas_umum',
        'value' => '3',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('settings', ['key' => 'batas_umum']);
});

test('key setelan harus snake_case', function () {
    $admin = f6cUser('admin');

    $response = $this->actingAs($admin)->post(route('admin.settings.store'), [
        'key' => 'Batas Umum',
        'value' => '3',
    ]);

    $response->assertSessionHasErrors('key');
    $this->assertDatabaseMissing('settings', ['value' => '3']);
});

test('key setelan tidak boleh duplikat', function () {
    $admin = f6cUser('admin');
    Setting::create(['key' => 'batas_umum', 'value' => '3']);

    $response = $this->actingAs($admin)->post(route('admin.settings.store'), [
        'key' => 'batas_umum',
        'value' => '5',
    ]);

    $response->assertSessionHasErrors('key');
    expect(Setting::where('key', 'batas_umum')->first()->value)->toBe('3');
});

test('admin bisa mengubah dan menghapus setelan', function () {
    $admin = f6cUser('admin');
    $setting = Setting::create(['key' => 'batas_umum', 'value' => '3']);

    $response = $this->actingAs($admin)->put(route('admin.settings.update', $setting->id), [
        'key' => 'batas_umum',
        'value' => '5',
    ]);

    $response->assertRedirect();
    expect($setting->fresh()->value)->toBe('5');

    $response = $this->actingAs($admin)->delete(route('admin.settings.destroy', $setting->id));

    $response->assertRedirect();
    $this->assertDatabaseMissing('settings', ['key' => 'batas_umum']);
});

test('komponen RAB kustom dari settings muncul di form pengajuan', function () {
    f6cPeriodeAktif();
    $pengaju = f6cUser('pengaju', ['name' => 'Dr. Andi']);
    Setting::create(['key' => 'rab_komponen_kustom', 'value' => '["Sewa Lab", "Uji Sampel"]']);

    $response = $this->actingAs($pengaju)->get(route('proposal.create'));

    $response->assertOk();
    $response->assertSee('Sewa Lab');
    $response->assertSee('Uji Sampel');
    // Komponen bawaan tetap ada.
    $response->assertSee('Honorarium');
});

test('submit proposal dengan komponen RAB kustom lolos validasi', function () {
    Storage::fake('public');
    $pengaju = f6cUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f6cSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    f6cPeriodeAktif();
    Setting::create(['key' => 'rab_komponen_kustom', 'value' => '["Sewa Lab"]']);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), f6cPayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Sewa Lab', 'jumlah' => 2000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');

    $proposal = Proposal::where('judul', 'Pengembangan Sistem E-Hibah')->firstOrFail();
    $this->assertDatabaseHas('rab_items', [
        'proposal_id' => $proposal->id,
        'nama_komponen' => 'Sewa Lab',
    ]);
});

test('submit proposal dengan komponen RAB yang tidak terdaftar ditolak', function () {
    Storage::fake('public');
    $pengaju = f6cUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f6cSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    f6cPeriodeAktif();

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), f6cPayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Komponen Asing XYZ', 'jumlah' => 2000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHasErrors('rab.0.nama_komponen');
    $this->assertDatabaseMissing('proposals', ['judul' => 'Pengembangan Sistem E-Hibah']);
});
