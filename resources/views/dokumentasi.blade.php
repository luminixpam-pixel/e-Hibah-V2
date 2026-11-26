@extends('layouts.app')

@section('title', 'Dokumentasi Teknis')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dokumentasi Teknis E-Hibah</h4>
        <p class="text-muted small mb-0">Ringkasan teknis sistem untuk pengelola dan pengembang. Dokumen lengkap proyek ada di README.md repo.</p>
    </div>

    <h5 class="fw-bold mt-4">Gambaran Sistem</h5>
    <p>
        E-Hibah adalah sistem informasi pengelolaan hibah penelitian dan pengabdian kepada masyarakat
        Universitas YARSI. Sistem mengelola alur pengajuan proposal, verifikasi administrasi, penugasan
        reviewer, penilaian berbobot, penetapan keputusan, sampai pelaporan pasca-hibah (logbook, luaran,
        dan Monev/RTL).
    </p>
    <p>
        Teknologi: Laravel 12, PHP 8.2+, MySQL, Filament 3.3, Jetstream + Fortify + Sanctum, LDAP
        (login dosen memakai akun kampus YARSI), Blade + Tailwind CSS 4 + Vite, Dompdf untuk cetak PDF.
        Antarmuka memakai Blade server-rendered; tidak ada endpoint API baru di luar
        <code>GET /api/notifications</code> yang sudah ada.
    </p>

    <h5 class="fw-bold mt-4">Peran Pengguna (7 Role)</h5>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Role</th>
                <th>Tugas Utama</th>
            </tr>
        </thead>
        <tbody>
            <tr><td>admin</td><td>Administrator TI: kelola pengguna dan peran, kelola reviewer, kelola dokumen dan templat, menetapkan keputusan proposal.</td></tr>
            <tr><td>pengaju</td><td>Dosen/ketua pengusul: membuat proposal (draft lalu submit), mengundang anggota tim, mengisi logbook, mengunggah luaran dan laporan, mengirim RTL atas temuan Monev.</td></tr>
            <tr><td>reviewer</td><td>Menilai proposal yang ditugaskan dengan rubrik 7 kriteria berbobot (skor maks 190, skala 500) beserta rekomendasi biaya.</td></tr>
            <tr><td>verifikator</td><td>Memeriksa kelengkapan administrasi proposal: Lolos Administrasi atau Revisi Administrasi beserta alasan.</td></tr>
            <tr><td>lppm</td><td>Pengelola LPPM: plotting reviewer (2 reviewer + tenggat), memantau progres review, membuat temuan Monev, menerbitkan pengumuman, mengelola master data.</td></tr>
            <tr><td>dekan</td><td>Pimpinan fakultas: melihat laporan akhir dosen di fakultasnya.</td></tr>
            <tr><td>kepala_lppm</td><td>Kepala LPPM: dasbor analitik ringkasan per status dan per periode, memantau keputusan.</td></tr>
        </tbody>
    </table>
    <p class="small text-muted">
        Peran disimpan di satu kolom <code>users.role</code> dan ditegakkan lewat middleware
        <code>role:&lt;role&gt;</code> di route. Akun tes dibuat oleh <code>AkunRoleSeeder</code>
        (satu akun per role); daftar lengkapnya ada di README.md repo — halaman ini sengaja tidak
        memuat kredensial apa pun.
    </p>

    <h5 class="fw-bold mt-4">Alur Utama</h5>
    <ol>
        <li><strong>Pengajuan</strong> — pengaju memilih skema, mengisi RAB per komponen (total otomatis, divalidasi terhadap plafon skema), menyimpan draft, lalu submit dan menerima nomor pengajuan. Kuota per kategori skema dan status blacklist diperiksa saat submit.</li>
        <li><strong>Verifikasi</strong> — verifikator memeriksa proposal yang berstatus Dikirim; hasilnya Lolos Administrasi atau Revisi Administrasi (alasan wajib). Pengaju dapat mengirim ulang hasil revisi tanpa memakan kuota baru.</li>
        <li><strong>Plotting</strong> — LPPM menugaskan 2 reviewer beserta tenggat pada proposal yang lolos administrasi; pencocokan memberi skor berdasarkan bidang keahlian dan fakultas yang sama, serta mengecualikan pengusul dan anggota tim.</li>
        <li><strong>Review</strong> — reviewer menilai dengan rubrik 7 kriteria dan mengisi rekomendasi biaya; LPPM memantau progres (jumlah review masuk dari yang ditugaskan).</li>
        <li><strong>Keputusan</strong> — admin menetapkan Disetujui / Ditolak / Direvisi; riwayat keputusan tercatat di activity log.</li>
        <li><strong>Pasca-hibah</strong> — proposal yang Disetujui dilanjutkan dengan logbook, luaran, laporan kemajuan/akhir, dan temuan Monev (Open &rarr; In_Review &rarr; Closed) beserta RTL dan bukti perbaikan.</li>
    </ol>

    <h5 class="fw-bold mt-4">Cara Menjalankan Test</h5>
    <p>Jalankan dari root repo (memakai Pest):</p>
    <pre class="bg-light p-3 rounded"><code>php artisan test --compact
php artisan test --filter=PengajuanProposalTest
php artisan test --filter=VerifikasiDanPlottingTest
php artisan test --filter=LogbookTest
php artisan test --filter=LuaranTest
php artisan test --filter=MonevTest
php artisan test --filter=PengumumanTest
php artisan test --filter=ErrorReportTest
php artisan test --filter=DokumentasiTest</code></pre>
    <p class="small text-muted">
        Test memakai <code>RefreshDatabase</code> dengan SQLite in-memory. Setelah mengubah file PHP,
        jalankan <code>vendor/bin/pint --dirty</code> untuk merapikan format kode.
    </p>

    <h5 class="fw-bold mt-4">Catatan Penting</h5>
    <ul>
        <li><strong>Jangan pakai <code>Auth::id()</code> / <code>auth()-&gt;id()</code> untuk membandingkan ID angka.</strong> Di codebase ini method tersebut mengembalikan username (string), bukan <code>users.id</code>, karena <code>User::getAuthIdentifierName()</code> mengembalikan <code>username</code> (login LDAP). Selalu pakai <code>auth()-&gt;user()-&gt;id</code> untuk cek kepemilikan.</li>
        <li><strong>Data dummy SkemaSeeder menunggu LPPM.</strong> Plafon dan kuota skema Pembinaan / Kompetisi / Khusus di seeder masih angka dummy bertanda <code>[DUMMY]</code> dan wajib diganti dengan angka resmi dari SK / panduan internal LPPM begitu jawabannya ada; jangan dipakai untuk validasi produksi.</li>
        <li><strong>File <code>.env</code> tidak boleh di-commit.</strong> Database SQLite dan file sensitif lain tidak boleh masuk repo.</li>
        <li><strong>Login LDAP.</strong> Akun asli dosen YARSI login lewat LDAP memakai akun kampus; saat login pertama dan belum punya peran, sistem memberi peran <code>pengaju</code> — peran sesuai jabatan diatur admin lewat menu Kelola Pengguna.</li>
        <li><strong>Kuota.</strong> Setiap dosen maksimal terlibat dalam 3 proposal (sebagai ketua atau anggota), sesuai kuota per kategori skema.</li>
    </ul>
</div>
@endsection
