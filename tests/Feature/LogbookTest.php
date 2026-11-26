<?php

use App\Models\Fakultas;
use App\Models\Logbook;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f5logBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f5logBuatPengaju(array $attrs = []): User
{
    return f5logBuatUser('pengaju', $attrs);
}

function f5logBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f5logBuatProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
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

function f5logBuatLogbook(Proposal $proposal, array $attrs = []): Logbook
{
    return Logbook::create(array_merge([
        'proposal_id' => $proposal->id,
        'tanggal' => now()->toDateString(),
        'uraian_kegiatan' => 'Pengumpulan data lapangan.',
    ], $attrs));
}

test('pemilik melihat daftar logbook proposal disetujui di index dan show', function () {
    $pengaju = f5logBuatPengaju(['name' => 'Dr. Andi']);
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());
    f5logBuatLogbook($proposal, ['uraian_kegiatan' => 'Wawancara responden.']);

    $this->actingAs($pengaju)->get(route('logbook.index'))
        ->assertOk()
        ->assertSee('Proposal disetujui')
        ->assertSee('1 entri');

    $this->actingAs($pengaju)->get(route('logbook.show', $proposal))
        ->assertOk()
        ->assertSee('Wawancara responden.');
});

test('pengaju hanya melihat proposal disetujui milik sendiri di index', function () {
    $pengaju = f5logBuatPengaju(['name' => 'Dr. Andi']);
    $orangLain = f5logBuatPengaju(['name' => 'Budi Santoso']);
    $skema = f5logBuatSkema();
    $milikSaya = f5logBuatProposalDisetujui($pengaju, $skema, ['judul' => 'Proposal saya']);
    f5logBuatProposalDisetujui($orangLain, $skema, ['judul' => 'Proposal orang lain']);

    $this->actingAs($pengaju)->get(route('logbook.index'))
        ->assertOk()
        ->assertSee('Proposal saya')
        ->assertDontSee('Proposal orang lain');

    expect($milikSaya->fresh()->status)->toBe('Disetujui');
});

test('lppm melihat semua proposal disetujui, dekan hanya fakultasnya', function () {
    $pengaju = f5logBuatPengaju();
    $skema = f5logBuatSkema();
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $fakultasB = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    f5logBuatProposalDisetujui($pengaju, $skema, ['judul' => 'Proposal FTI', 'fakultas_prodi' => $fakultasA->id]);
    f5logBuatProposalDisetujui($pengaju, $skema, ['judul' => 'Proposal FK', 'fakultas_prodi' => $fakultasB->id]);

    $lppm = f5logBuatUser('lppm');
    $this->actingAs($lppm)->get(route('logbook.index'))
        ->assertOk()
        ->assertSee('Proposal FTI')
        ->assertSee('Proposal FK');

    $dekan = f5logBuatUser('dekan', ['fakultas_id' => $fakultasA->id]);
    $this->actingAs($dekan)->get(route('logbook.index'))
        ->assertOk()
        ->assertSee('Proposal FTI')
        ->assertDontSee('Proposal FK');
});

test('pemilik bisa tambah entri logbook', function () {
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());

    $response = $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Observasi laboratorium.',
    ]);

    $response->assertRedirect(route('logbook.show', $proposal));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('logbooks', [
        'proposal_id' => $proposal->id,
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Observasi laboratorium.',
    ]);
});

test('pengaju bukan pemilik ditolak 403 saat lihat dan tambah logbook', function () {
    $pengaju = f5logBuatPengaju();
    $orangLain = f5logBuatPengaju(['name' => 'Citra Lestari']);
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());

    $this->actingAs($orangLain)->get(route('logbook.show', $proposal))->assertForbidden();
    $this->actingAs($orangLain)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Menyusup.',
    ])->assertForbidden();

    $this->assertDatabaseCount('logbooks', 0);
});

test('reviewer dan verifikator ditolak 403 di logbook', function () {
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());
    f5logBuatLogbook($proposal);

    $reviewer = f5logBuatUser('reviewer');
    $this->actingAs($reviewer)->get(route('logbook.index'))->assertForbidden();
    $this->actingAs($reviewer)->get(route('logbook.show', $proposal))->assertForbidden();

    $verifikator = f5logBuatUser('verifikator');
    $this->actingAs($verifikator)->get(route('logbook.index'))->assertForbidden();
    $this->actingAs($verifikator)->get(route('logbook.show', $proposal))->assertForbidden();
});

test('proposal bukan Disetujui ditolak 403 untuk logbook', function () {
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema(), [
        'status' => 'Dikirim',
        'status_pendanaan' => null,
    ]);

    $this->actingAs($pengaju)->get(route('logbook.show', $proposal))->assertForbidden();
    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Belum disetujui.',
    ])->assertForbidden();

    $this->assertDatabaseCount('logbooks', 0);
});

test('upload file bukti pdf dan download oleh pemilik', function () {
    Storage::fake('public');
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());

    $response = $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Kegiatan dengan bukti.',
        'file_bukti' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('logbook.show', $proposal));
    $logbook = Logbook::where('proposal_id', $proposal->id)->firstOrFail();
    expect($logbook->file_bukti)->not->toBeNull();
    Storage::disk('public')->assertExists($logbook->file_bukti);

    $this->actingAs($pengaju)->get(route('logbook.download', $logbook))->assertOk();

    // Pengaju lain tidak boleh mengunduh.
    $orangLain = f5logBuatPengaju(['name' => 'Dedi']);
    $this->actingAs($orangLain)->get(route('logbook.download', $logbook))->assertForbidden();
});

test('pemilik bisa ubah entri logbook, file lama dihapus bila diganti', function () {
    Storage::fake('public');
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());

    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Uraian awal.',
        'file_bukti' => UploadedFile::fake()->create('lama.pdf', 100, 'application/pdf'),
    ]);
    $logbook = Logbook::where('proposal_id', $proposal->id)->firstOrFail();
    $fileLama = $logbook->file_bukti;

    $response = $this->actingAs($pengaju)->put(route('logbook.update', $logbook), [
        'tanggal' => '2026-11-21',
        'uraian_kegiatan' => 'Uraian diperbarui.',
        'file_bukti' => UploadedFile::fake()->create('baru.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('logbook.show', $proposal));
    expect($logbook->fresh()->uraian_kegiatan)->toBe('Uraian diperbarui.');
    Storage::disk('public')->assertMissing($fileLama);
    Storage::disk('public')->assertExists($logbook->fresh()->file_bukti);
});

test('pemilik bisa hapus entri logbook beserta filenya, bukan pemilik ditolak', function () {
    Storage::fake('public');
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());
    $logbook = f5logBuatLogbook($proposal, ['file_bukti' => 'logbook_bukti/bukti.pdf']);
    Storage::disk('public')->put('logbook_bukti/bukti.pdf', 'isi');

    $orangLain = f5logBuatPengaju(['name' => 'Eka']);
    $this->actingAs($orangLain)->delete(route('logbook.destroy', $logbook))->assertForbidden();
    $this->assertDatabaseHas('logbooks', ['id' => $logbook->id]);

    $response = $this->actingAs($pengaju)->delete(route('logbook.destroy', $logbook));
    $response->assertRedirect(route('logbook.show', $proposal));
    $this->assertDatabaseMissing('logbooks', ['id' => $logbook->id]);
    Storage::disk('public')->assertMissing('logbook_bukti/bukti.pdf');
});

test('validasi tambah logbook gagal bila tanggal kosong atau file bukan pdf', function () {
    $pengaju = f5logBuatPengaju();
    $proposal = f5logBuatProposalDisetujui($pengaju, f5logBuatSkema());

    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'uraian_kegiatan' => 'Tanpa tanggal.',
    ])->assertSessionHasErrors('tanggal');

    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'File salah.',
        'file_bukti' => UploadedFile::fake()->create('bukti.txt', 100, 'text/plain'),
    ])->assertSessionHasErrors('file_bukti');

    $this->assertDatabaseCount('logbooks', 0);
});
