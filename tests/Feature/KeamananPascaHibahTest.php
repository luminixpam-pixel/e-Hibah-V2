<?php

use App\Models\AdminDocument;
use App\Models\Fakultas;
use App\Models\Logbook;
use App\Models\Luaran;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

// Semua helper di file ini diawali kh dan didefinisikan sendiri
// (jangan memakai helper dari file test lain — urutan load tidak dijamin).

function khBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function khBuatProposal(User $user, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal didanai',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'status' => 'Disetujui',
        'status_pendanaan' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function khBuatProposalMenyimpang(User $user, array $attrs = []): Proposal
{
    // Status alur 'Disetujui' tapi pendanaan belum diputuskan
    // (status_pendanaan 'Proses') — inilah kasus bug #10: guard yang
    // hanya melihat status alur akan salah mengizinkan.
    return khBuatProposal($user, array_merge([
        'status' => 'Disetujui',
        'status_pendanaan' => 'Proses',
    ], $attrs));
}

function khFilePdf(string $nama = 'laporan.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($nama, 100, 'application/pdf');
}

function khSimpanFileLaporanAkhir(Proposal $proposal): void
{
    Storage::disk('public')->put('laporan_akhir/akhir.pdf', 'isi laporan akhir');
    $proposal->update(['file_laporan_akhir' => 'laporan_akhir/akhir.pdf']);
}

function khSimpanFileLaporanKemajuan(Proposal $proposal): void
{
    Storage::disk('public')->put('laporan_kemajuan/kemajuan.pdf', 'isi laporan kemajuan');
    $proposal->update(['file_laporan' => 'laporan_kemajuan/kemajuan.pdf']);
}

/*
 |----------------------------------------------------------------------
 | Laporan akhir — store (celah utama: dulu tanpa cek kepemilikan)
 |----------------------------------------------------------------------
 */

test('pengaju lain ditolak 403 saat mengunggah laporan akhir untuk proposal orang lain', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $penyusup = khBuatUser('pengaju', ['name' => 'Budi Santoso']);
    $proposal = khBuatProposal($pemilik);

    $this->actingAs($penyusup)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ])->assertForbidden();

    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
    Storage::disk('public')->assertMissing('laporan_akhir/laporan.pdf');
});

test('reviewer ditolak 403 saat mengunggah laporan akhir untuk proposal orang lain', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $proposal = khBuatProposal($pemilik);
    khSimpanFileLaporanKemajuan($proposal);

    $this->actingAs(khBuatUser('reviewer'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ])->assertForbidden();

    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

test('admin boleh mengunggah laporan akhir atas nama pengaju bila sudah ada kemajuan (butir 5), ditolak bila belum ada (butir 1)', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $proposal = khBuatProposal($pemilik);

    // Belum ada laporan kemajuan: ditolak (redirect + error, bukan 403).
    $response = $this->actingAs(khBuatUser('admin'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();

    // Sesudah ada laporan kemajuan: admin boleh mengunggah.
    khSimpanFileLaporanKemajuan($proposal);
    $response = $this->actingAs(khBuatUser('admin'))->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();
});

test('pemilik ditolak saat mengunggah laporan akhir untuk proposal yang belum didanai', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju, [
        'status' => 'Review Selesai',
        'status_pendanaan' => 'Proses',
    ]);

    $response = $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan_akhir)->toBeNull();
});

test('pemilik berhasil mengunggah laporan akhir untuk proposal yang didanai', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);
    // Butir 1: laporan akhir wajib ada laporan kemajuan dulu.
    khSimpanFileLaporanKemajuan($proposal);

    $response = $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
        'keterangan' => 'Laporan final.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');
    expect($proposal->fresh()->file_laporan_akhir)->not->toBeNull();
    expect($proposal->fresh()->keterangan_akhir)->toBe('Laporan final.');
});

test('unggah laporan akhir dengan proposal_id yang tidak ada ditolak validasi', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => 999999,
        'file' => khFilePdf(),
    ])->assertSessionHasErrors('proposal_id');
});

test('unggah ulang laporan akhir mengarsipkan file lama sebagai riwayat versi, bukan menghapusnya (butir 3)', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);
    // Butir 1: laporan akhir wajib ada laporan kemajuan dulu.
    khSimpanFileLaporanKemajuan($proposal);
    khSimpanFileLaporanAkhir($proposal);

    $this->actingAs($pengaju)->post(route('laporan.akhir.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf('baru.pdf'),
    ])->assertRedirect();

    // PERUBAHAN PERILAKU dari 4fb1ddef: berkas lama TIDAK dihapus,
    // melainkan disimpan sebagai versi 1 di laporan_versi_berkas.
    Storage::disk('public')->assertExists('laporan_akhir/akhir.pdf');
    $this->assertDatabaseHas('laporan_versi_berkas', [
        'proposal_id' => $proposal->id,
        'jenis' => 'akhir',
        'file_path' => 'laporan_akhir/akhir.pdf',
        'nomor_versi' => 1,
    ]);
    expect($proposal->fresh()->file_laporan_akhir)->not->toBe('laporan_akhir/akhir.pdf');
});

/*
 |----------------------------------------------------------------------
 | Laporan akhir & kemajuan — download / preview
 |----------------------------------------------------------------------
 */

test('pengaju lain ditolak 403 saat mengunduh dan preview laporan akhir orang lain', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $orangLain = khBuatUser('pengaju', ['name' => 'Citra']);
    $proposal = khBuatProposal($pemilik);
    khSimpanFileLaporanAkhir($proposal);

    $this->actingAs($orangLain)->get(route('laporan.akhir.download', $proposal->id))->assertForbidden();
    $this->actingAs($orangLain)->get(route('laporan.akhir.preview', $proposal->id))->assertForbidden();
});

test('reviewer yang di-plot boleh mengunduh laporan akhir, reviewer lain tidak', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $proposal = khBuatProposal($pemilik);
    khSimpanFileLaporanAkhir($proposal);

    $reviewerTerplot = khBuatUser('reviewer');
    $proposal->reviewers()->attach($reviewerTerplot->id);
    $this->actingAs($reviewerTerplot)->get(route('laporan.akhir.download', $proposal->id))->assertOk();

    $reviewerLain = khBuatUser('reviewer');
    $this->actingAs($reviewerLain)->get(route('laporan.akhir.download', $proposal->id))->assertForbidden();
});

test('lppm boleh mengunduh laporan akhir, dekan fakultas lain tidak', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $proposal = khBuatProposal($pemilik);
    khSimpanFileLaporanAkhir($proposal);

    $this->actingAs(khBuatUser('lppm'))->get(route('laporan.akhir.download', $proposal->id))->assertOk();

    $fakultasLain = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    $dekanLain = khBuatUser('dekan', ['fakultas_id' => $fakultasLain->id]);
    $this->actingAs($dekanLain)->get(route('laporan.akhir.download', $proposal->id))->assertForbidden();
});

test('unduh laporan akhir dengan id yang tidak ada menghasilkan 404', function () {
    $pengaju = khBuatUser('pengaju');
    $this->actingAs($pengaju)->get(route('laporan.akhir.download', 999999))->assertNotFound();
    $this->actingAs($pengaju)->get(route('laporan.kemajuan.download', 999999))->assertNotFound();
});

test('pengaju lain ditolak 403 saat mengunduh dan preview laporan kemajuan orang lain', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $orangLain = khBuatUser('pengaju', ['name' => 'Dedi']);
    $proposal = khBuatProposal($pemilik);
    khSimpanFileLaporanKemajuan($proposal);

    $this->actingAs($orangLain)->get(route('laporan.kemajuan.download', $proposal->id))->assertForbidden();
    $this->actingAs($orangLain)->get(route('laporan.kemajuan.preview', $proposal->id))->assertForbidden();

    $this->actingAs($pemilik)->get(route('laporan.kemajuan.download', $proposal->id))->assertOk();
    $this->actingAs(khBuatUser('lppm'))->get(route('laporan.kemajuan.preview', $proposal->id))->assertOk();
});

test('pengaju lain ditolak saat mengunggah laporan kemajuan untuk proposal orang lain', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $orangLain = khBuatUser('pengaju', ['name' => 'Eka']);
    $proposal = khBuatProposal($pemilik);

    $response = $this->actingAs($orangLain)->post(route('laporan.kemajuan.store'), [
        'proposal_id' => $proposal->id,
        'file' => khFilePdf(),
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($proposal->fresh()->file_laporan)->toBeNull();
});

/*
 |----------------------------------------------------------------------
 | Logbook & luaran — guard status_pendanaan (bug #10)
 |----------------------------------------------------------------------
 */

test('logbook dan luaran ditolak untuk proposal yang status alurnya Disetujui tapi pendanaannya belum Disetujui', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposalMenyimpang($pengaju);

    $this->actingAs($pengaju)->get(route('logbook.show', $proposal))->assertForbidden();
    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Menyusup.',
    ])->assertForbidden();

    $this->actingAs($pengaju)->get(route('luaran.show', $proposal))->assertForbidden();
    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Jurnal menyusup.',
        'status' => 'Draft',
    ])->assertForbidden();

    $this->assertDatabaseCount('logbooks', 0);
    $this->assertDatabaseCount('luarans', 0);
});

test('logbook dan luaran tidak terlihat di index untuk proposal yang pendanaannya belum Disetujui', function () {
    $pengaju = khBuatUser('pengaju');
    khBuatProposalMenyimpang($pengaju, ['judul' => 'Proposal belum didanai']);

    $this->actingAs($pengaju)->get(route('logbook.index'))
        ->assertOk()
        ->assertDontSee('Proposal belum didanai');

    $this->actingAs($pengaju)->get(route('luaran.index'))
        ->assertOk()
        ->assertDontSee('Proposal belum didanai');
});

test('tanggal logbook di masa depan ditolak validasi', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);

    $this->actingAs($pengaju)->post(route('logbook.store', $proposal), [
        'tanggal' => now()->addDays(5)->toDateString(),
        'uraian_kegiatan' => 'Kegiatan masa depan.',
    ])->assertSessionHasErrors('tanggal');

    $this->assertDatabaseCount('logbooks', 0);
});

test('luaran Published dan Granted tanpa file ditolak, Draft tanpa file boleh', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Jurnal tanpa bukti.',
        'status' => 'Published',
    ])->assertSessionHasErrors('file');

    $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'HKI',
        'judul' => 'HKI tanpa bukti.',
        'status' => 'Granted',
    ])->assertSessionHasErrors('file');

    $this->assertDatabaseCount('luarans', 0);

    $response = $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Jurnal draf.',
        'status' => 'Draft',
    ]);
    $response->assertRedirect();
    $this->assertDatabaseCount('luarans', 1);
});

test('luaran Published dengan file berhasil, naik status ke Published tanpa file ditolak', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);

    $response = $this->actingAs($pengaju)->post(route('luaran.store', $proposal), [
        'jenis' => 'Jurnal',
        'judul' => 'Jurnal terbit.',
        'status' => 'Published',
        'file' => khFilePdf(),
    ]);
    $response->assertRedirect();
    $luaran = Luaran::where('proposal_id', $proposal->id)->firstOrFail();
    expect($luaran->file)->not->toBeNull();

    // Luaran kedua: draf tanpa file, lalu coba naik ke Published tanpa file.
    $draf = Luaran::create([
        'proposal_id' => $proposal->id,
        'jenis' => 'Buku',
        'judul' => 'Buku draf.',
        'status' => 'Draft',
    ]);

    $this->actingAs($pengaju)->put(route('luaran.update', $draf), [
        'jenis' => 'Buku',
        'judul' => 'Buku draf.',
        'status' => 'Published',
    ])->assertSessionHasErrors('file');

    expect($draf->fresh()->status)->toBe('Draft');
});

test('hapus logbook dan luaran ditolak untuk proposal yang pendanaannya sudah tidak Disetujui', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);
    $logbook = Logbook::create([
        'proposal_id' => $proposal->id,
        'tanggal' => '2026-11-20',
        'uraian_kegiatan' => 'Entri lama.',
    ]);
    $luaran = Luaran::create([
        'proposal_id' => $proposal->id,
        'jenis' => 'Jurnal',
        'judul' => 'Luaran lama.',
        'status' => 'Draft',
    ]);

    // Pendanaan dicabut setelah entri dibuat.
    $proposal->update(['status_pendanaan' => 'Ditolak', 'status' => 'Ditolak']);

    $this->actingAs($pengaju)->delete(route('logbook.destroy', $logbook))->assertForbidden();
    $this->actingAs($pengaju)->delete(route('luaran.destroy', $luaran))->assertForbidden();
    $this->assertDatabaseHas('logbooks', ['id' => $logbook->id]);
    $this->assertDatabaseHas('luarans', ['id' => $luaran->id]);
});

/*
 |----------------------------------------------------------------------
 | Monev — guard status_pendanaan + verifikasi
 |----------------------------------------------------------------------
 */

test('lppm ditolak membuat temuan untuk proposal yang pendanaannya belum Disetujui', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposalMenyimpang($pengaju);
    $lppm = khBuatUser('lppm');

    $response = $this->actingAs($lppm)->post(route('monev.store', $proposal), [
        'uraian_temuan' => 'Temuan.',
        'rekomendasi' => 'Perbaiki.',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');
    $this->assertDatabaseCount('monev_findings', 0);
});

test('monev tidak terlihat di index untuk proposal yang pendanaannya belum Disetujui', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposalMenyimpang($pengaju);
    MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan lama.',
        'rekomendasi' => 'Perbaiki.',
        'status_temuan' => 'Open',
    ]);

    $this->actingAs(khBuatUser('lppm'))->get(route('monev.index'))
        ->assertOk()
        ->assertDontSee('Temuan lama.');
});

test('verifikasi monev hanya oleh lppm dan hanya untuk temuan In_Review', function () {
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);
    $finding = MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan.',
        'rekomendasi' => 'Perbaiki.',
        'status_temuan' => 'Open',
    ]);

    // Bukan lppm: 403 (route memakai middleware role:lppm).
    $this->actingAs($pengaju)->post(route('monev.verifikasi', $finding), [
        'keputusan' => 'tutup',
    ])->assertForbidden();

    // LPPM tapi temuan masih Open (belum ada RTL): ditolak dengan error.
    $response = $this->actingAs(khBuatUser('lppm'))->post(route('monev.verifikasi', $finding), [
        'keputusan' => 'tutup',
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($finding->fresh()->status_temuan)->toBe('Open');
});

test('kirim RTL oleh pengaju bukan pemilik ditolak 403', function () {
    Storage::fake('public');
    $pemilik = khBuatUser('pengaju');
    $orangLain = khBuatUser('pengaju', ['name' => 'Fajar']);
    $proposal = khBuatProposal($pemilik);
    $finding = MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan.',
        'rekomendasi' => 'Perbaiki.',
        'status_temuan' => 'Open',
    ]);

    $this->actingAs($orangLain)->post(route('monev.kirimRtl', $finding), [
        'file_rtl' => khFilePdf('rtl.pdf'),
        'bukti_perbaikan' => khFilePdf('bukti.pdf'),
    ])->assertForbidden();

    expect($finding->fresh()->status_temuan)->toBe('Open');
});

test('verifikasi monev ditolak bila proposal sudah tidak didanai', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $proposal = khBuatProposal($pengaju);
    $finding = MonevFinding::create([
        'proposal_id' => $proposal->id,
        'uraian_temuan' => 'Temuan.',
        'rekomendasi' => 'Perbaiki.',
        'status_temuan' => 'In_Review',
        'file_rtl' => 'monev_rtl/rtl.pdf',
        'bukti_perbaikan' => 'monev_bukti/bukti.pdf',
    ]);

    $proposal->update(['status_pendanaan' => 'Ditolak', 'status' => 'Ditolak']);

    $response = $this->actingAs(khBuatUser('lppm'))->post(route('monev.verifikasi', $finding), [
        'keputusan' => 'tutup',
    ]);
    $response->assertRedirect();
    $response->assertSessionHas('error');
    expect($finding->fresh()->status_temuan)->toBe('In_Review');
});

/*
 |----------------------------------------------------------------------
 | Dokumen admin & template — cek role di controller
 |----------------------------------------------------------------------
 */

test('non-admin ditolak 403 saat simpan, toggle, dan hapus dokumen admin', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $doc = AdminDocument::create([
        'judul' => 'Panduan.',
        'file_path' => 'admin_docs/panduan.pdf',
        'uploaded_by' => 1,
        'is_visible' => true,
    ]);

    $this->actingAs($pengaju)->post(route('admin.dokumen.store'), [
        'judul' => 'Dokumen menyusup.',
        'file' => khFilePdf(),
    ])->assertForbidden();

    $this->actingAs($pengaju)->patch(route('admin.dokumen.toggle', $doc->id))->assertForbidden();
    $this->actingAs($pengaju)->delete(route('admin.dokumen.destroy', $doc->id))->assertForbidden();

    $this->assertDatabaseCount('admin_documents', 1);
    expect($doc->fresh()->is_visible)->toBeTrue();
});

test('dokumen admin yang disembunyikan tidak bisa diunduh non-admin', function () {
    Storage::fake('public');
    Storage::disk('public')->put('admin_docs/rahasia.pdf', 'isi');
    $doc = AdminDocument::create([
        'judul' => 'Rahasia.',
        'file_path' => 'admin_docs/rahasia.pdf',
        'uploaded_by' => 1,
        'is_visible' => false,
    ]);

    $this->actingAs(khBuatUser('pengaju'))->get(route('dokumen.download', $doc->id))->assertForbidden();
    $this->actingAs(khBuatUser('pengaju'))->get(route('dokumen.preview', $doc->id))->assertForbidden();
    $this->actingAs(khBuatUser('admin'))->get(route('dokumen.download', $doc->id))->assertOk();
});

test('admin yang mengunggah dokumen tercatat dengan id numerik, bukan username', function () {
    Storage::fake('public');
    $admin = khBuatUser('admin');

    $response = $this->actingAs($admin)->post(route('admin.dokumen.store'), [
        'judul' => 'Panduan baru.',
        'file' => khFilePdf(),
    ]);
    $response->assertRedirect();

    $doc = AdminDocument::where('judul', 'Panduan baru.')->firstOrFail();
    expect((int) $doc->uploaded_by)->toBe((int) $admin->id);
});

test('non-admin ditolak 403 saat unggah dan hapus template', function () {
    Storage::fake('public');
    $pengaju = khBuatUser('pengaju');
    $template = Template::create([
        'nama_template' => 'lama.docx',
        'file_path' => 'templates/lama.docx',
        'jenis' => 'laporan_akhir',
    ]);

    $this->actingAs($pengaju)->post(route('admin.template.upload'), [
        'file_template' => UploadedFile::fake()->create('baru.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'jenis' => 'laporan_akhir',
    ])->assertForbidden();

    $this->actingAs($pengaju)->delete(route('admin.template.destroy', $template->id))->assertForbidden();
    $this->assertDatabaseHas('templates', ['id' => $template->id]);
});

test('unggah template dengan jenis yang tidak dikenal ditolak validasi', function () {
    Storage::fake('public');
    $admin = khBuatUser('admin');

    $this->actingAs($admin)->post(route('admin.template.upload'), [
        'file_template' => UploadedFile::fake()->create('x.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        'jenis' => 'jenis_ngawur',
    ])->assertSessionHasErrors('jenis');

    $this->assertDatabaseCount('templates', 0);
});

test('nama file template hasil traversal dibersihkan sebelum disimpan', function () {
    Storage::fake('public');
    $admin = khBuatUser('admin');

    $file = UploadedFile::fake()->create('template.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    // Simulasikan nama asli berisi traversal (client bisa mengirimnya).
    $file = new UploadedFile(
        $file->getPathname(),
        '../../jahat.docx',
        $file->getMimeType(),
        null,
        true
    );

    $this->actingAs($admin)->post(route('admin.template.upload'), [
        'file_template' => $file,
        'jenis' => 'laporan_akhir',
    ])->assertRedirect();

    $template = Template::where('jenis', 'laporan_akhir')->firstOrFail();
    expect($template->nama_template)->not->toContain('..');
    expect($template->nama_template)->not->toContain('/');
    expect($template->file_path)->toStartWith('templates/');
    Storage::disk('public')->assertExists($template->file_path);
});
