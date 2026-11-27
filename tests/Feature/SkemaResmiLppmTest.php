<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Database\Seeders\SkemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function skBuatPengaju(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'pengaju'], $attrs));
}

function skBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Kompetisi',
        'bidang' => 'Kesehatan',
        'plafon' => 25000000,
        'kuota' => 0,
        'deskripsi' => 'Skema kompetisi kesehatan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function skBuatPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function skBuatAnggota(string $nama): User
{
    return User::factory()->create(['role' => 'pengaju', 'name' => $nama]);
}

function skPayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
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

test('seeder menghasilkan lima skema dengan plafon resmi LPPM dan kuota belum ditetapkan', function () {
    (new SkemaSeeder)->run();

    $this->assertEquals(5, Skema::count());

    $this->assertDatabaseHas('skemas', ['kategori' => 'Pembinaan', 'bidang' => 'Kesehatan', 'plafon' => 15000000, 'kuota' => 0]);
    $this->assertDatabaseHas('skemas', ['kategori' => 'Pembinaan', 'bidang' => 'Humaniora', 'plafon' => 10000000, 'kuota' => 0]);
    $this->assertDatabaseHas('skemas', ['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan', 'plafon' => 25000000, 'kuota' => 0]);
    $this->assertDatabaseHas('skemas', ['kategori' => 'Kompetisi', 'bidang' => 'Humaniora', 'plafon' => 15000000, 'kuota' => 0]);
    $this->assertDatabaseHas('skemas', ['kategori' => 'Khusus', 'bidang' => null, 'plafon' => 50000000, 'kuota' => 0]);

    // Tidak ada lagi penanda [DUMMY] di data skema.
    $this->assertEquals(0, Skema::where('deskripsi', 'like', '%[DUMMY]%')->count());
});

test('seeder idempotent: dijalankan dua kali tetap lima baris', function () {
    (new SkemaSeeder)->run();
    (new SkemaSeeder)->run();

    $this->assertEquals(5, Skema::count());
});

test('seeder memperbarui baris skema lama di tempat sehingga proposal yang sudah ada tetap valid', function () {
    $pengaju = skBuatPengaju();
    $pembinaanLama = Skema::create([
        'kategori' => 'Pembinaan', 'bidang' => null,
        'plafon' => 25000000, 'kuota' => 2,
        'deskripsi' => '[DUMMY] Skema Pembinaan — deskripsi resmi menyusul.',
    ]);
    $kompetisiLama = Skema::create([
        'kategori' => 'Kompetisi', 'bidang' => null,
        'plafon' => 50000000, 'kuota' => 2,
        'deskripsi' => '[DUMMY] Skema Kompetisi — deskripsi resmi menyusul.',
    ]);
    $proposal = Proposal::create([
        'judul' => 'Proposal Lama', 'nama_ketua' => $pengaju->name,
        'user_id' => $pengaju->id, 'skema_id' => $pembinaanLama->id, 'status' => 'Draft',
    ]);
    $draftKompetisi = Proposal::create([
        'judul' => 'Draft Kompetisi', 'nama_ketua' => $pengaju->name,
        'user_id' => $pengaju->id, 'skema_id' => $kompetisiLama->id, 'status' => 'Draft',
    ]);

    (new SkemaSeeder)->run();

    // ID baris tidak berubah — proposal tetap menunjuk ke baris yang hidup.
    $this->assertEquals($pembinaanLama->id, $proposal->fresh()->skema_id);
    $this->assertEquals($kompetisiLama->id, $draftKompetisi->fresh()->skema_id);
    $this->assertEquals('Pembinaan Kesehatan', $proposal->fresh()->skema->label());
    $this->assertEquals(15000000, (int) $proposal->fresh()->skema->plafon);
    $this->assertEquals('Kompetisi Kesehatan', $draftKompetisi->fresh()->skema->label());
    $this->assertEquals(25000000, (int) $draftKompetisi->fresh()->skema->plafon);
    $this->assertEquals(5, Skema::count());
});

test('label skema dan batas ukuran tim mengikuti angka resmi LPPM', function () {
    $this->assertEquals('Pembinaan Kesehatan', skBuatSkema(['kategori' => 'Pembinaan', 'bidang' => 'Kesehatan'])->label());
    $this->assertEquals('Kompetisi Humaniora', skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Humaniora'])->label());
    $this->assertEquals('Khusus', skBuatSkema(['kategori' => 'Khusus', 'bidang' => null])->label());

    $this->assertEquals(3, skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan'])->maksUkuranTim());
    $this->assertEquals(1, skBuatSkema(['kategori' => 'Khusus', 'bidang' => null])->maksUkuranTim());
    // Pembinaan belum ditetapkan LPPM — tidak dibatasi.
    $this->assertNull(skBuatSkema(['kategori' => 'Pembinaan', 'bidang' => 'Humaniora'])->maksUkuranTim());
});

test('submit ditolak bila total RAB melebihi plafon resmi skema yang dipilih', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan', 'plafon' => 25000000]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();

    // Total RAB Rp26jt > plafon Rp25jt.
    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 24000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 2000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);
});

test('submit lolos bila total RAB tepat sama dengan plafon resmi skema', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Humaniora', 'plafon' => 15000000]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();

    // Total RAB Rp15jt = plafon Rp15jt.
    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 14000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('proposals', 1);
});

test('submit skema Kompetisi ditolak bila tim lebih dari 3 orang', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan']);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();
    skBuatAnggota('Anggota Satu');
    skBuatAnggota('Anggota Dua');
    skBuatAnggota('Anggota Tiga');

    // 1 ketua + 3 anggota = 4 orang > maks 3.
    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'anggota' => ['Anggota Satu', 'Anggota Dua', 'Anggota Tiga'],
    ]));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);
});

test('submit skema Kompetisi lolos bila tim tepat 3 orang', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan']);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();
    skBuatAnggota('Anggota Satu');
    skBuatAnggota('Anggota Dua');

    // 1 ketua + 2 anggota = 3 orang = maks.
    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'anggota' => ['Anggota Satu', 'Anggota Dua'],
    ]));

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('proposals', 1);
});

test('submit skema Khusus ditolak bila ada anggota, lolos bila hanya ketua', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Khusus', 'bidang' => null, 'plafon' => 50000000]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();
    skBuatAnggota('Anggota Satu');

    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'anggota' => ['Anggota Satu'],
    ]));

    $response->assertSessionHas('error');
    $this->assertDatabaseCount('proposals', 0);

    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'anggota' => [],
    ]));

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseCount('proposals', 1);
});

test('draft boleh melebihi batas tim dan plafon; ditolak saat dikirim lewat tombol Kirim', function () {
    Storage::fake('public');
    $user = skBuatPengaju();
    $skema = skBuatSkema(['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan', 'plafon' => 25000000]);
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    skBuatPeriodeAktif();
    skBuatAnggota('Anggota Satu');
    skBuatAnggota('Anggota Dua');
    skBuatAnggota('Anggota Tiga');

    // Draft: 1 ketua + 3 anggota (4 orang) dan RAB Rp26jt — keduanya
    // di atas batas, tetapi draft boleh disimpan bertahap.
    $response = $this->actingAs($user)->post(route('proposal.store'), skPayloadSubmit($skema, $fakultas, [
        'aksi' => 'draft',
        'anggota' => ['Anggota Satu', 'Anggota Dua', 'Anggota Tiga'],
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 24000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 2000000, 'keterangan' => ''],
        ],
    ]));

    $response->assertSessionHasNoErrors();
    $proposal = Proposal::first();
    $this->assertEquals('Draft', $proposal->status);

    // Tombol Kirim di daftar proposal menolak draft yang sama.
    $response = $this->actingAs($user)->post(route('proposal.kirim', $proposal));

    $response->assertSessionHas('error');
    $this->assertEquals('Draft', $proposal->fresh()->status);
});

test('admin tidak boleh membuat pasangan kategori dan bidang yang duplikat, tetapi boleh beda bidang', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $data = [
        'kategori' => 'Kompetisi',
        'bidang' => 'Kesehatan',
        'plafon' => 25000000,
        'kuota' => 0,
        'deskripsi' => 'Skema kompetisi kesehatan.',
        'syarat' => 'Dosen aktif.',
    ];

    $this->actingAs($admin)->post(route('admin.skema.store'), $data)
        ->assertRedirect(route('admin.skema.index'));

    $this->actingAs($admin)->post(route('admin.skema.store'), $data)
        ->assertSessionHasErrors('kategori');
    $this->assertEquals(1, Skema::where('kategori', 'Kompetisi')->where('bidang', 'Kesehatan')->count());

    // Kategori sama, bidang berbeda: boleh.
    $this->actingAs($admin)->post(route('admin.skema.store'), array_merge($data, ['bidang' => 'Humaniora', 'plafon' => 15000000]))
        ->assertRedirect(route('admin.skema.index'));
    $this->assertEquals(2, Skema::where('kategori', 'Kompetisi')->count());

    // Khusus tidak boleh punya bidang.
    $this->actingAs($admin)->post(route('admin.skema.store'), array_merge($data, ['kategori' => 'Khusus', 'bidang' => 'Kesehatan']))
        ->assertSessionHasErrors('bidang');
});
