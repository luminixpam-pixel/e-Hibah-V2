<?php

use App\Models\DokumenResmi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f6mBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6mBuatDokumenResmi(array $attrs = []): DokumenResmi
{
    $uploader = f6mBuatUser('admin');

    return DokumenResmi::create(array_merge([
        'judul' => 'Panduan Hibah',
        'kategori' => 'Panduan',
        'deskripsi' => 'Panduan resmi pengajuan hibah.',
        'file_path' => 'dokumen_resmi/panduan.pdf',
        'uploaded_by' => $uploader->id,
    ], $attrs));
}

test('user login bisa mengunduh dokumen resmi dan isi file benar', function () {
    Storage::fake('public');
    Storage::disk('public')->put('dokumen_resmi/panduan.pdf', 'isi-panduan-hibah');

    $dokumen = f6mBuatDokumenResmi();
    $pengaju = f6mBuatUser('pengaju');

    $response = $this->actingAs($pengaju)->get(route('dokumen-resmi.download', $dokumen));

    $response->assertOk();
    $response->assertDownload('Panduan Hibah.pdf');
    $this->assertSame('isi-panduan-hibah', $response->streamedContent());
});

test('unduh dokumen resmi yang tidak ada menghasilkan 404', function () {
    $pengaju = f6mBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('dokumen-resmi.download', 999999))
        ->assertNotFound();
});

test('tamu tidak bisa mengunduh dokumen resmi', function () {
    Storage::fake('public');
    $dokumen = f6mBuatDokumenResmi();

    $this->get(route('dokumen-resmi.download', $dokumen))
        ->assertRedirect(route('login'));
});

test('unduh dokumen resmi yang file fisiknya hilang kembali dengan pesan error', function () {
    Storage::fake('public');
    // Baris dokumen ada, tetapi tidak ada file di storage.
    $dokumen = f6mBuatDokumenResmi(['file_path' => 'dokumen_resmi/hilang.pdf']);
    $pengaju = f6mBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('dokumen-resmi.download', $dokumen))
        ->assertRedirect()
        ->assertSessionHas('error', 'Berkas fisik tidak ditemukan.');
});
