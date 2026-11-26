<?php

use App\Models\Fakultas;
use App\Models\Luaran;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f5luarBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f5luarBuatPengaju(array $attrs = []): User
{
    return f5luarBuatUser('pengaju', $attrs);
}

function f5luarBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f5luarBuatProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal disetujui',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function f5luarBuatLuaran(Proposal $proposal, array $attrs = []): Luaran
{
    return Luaran::create(array_merge([
        'proposal_id' => $proposal->id,
        'jenis' => 'Jurnal',
        'judul' => 'Artikel jurnal hasil penelitian.',
        'status' => 'Draft',
    ], $attrs));
}

test('pemilik melihat daftar luaran proposal disetujui di index dan show', function () {
    $pengaju = f5luarBuatPengaju(['name' => 'Dr. Andi']);
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());
    f5luarBuatLuaran($proposal, ['judul' => 'Artikel jurnal hasil penelitian.']);

    $this->actingAs($pengaju)->get(route('luaran.index'))
        ->assertOk()
        ->assertSee('Proposal disetujui')
        ->assertSee('1 luaran');

    $this->actingAs($pengaju)->get(route('luaran.show', $proposal))
        ->assertOk()
        ->assertSee('Artikel jurnal hasil penelitian.')
        ->assertSee('Jurnal');
});

test('pemilik bisa tambah luaran', function () {
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());

    $response = $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'HKI',
        'judul' => 'Hak cipta aplikasi E-Hibah.',
        'status' => 'Granted',
    ]);

    $response->assertRedirect(route('luaran.show', $proposal));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('luarans', [
        'proposal_id' => $proposal->id,
        'jenis' => 'HKI',
        'judul' => 'Hak cipta aplikasi E-Hibah.',
        'status' => 'Granted',
    ]);
});

test('pengaju bukan pemilik ditolak 403 saat lihat dan tambah luaran', function () {
    $pengaju = f5luarBuatPengaju();
    $orangLain = f5luarBuatPengaju(['name' => 'Citra Lestari']);
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());

    $this->actingAs($orangLain)->get(route('luaran.show', $proposal))->assertForbidden();
    $this->actingAs($orangLain)->post(route('luaran.store', $proposal), [
        'jenis' => 'Buku',
        'judul' => 'Menyusup.',
        'status' => 'Draft',
    ])->assertForbidden();

    $this->assertDatabaseCount('luarans', 0);
});

test('reviewer dan verifikator ditolak 403 di luaran', function () {
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());
    f5luarBuatLuaran($proposal);

    $reviewer = f5luarBuatUser('reviewer');
    $this->actingAs($reviewer)->get(route('luaran.index'))->assertForbidden();
    $this->actingAs($reviewer)->get(route('luaran.show', $proposal))->assertForbidden();

    $verifikator = f5luarBuatUser('verifikator');
    $this->actingAs($verifikator)->get(route('luaran.index'))->assertForbidden();
    $this->actingAs($verifikator)->get(route('luaran.show', $proposal))->assertForbidden();
});

test('proposal bukan Disetujui ditolak 403 untuk luaran', function () {
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema(), [
        'status' => 'Dikirim',
        'status_pendanaan' => null,
    ]);

    $this->actingAs($pengaju)->get(route('luaran.show', $proposal))->assertForbidden();
    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Belum disetujui.',
        'status' => 'Draft',
    ])->assertForbidden();

    $this->assertDatabaseCount('luarans', 0);
});

test('upload file luaran pdf dan download oleh pemilik', function () {
    Storage::fake('public');
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());

    $response = $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Prosiding',
        'judul' => 'Makalah seminar nasional.',
        'status' => 'Published',
        'file' => UploadedFile::fake()->create('makalah.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('luaran.show', $proposal));
    $luaran = Luaran::where('proposal_id', $proposal->id)->firstOrFail();
    expect($luaran->file)->not->toBeNull();
    Storage::disk('public')->assertExists($luaran->file);

    $this->actingAs($pengaju)->get(route('luaran.download', $luaran))->assertOk();

    // Pengaju lain tidak boleh mengunduh.
    $orangLain = f5luarBuatPengaju(['name' => 'Dedi']);
    $this->actingAs($orangLain)->get(route('luaran.download', $luaran))->assertForbidden();
});

test('pemilik bisa ubah luaran, file lama dihapus bila diganti', function () {
    Storage::fake('public');
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Judul awal.',
        'status' => 'Draft',
        'file' => UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf'),
    ]);
    $luaran = Luaran::where('proposal_id', $proposal->id)->firstOrFail();
    $fileLama = $luaran->file;

    $response = $this->actingAs($pengaju)->put(route('luaran.update', $luaran), [
        'jenis' => 'Jurnal',
        'judul' => 'Judul diperbarui.',
        'status' => 'Submitted',
    ]);

    $response->assertRedirect(route('luaran.show', $proposal));
    expect($luaran->fresh()->judul)->toBe('Judul diperbarui.')
        ->and($luaran->fresh()->status)->toBe('Submitted')
        ->and($luaran->fresh()->file)->toBe($fileLama);

    // Ganti file: file lama harus terhapus.
    $this->actingAs($pengaju)->put(route('luaran.update', $luaran), [
        'jenis' => 'Jurnal',
        'judul' => 'Judul diperbarui.',
        'status' => 'Submitted',
        'file' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ]);
    Storage::disk('public')->assertMissing($fileLama);
    Storage::disk('public')->assertExists($luaran->fresh()->file);
});

test('pemilik bisa hapus luaran beserta filenya, bukan pemilik ditolak', function () {
    Storage::fake('public');
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());
    $luaran = f5luarBuatLuaran($proposal, ['file' => 'luaran/bukti.pdf']);
    Storage::disk('public')->put('luaran/bukti.pdf', 'isi');

    $orangLain = f5luarBuatPengaju(['name' => 'Eka']);
    $this->actingAs($orangLain)->delete(route('luaran.destroy', $luaran))->assertForbidden();
    $this->assertDatabaseHas('luarans', ['id' => $luaran->id]);

    $response = $this->actingAs($pengaju)->delete(route('luaran.destroy', $luaran));
    $response->assertRedirect(route('luaran.show', $proposal));
    $this->assertDatabaseMissing('luarans', ['id' => $luaran->id]);
    Storage::disk('public')->assertMissing('luaran/bukti.pdf');
});

test('validasi tambah luaran gagal bila jenis atau status tidak valid', function () {
    $pengaju = f5luarBuatPengaju();
    $proposal = f5luarBuatProposalDisetujui($pengaju, f5luarBuatSkema());

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Video',
        'judul' => 'Jenis tidak dikenal.',
        'status' => 'Draft',
    ])->assertSessionHasErrors('jenis');

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Status tidak dikenal.',
        'status' => 'Selesai',
    ])->assertSessionHasErrors('status');

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => '',
        'status' => 'Draft',
    ])->assertSessionHasErrors('judul');

    $this->assertDatabaseCount('luarans', 0);
});
