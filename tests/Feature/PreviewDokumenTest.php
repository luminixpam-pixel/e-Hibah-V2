<?php

use App\Models\AdminDocument;
use App\Models\DokumenResmi;
use App\Models\Fakultas;
use App\Models\Logbook;
use App\Models\Luaran;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\Skema;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function pvBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function pvBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function pvBuatProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji preview',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'status_pendanaan' => 'Proses',
        'biaya' => 1000000,
    ], $attrs));
}

function pvBuatProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    return pvBuatProposal($user, $skema, array_merge([
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
    ], $attrs));
}

/**
 * Isi PDF palsu: finfo mendeteksi application/pdf dari magic "%PDF-",
 * jadi previewStoredFile mengembalikannya inline (bukan redirect).
 * Isi 'isi-pdf' biasa terdeteksi text/plain dan akan gagal.
 */
function pvIsiPdf(): string
{
    return "%PDF-1.4\n%isi-pdf-preview\n%%EOF";
}

function pvSimpanPdf(string $path): void
{
    Storage::disk('public')->put($path, pvIsiPdf());
}

/**
 * Byte JPEG minimal (magic FF D8 FF E0 + JFIF) dan PNG 1x1 valid,
 * supaya finfo mendeteksi image/jpeg / image/png tanpa butuh GD.
 */
function pvIsiJpg(): string
{
    return "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00".str_repeat("\x00", 64);
}

function pvIsiPng(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
}

function pvBuatReview(Proposal $proposal, User $reviewer, array $attrs = []): Review
{
    return Review::create(array_merge([
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
        'total_score' => 400,
        'catatan' => 'Bagus.',
    ], $attrs));
}

function pvAssertInlinePdf($response): void
{
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');
}

// --- 1: Dokumen resmi PDF ---

test('dokumen resmi pdf bisa di-preview inline oleh user login', function () {
    Storage::fake('public');
    pvSimpanPdf('dokumen_resmi/panduan.pdf');

    $dokumen = DokumenResmi::create([
        'judul' => 'Panduan Hibah',
        'kategori' => 'Panduan',
        'deskripsi' => 'Panduan resmi pengajuan hibah.',
        'file_path' => 'dokumen_resmi/panduan.pdf',
        'uploaded_by' => pvBuatUser('admin')->id,
    ]);
    $pengaju = pvBuatUser('pengaju');

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('dokumen-resmi.preview', $dokumen)));
});

// --- 2: Tamu ---

test('tamu tidak bisa membuka route preview dan diarahkan ke login', function () {
    Storage::fake('public');
    pvSimpanPdf('proposal/proposal.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['file_path' => 'proposal/proposal.pdf']);

    $this->get(route('proposal.preview', $proposal))
        ->assertRedirect(route('login'));
});

// --- 3: Berkas proposal ---

test('pemilik dan user login lain bisa me-preview berkas proposal', function () {
    Storage::fake('public');
    pvSimpanPdf('proposal/proposal.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['file_path' => 'proposal/proposal.pdf']);

    // Pemilik.
    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('proposal.preview', $proposal)));

    // User login lain juga bisa — mirror perilaku download() yang
    // tidak membatasi kepemilikan (hanya auth).
    $orangLain = pvBuatUser('pengaju', ['name' => 'Budi Santoso']);
    pvAssertInlinePdf($this->actingAs($orangLain)->get(route('proposal.preview', $proposal)));
});

// --- 4: Logbook ---

test('logbook: pemilik bisa preview, bukan pemilik dan reviewer ditolak 403', function () {
    Storage::fake('public');
    pvSimpanPdf('logbook_bukti/bukti.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposalDisetujui($pengaju, pvBuatSkema());
    $logbook = Logbook::create([
        'proposal_id' => $proposal->id,
        'tanggal' => now()->toDateString(),
        'uraian_kegiatan' => 'Pengumpulan data lapangan.',
        'file_bukti' => 'logbook_bukti/bukti.pdf',
    ]);

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('logbook.preview', $logbook)));

    $orangLain = pvBuatUser('pengaju', ['name' => 'Citra Lestari']);
    $this->actingAs($orangLain)->get(route('logbook.preview', $logbook))->assertForbidden();

    $reviewer = pvBuatUser('reviewer');
    $this->actingAs($reviewer)->get(route('logbook.preview', $logbook))->assertForbidden();
});

// --- 5: Luaran ---

test('luaran: pemilik bisa preview, bukan pemilik ditolak 403', function () {
    Storage::fake('public');
    pvSimpanPdf('luaran/jurnal.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposalDisetujui($pengaju, pvBuatSkema());
    $luaran = Luaran::create([
        'proposal_id' => $proposal->id,
        'jenis' => 'Jurnal',
        'judul' => 'Artikel jurnal hasil penelitian.',
        'status' => 'Draft',
        'file' => 'luaran/jurnal.pdf',
    ]);

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('luaran.preview', $luaran)));

    $orangLain = pvBuatUser('pengaju', ['name' => 'Dedi']);
    $this->actingAs($orangLain)->get(route('luaran.preview', $luaran))->assertForbidden();
});

// --- 6: Monev RTL ---

test('monev rtl: lppm bisa preview, bukan pemilik ditolak 403, jenis tidak dikenal 404', function () {
    Storage::fake('public');
    pvSimpanPdf('monev/rtl.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposalDisetujui($pengaju, pvBuatSkema());
    $finding = MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Laporan kemajuan terlambat.',
        'rekomendasi' => 'Segera unggah laporan kemajuan.',
        'status_temuan' => 'Open',
        'file_rtl' => 'monev/rtl.pdf',
    ]);

    $lppm = pvBuatUser('lppm');
    pvAssertInlinePdf($this->actingAs($lppm)->get(route('monev.preview', [$finding, 'rtl'])));

    $orangLain = pvBuatUser('pengaju', ['name' => 'Eka']);
    $this->actingAs($orangLain)->get(route('monev.preview', [$finding, 'rtl']))->assertForbidden();

    $this->actingAs($lppm)->get(route('monev.preview', [$finding, 'ngawur']))->assertNotFound();
});

// --- 7: Laporan kemajuan dan laporan akhir ---

test('laporan kemajuan dan laporan akhir bisa di-preview inline oleh user login', function () {
    Storage::fake('public');
    pvSimpanPdf('laporan/kemajuan.pdf');
    pvSimpanPdf('laporan/akhir.pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposalDisetujui($pengaju, pvBuatSkema(), [
        'file_laporan' => 'laporan/kemajuan.pdf',
        'file_laporan_akhir' => 'laporan/akhir.pdf',
    ]);

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('laporan.kemajuan.preview', $proposal)));
    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('laporan.akhir.preview', $proposal)));
});

// --- 8: Dokumen (AdminDocument) yang disembunyikan ---

test('dokumen yang disembunyikan: non-admin ditolak 403, admin bisa preview', function () {
    Storage::fake('public');
    pvSimpanPdf('dokumen/panduan.pdf');
    $admin = pvBuatUser('admin');
    $doc = AdminDocument::create([
        'judul' => 'Panduan Internal',
        'file_path' => 'dokumen/panduan.pdf',
        'uploaded_by' => $admin->id,
        'is_visible' => false,
    ]);

    $pengaju = pvBuatUser('pengaju');
    $this->actingAs($pengaju)->get(route('dokumen.preview', $doc))->assertForbidden();

    pvAssertInlinePdf($this->actingAs($admin)->get(route('dokumen.preview', $doc)));
});

// --- 9: Fallback tipe non-previewable (docx) ---

test('preview docx mengarah ke route download dengan flash message', function () {
    Storage::fake('public');
    Storage::disk('public')->put('proposal/dokumen.docx', 'isi-docx-bukan-pdf');
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['file_path' => 'proposal/dokumen.docx']);

    $this->actingAs($pengaju)->get(route('proposal.preview', $proposal))
        ->assertRedirect(route('proposal.download', $proposal))
        ->assertSessionHas('success', 'Preview tidak tersedia untuk tipe file ini. File diunduh sebagai gantinya.');
});

// --- 10: Gambar (jpg/png) ---

test('preview gambar jpg dan png mengembalikan inline dengan content-type image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('proposal/foto.jpg', pvIsiJpg());
    Storage::disk('public')->put('dokumen/diagram.png', pvIsiPng());

    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['file_path' => 'proposal/foto.jpg']);

    $response = $this->actingAs($pengaju)->get(route('proposal.preview', $proposal));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/jpeg');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');

    $admin = pvBuatUser('admin');
    $doc = AdminDocument::create([
        'judul' => 'Diagram Alur',
        'file_path' => 'dokumen/diagram.png',
        'uploaded_by' => $admin->id,
        'is_visible' => true,
    ]);

    $response = $this->actingAs($pengaju)->get(route('dokumen.preview', $doc));
    $response->assertOk();
    $response->assertHeader('Content-Type', 'image/png');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');
});

// --- 11: Review PDF ---

test('review pdf bisa di-preview inline oleh user login', function () {
    $pengaju = pvBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = pvBuatUser('reviewer', ['name' => 'Sinta']);
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['judul' => 'Proposal sudah direview']);
    $review = pvBuatReview($proposal, $reviewer);

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('review.preview', $review)));
});

// --- 12: Template ---

test('template pdf bisa di-preview inline, template docx mengarah ke download', function () {
    Storage::fake('public');
    pvSimpanPdf('templates/template.pdf');
    Storage::disk('public')->put('templates/template.docx', 'isi-docx-bukan-pdf');

    $templatePdf = Template::create([
        'nama_template' => 'Template Laporan.pdf',
        'file_path' => 'templates/template.pdf',
        'jenis' => 'laporan_kemajuan',
    ]);
    $templateDocx = Template::create([
        'nama_template' => 'Template Laporan.docx',
        'file_path' => 'templates/template.docx',
        'jenis' => 'laporan_akhir',
    ]);
    $pengaju = pvBuatUser('pengaju');

    pvAssertInlinePdf($this->actingAs($pengaju)->get(route('template.preview', $templatePdf)));

    $this->actingAs($pengaju)->get(route('template.preview', $templateDocx))
        ->assertRedirect(route('template.download', $templateDocx))
        ->assertSessionHas('success', 'Preview tidak tersedia untuk tipe file ini. File diunduh sebagai gantinya.');
});

// --- 13: Berkas fisik hilang ---

test('preview berkas fisik yang hilang kembali dengan pesan error', function () {
    Storage::fake('public');
    // Baris proposal ada, tetapi tidak ada file di storage.
    $pengaju = pvBuatUser('pengaju');
    $proposal = pvBuatProposal($pengaju, pvBuatSkema(), ['file_path' => 'proposal/hilang.pdf']);

    $this->actingAs($pengaju)->get(route('proposal.preview', $proposal))
        ->assertRedirect()
        ->assertSessionHas('error', 'File tidak ditemukan di server.');
});

test('preview dokumen resmi yang berkas fisiknya hilang kembali dengan pesan error', function () {
    Storage::fake('public');
    $dokumen = DokumenResmi::create([
        'judul' => 'Panduan Hibah',
        'kategori' => 'Panduan',
        'deskripsi' => 'Panduan resmi pengajuan hibah.',
        'file_path' => 'dokumen_resmi/hilang.pdf',
        'uploaded_by' => pvBuatUser('admin')->id,
    ]);
    $pengaju = pvBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('dokumen-resmi.preview', $dokumen))
        ->assertRedirect()
        ->assertSessionHas('error', 'Berkas fisik tidak ditemukan.');
});

// --- 14: Dua dokumen berbeda tidak tertukar (buka-tutup-buka lagi) ---

test('preview dua dokumen berbeda menampilkan isi masing-masing, bukan tertukar', function () {
    Storage::fake('public');
    // Marker unik per file, di dalam isi PDF palsu (tetap terdeteksi
    // application/pdf lewat magic "%PDF-").
    $markerPertama = 'MARKER-DOKUMEN-PERTAMA-7f3a';
    $markerKedua = 'MARKER-DOKUMEN-KEDUA-9b1c';
    Storage::disk('public')->put('dokumen_resmi/pertama.pdf', "%PDF-1.4\n{$markerPertama}\n%%EOF");
    Storage::disk('public')->put('dokumen_resmi/kedua.pdf', "%PDF-1.4\n{$markerKedua}\n%%EOF");

    $admin = pvBuatUser('admin');
    $pertama = DokumenResmi::create([
        'judul' => 'Panduan Pertama',
        'kategori' => 'Panduan',
        'deskripsi' => 'Dokumen pertama.',
        'file_path' => 'dokumen_resmi/pertama.pdf',
        'uploaded_by' => $admin->id,
    ]);
    $kedua = DokumenResmi::create([
        'judul' => 'Panduan Kedua',
        'kategori' => 'Panduan',
        'deskripsi' => 'Dokumen kedua.',
        'file_path' => 'dokumen_resmi/kedua.pdf',
        'uploaded_by' => $admin->id,
    ]);
    $pengaju = pvBuatUser('pengaju');

    // Buka preview dokumen pertama, lalu dokumen kedua — simulasi
    // buka, tutup, buka lagi untuk dokumen lain. Respons streamed
    // (Storage::response) dibaca lewat streamedContent(), bukan
    // getContent() yang kosong untuk streamed response.
    $responsPertama = $this->actingAs($pengaju)->get(route('dokumen-resmi.preview', $pertama));
    $responsPertama->assertOk();
    $isiPertama = $responsPertama->streamedContent();
    expect($isiPertama)->toContain($markerPertama)
        ->and($isiPertama)->not->toContain($markerKedua);

    $responsKedua = $this->actingAs($pengaju)->get(route('dokumen-resmi.preview', $kedua));
    $responsKedua->assertOk();
    $isiKedua = $responsKedua->streamedContent();
    expect($isiKedua)->toContain($markerKedua)
        ->and($isiKedua)->not->toContain($markerPertama);
});
