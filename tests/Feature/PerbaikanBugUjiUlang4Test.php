<?php

// Perbaikan bug uji manual web ke-4, 28 Nov 2026 (Bagian CONTROLLER +
// FORM + TEST): bug #4 (pesan validasi Indonesia tidak tampil lewat
// UI), #5 (file hilang saat Simpan Draft / submit gagal), #6 (submit
// diblokir tanggungan tanpa pesan error), #7 (baris RAB kosong ikut
// divalidasi). Helper berprefix pu4* agar tidak bentrok dengan
// pu3* / bb* / f3*.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pu4User(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pu4Skema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pu4Fakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function pu4PeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function pu4Proposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => pu4Fakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Draft',
        'biaya' => 1000000,
    ], $attrs));
}

function pu4PayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
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

function pu4ProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal disetujui lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => pu4Fakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

// --- Bug #4: pesan validasi Indonesia tampil lewat server ---

test('pu4: submit proposal tanpa judul menampilkan pesan validasi Bahasa Indonesia', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu4PayloadSubmit($skema, $fakultas, ['judul' => '']));

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu4: submit proposal tanpa file menampilkan pesan validasi Bahasa Indonesia', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $payload = pu4PayloadSubmit($skema, $fakultas);
    unset($payload['file']);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertSessionHasErrors(['file' => 'File proposal wajib diunggah.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu4: edit proposal tanpa judul menampilkan pesan validasi Bahasa Indonesia', function () {
    pu4PeriodeAktif();
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    $proposal = pu4Proposal($pengaju, $skema, [
        'judul' => 'Judul lama',
        'status' => 'Draft',
    ]);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => '',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
        ]);

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    expect($proposal->fresh()->judul)->toBe('Judul lama');
});

test('pu4: kirim edit draft tanpa file menampilkan pesan File proposal wajib diunggah', function () {
    pu4PeriodeAktif();
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    $proposal = pu4Proposal($pengaju, $skema, [
        'judul' => 'Judul draft',
        'status' => 'Draft',
        'file_path' => null,
    ]);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.edit', $proposal->id))
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'submit',
            'judul' => 'Judul draft',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'skema_id' => $skema->id,
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
            ],
            'anggota' => [],
        ]);

    $response->assertSessionHas('error', 'File proposal wajib diunggah.');
    expect($proposal->fresh()->status)->toBe('Draft');
});

test('pu4: form buat dan form edit memakai novalidate agar validasi server yang tampil', function () {
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    pu4PeriodeAktif();
    $proposal = pu4Proposal($pengaju, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($pengaju)->get(route('proposal.create'));
    $response->assertOk();
    $response->assertSee('novalidate', false);

    $response = $this->actingAs($pengaju)->get(route('proposal.edit', $proposal->id));
    $response->assertOk();
    $response->assertSee('novalidate', false);
});

// --- Bug #5: file tersimpan saat Simpan Draft dan bertahan saat submit gagal ---

test('pu4: file tersimpan saat Simpan Draft dari form buat', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu4PayloadSubmit($skema, $fakultas, ['aksi' => 'draft']));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');

    $proposal = Proposal::first();
    expect($proposal)->not->toBeNull();
    expect($proposal->status)->toBe('Draft');
    expect($proposal->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($proposal->file_path);
});

test('pu4: file tersimpan saat Simpan Draft dari form edit', function () {
    Storage::fake('public');
    pu4PeriodeAktif();
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    $proposal = pu4Proposal($pengaju, $skema, ['status' => 'Draft', 'file_path' => null]);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => 'Judul draft',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'skema_id' => $skema->id,
            'file' => UploadedFile::fake()->create('draft-baru.pdf', 100, 'application/pdf'),
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
            ],
            'anggota' => [],
        ]);

    $response->assertRedirect(route('monitoring.proposalDikirim'));
    expect($proposal->fresh()->file_path)->not->toBeNull();
    Storage::disk('public')->assertExists($proposal->fresh()->file_path);
});

test('pu4: file bertahan saat submit gagal validasi dan bisa dipakai ulang tanpa unggah ulang', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    // Percobaan pertama: judul kosong → validasi gagal, berkas sudah tersimpan.
    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu4PayloadSubmit($skema, $fakultas, ['judul' => '']));

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    $fileTersimpan = session('file_tersimpan');
    expect($fileTersimpan)->not->toBeNull();
    Storage::disk('public')->assertExists($fileTersimpan);
    $this->assertDatabaseCount('proposals', 0);

    // Kirim ulang tanpa unggah baru, hanya membawa file_tersimpan.
    $payload = pu4PayloadSubmit($skema, $fakultas, ['file_tersimpan' => $fileTersimpan]);
    unset($payload['file']);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertRedirect(route('proposal.index'));
    $proposal = Proposal::first();
    expect($proposal->status)->toBe('Dikirim');
    expect($proposal->file_path)->toBe($fileTersimpan);
    Storage::disk('public')->assertExists($proposal->file_path);
});

test('pu4: file bertahan saat submit gagal karena tanggungan laporan', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    // Proposal Disetujui tanpa laporan akhir → pengaju bertanggungan.
    pu4ProposalDisetujui($pengaju, $skema);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu4PayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');
    $fileTersimpan = session('file_tersimpan');
    expect($fileTersimpan)->not->toBeNull();
    Storage::disk('public')->assertExists($fileTersimpan);
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('pu4: file baru bertahan saat edit draft gagal validasi (berkas lama tetap tertaut)', function () {
    Storage::fake('public');
    pu4PeriodeAktif();
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    Storage::disk('public')->put('proposal_files/lama.pdf', 'isi berkas lama');
    $proposal = pu4Proposal($pengaju, $skema, [
        'status' => 'Draft',
        'file_path' => 'proposal_files/lama.pdf',
    ]);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => '',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
        ]);

    $response->assertSessionHasErrors(['judul' => 'Judul proposal wajib diisi.']);
    // Berkas lama tetap tertaut di proposal.
    expect($proposal->fresh()->file_path)->toBe('proposal_files/lama.pdf');
    Storage::disk('public')->assertExists('proposal_files/lama.pdf');
    // Berkas baru sudah tersimpan dan dibawa lewat flash.
    $fileTersimpan = session('file_tersimpan');
    expect($fileTersimpan)->not->toBeNull();
    expect($fileTersimpan)->not->toBe('proposal_files/lama.pdf');
    Storage::disk('public')->assertExists($fileTersimpan);
});

// --- Bug #6: submit diblokir tanggungan menampilkan pesan error yang jelas ---

test('pu4: submit saat tanggungan menampilkan pesan error dan form buat memberi peringatan dinamis', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    pu4ProposalDisetujui($pengaju, $skema);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), pu4PayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');

    // Peringatan dinamis (bukan hanya spanduk statis) di form buat.
    $response = $this->actingAs($pengaju)->get(route('proposal.create'));
    $response->assertOk();
    $response->assertSee('masih memiliki tanggungan laporan');
});

test('pu4: form edit draft menampilkan peringatan tanggungan saat pengaju bertanggungan', function () {
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    pu4PeriodeAktif();
    pu4ProposalDisetujui($pengaju, $skema);
    $draft = pu4Proposal($pengaju, $skema, ['judul' => 'Draft baru', 'status' => 'Draft']);

    $response = $this->actingAs($pengaju)->get(route('proposal.edit', $draft->id));

    $response->assertOk();
    $response->assertSee('masih memiliki tanggungan laporan');
});

// --- Bug #7: baris RAB kosong diabaikan, baris terisi sebagian tetap divalidasi ---

test('pu4: baris RAB kosong diabaikan saat submit', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $payload = pu4PayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => '', 'jumlah' => '', 'keterangan' => ''],
        ],
    ]);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertRedirect(route('proposal.index'));
    $proposal = Proposal::first();
    expect($proposal->status)->toBe('Dikirim');
    expect($proposal->rabItems()->count())->toBe(1);
    expect($proposal->biaya)->toBe(5000000);
});

test('pu4: baris RAB terisi sebagian tetap divalidasi (nama kosong, jumlah terisi)', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $payload = pu4PayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => '', 'jumlah' => 100000, 'keterangan' => ''],
        ],
    ]);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertSessionHasErrors(['rab.1.nama_komponen' => 'Nama komponen RAB wajib diisi.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu4: baris RAB terisi sebagian tetap divalidasi (jumlah kosong, nama terisi)', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $payload = pu4PayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => '', 'keterangan' => ''],
        ],
    ]);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertSessionHasErrors(['rab.1.jumlah' => 'Jumlah RAB wajib diisi.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu4: submit dengan semua baris RAB kosong tetap ditolak sebagai RAB belum diisi', function () {
    pu4PeriodeAktif();
    Storage::fake('public');
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();

    $payload = pu4PayloadSubmit($skema, $fakultas, [
        'rab' => [
            ['nama_komponen' => '', 'jumlah' => '', 'keterangan' => ''],
        ],
    ]);

    $response = $this->actingAs($pengaju)->post(route('proposal.store'), $payload);

    $response->assertSessionHasErrors(['rab' => 'RAB wajib diisi minimal satu komponen.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('pu4: baris RAB kosong diabaikan saat simpan draft dari form edit', function () {
    Storage::fake('public');
    pu4PeriodeAktif();
    $pengaju = pu4User('pengaju', ['name' => 'Dr. Andi']);
    $skema = pu4Skema();
    $fakultas = pu4Fakultas();
    $proposal = pu4Proposal($pengaju, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), [
            'aksi' => 'draft',
            'judul' => 'Judul draft',
            'nama_ketua' => 'Dr. Andi',
            'fakultas_prodi' => $fakultas->id,
            'skema_id' => $skema->id,
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => 2000000, 'keterangan' => ''],
                ['nama_komponen' => '', 'jumlah' => '', 'keterangan' => ''],
            ],
            'anggota' => [],
        ]);

    $response->assertRedirect(route('monitoring.proposalDikirim'));
    expect($proposal->fresh()->rabItems()->count())->toBe(1);
    expect($proposal->fresh()->biaya)->toBe(2000000);
});
