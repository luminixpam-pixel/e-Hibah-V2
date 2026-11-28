<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminDocumentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenResmiController;
use App\Http\Controllers\ErrorReportController;
use App\Http\Controllers\FakultasController;
use App\Http\Controllers\LaporanAkhirController;
use App\Http\Controllers\LaporanKemajuanController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\LuaranController;
use App\Http\Controllers\MonevController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\ProposalMemberController;
use App\Http\Controllers\ProposalReviewerController;
use App\Http\Controllers\ReviewerController;
use App\Http\Controllers\RoleDashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SkemaController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| E-Hibah — routes/web.php (VERSI RAPI)
|--------------------------------------------------------------------------
| Ditulis ulang dari routes/web.php lama (281 baris, 96 route) yang
| penuh duplikat: 11 URI ganda, 15 nama route ganda, 8 route memanggil
| method yang tidak ada, 1 blok notifikasi tanpa middleware auth,
| dan middleware('admin') tanpa parameter (= 403 untuk semua orang).
|
| Prinsip versi ini: SATU route = SATU definisi. Tidak ada lagi
| route yang sama didaftarkan dua kali, dan semua method yang
| dipanggil sudah ada di controller-nya.
|
| Perubahan dibanding file lama:
|  - Blok notifikasi digabung jadi SATU (dulu 3 blok, satu tanpa auth).
|  - Blok dokumen admin digabung jadi SATU dengan role:admin
|    (dulu ada 2, satu pakai middleware('admin') yang selalu 403).
|  - Blok reviewer admin digabung jadi SATU dengan role:admin.
|  - Route '/proposal-perlu-direview' hanya SATU, memanggil
|    ProposalController@perluDireview (dulu ada 2 dengan controller
|    berbeda, dan yang satu memanggil method yang tidak ada).
|  - Grup '/monitoring' ber-prefix DIHAPUS — isinya duplikat dari
|    route monitoring flat (/proposal-disetujui, /daftar-proposal,
|    dst.) yang sudah dipakai semua view.
|  - Route '/review/{id}/isi' dan '/review/{id}/submit' DIHAPUS —
|    duplikat dari '/reviewer/isi-review/{id}'.
|  - Route POST '/mark-as-read' DIHAPUS — duplikat nama
|    notifications.markAllAsRead dari '/notifications/mark-all-read'.
|  - Route '/notifications/mark-all-read-ajax' DIHAPUS — endpoint
|    ini sengaja bypass CSRF. Pakai '/notifications/mark-all-read'
|    dengan token CSRF (bawaan Laravel) dari JavaScript.
|  - Method yang tidak ada DIPERBAIKI namanya:
|      proposalPerluDireview -> perluDireview
|      ReviewerController@remove -> removeReviewer
|      ReviewerController@set -> setReviewer
|    dan method yang belum ada DIBUAT di controller-nya:
|      ProposalController@create, @pengumumanPendanaan, @cekKuota
|      AdminDocumentController@destroy
|  - Route PUT '/user/profile/{user}' hanya SATU (dulu 2 baris identik).
|  - Ditambah 7 route dashboard per role (RoleDashboardController).
|--------------------------------------------------------------------------
*/

/* --- PUBLIC ROUTES --- */

// Halaman utama / Landing Page E-Hibah
Route::view('/', 'landing.index')->name('landing');

// Login
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Forgot Password
Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

/* --- AUTH ROUTES (ALL USERS) --- */
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard — dialihkan ke dashboard sesuai role
    Route::get('/dashboard', [RoleDashboardController::class, 'home'])->name('dashboard');

    // Dashboard per role (satu dashboard untuk tiap role).
    // Middleware role: hanya role yang cocok yang bisa membukanya.
    Route::get('/dashboard/admin', [RoleDashboardController::class, 'admin'])
        ->middleware('role:admin')->name('dashboard.admin');
    Route::get('/dashboard/pengaju', [RoleDashboardController::class, 'pengaju'])
        ->middleware('role:pengaju')->name('dashboard.pengaju');
    Route::get('/dashboard/reviewer', [RoleDashboardController::class, 'reviewer'])
        ->middleware('role:reviewer')->name('dashboard.reviewer');
    Route::get('/dashboard/verifikator', [RoleDashboardController::class, 'verifikator'])
        ->middleware('role:verifikator')->name('dashboard.verifikator');
    Route::get('/dashboard/lppm', [RoleDashboardController::class, 'lppm'])
        ->middleware('role:lppm')->name('dashboard.lppm');
    Route::get('/dashboard/dekan', [RoleDashboardController::class, 'dekan'])
        ->middleware('role:dekan')->name('dashboard.dekan');
    Route::get('/dashboard/kepala-lppm', [RoleDashboardController::class, 'kepalaLppm'])
        ->middleware('role:kepala_lppm')->name('dashboard.kepala-lppm');

    // Profil (dashboard lama tetap bisa dipakai lewat DashboardController)
    Route::post('/dashboard/update', [DashboardController::class, 'updateProfile'])->name('dashboard.updateProfile');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // Monitoring & Info Umum
    Route::get('/monitoring-kalender', [CalendarController::class, 'index'])->name('monitoring.kalender');
    Route::get('/pengumuman-pendanaan', [ProposalController::class, 'pengumumanPendanaan'])->name('proposal.pengumuman');
    Route::get('/cek-kuota-dosen', [ProposalController::class, 'cekKuota'])->name('dosen.cek-kuota');

    // Undangan keanggotaan tim (konfirmasi Setuju / Tolak oleh anggota).
    Route::get('/anggota/undangan', [ProposalMemberController::class, 'index'])->name('anggota.undangan');
    Route::post('/anggota/undangan/{id}/konfirmasi', [ProposalMemberController::class, 'confirm'])->name('anggota.konfirmasi');

    // Detail proposal yang diikuti sebagai anggota tim (read-only).
    Route::get('/anggota/proposal/{id}', [ProposalMemberController::class, 'show'])->name('anggota.proposal.show');

    // Verifikasi administrasi (formulir pemeriksaan oleh verifikator).
    Route::post('/verifikasi/{proposal}', [VerificationController::class, 'store'])
        ->middleware('role:verifikator')->name('verifikasi.store');

    // Proposal Actions (General)
    Route::controller(ProposalController::class)->group(function () {
        Route::get('/proposal', 'index')->name('proposal.index');
        Route::get('/daftar-proposal', 'index')->name('monitoring.proposalDikirim');
        Route::get('/proposal/{id}/edit', 'edit')->name('proposal.edit');
        Route::put('/proposal/{id}', 'update')->name('proposal.update');
        Route::get('/proposal/download/{id}', 'download')->name('proposal.download');
        Route::get('/proposal/preview/{id}', 'preview')->name('proposal.preview');
        Route::delete('/proposal/{id}', 'destroy')->name('proposal.destroy');
        Route::get('/proposal/{id}/tinjau', 'tinjau')->name('proposal.tinjau');
        Route::patch('/proposal/{id}/keputusan', 'keputusan')->middleware('role:admin')->name('proposal.keputusan');

        // Monitoring Statuses
        Route::get('/proposal-disetujui', 'proposalDisetujui')->name('monitoring.proposalDisetujui');
        Route::get('/proposal-ditolak', 'proposalDitolak')->name('monitoring.proposalDitolak');
        Route::get('/proposal-selesai', 'reviewSelesai')->name('monitoring.reviewSelesai');
        Route::get('/proposal-direvisi', 'proposalDirevisi')->name('monitoring.proposalDirevisi');
        Route::get('/hasil-review', 'hasilRevisi')->name('monitoring.hasilRevisi');
        Route::get('/review/{id}/pdf', 'downloadReviewPdf')->name('review.pdf');
        Route::get('/review/{id}/preview', 'previewReviewPdf')->name('review.preview');
    });

    // Laporan (Kemajuan & Akhir)
    Route::get('/laporan-kemajuan', [LaporanKemajuanController::class, 'index'])->name('laporan.kemajuan.index');
    Route::post('/laporan-kemajuan', [LaporanKemajuanController::class, 'store'])->name('laporan.kemajuan.store');
    Route::get('/laporan-kemajuan/download/{id}', [LaporanKemajuanController::class, 'downloadLaporan'])->name('laporan.kemajuan.download');
    Route::get('/laporan-kemajuan/preview/{id}', [LaporanKemajuanController::class, 'preview'])->name('laporan.kemajuan.preview');
    // Butir 4 (keputusan audit 28 Nov 2026): verifikasi oleh LPPM
    // (pola Monev) + hapus laporan kemajuan (ketua/admin, belum
    // Terverifikasi).
    Route::post('/laporan-kemajuan/{id}/verifikasi', [LaporanKemajuanController::class, 'verifikasi'])->middleware('role:lppm')->name('laporan.kemajuan.verifikasi');
    Route::delete('/laporan-kemajuan/{id}', [LaporanKemajuanController::class, 'destroy'])->name('laporan.kemajuan.destroy');
    // Butir 3: unduh satu berkas versi lama (kemajuan/akhir).
    Route::get('/laporan-versi/{versi}/download', [LaporanKemajuanController::class, 'downloadVersi'])->name('laporan.versi.download');

    Route::get('/laporan-akhir', [LaporanAkhirController::class, 'index'])->name('laporan.akhir.index');
    Route::post('/laporan-akhir/store', [LaporanAkhirController::class, 'store'])->name('laporan.akhir.store');
    Route::get('/laporan-akhir/download/{id}', [LaporanAkhirController::class, 'download'])->name('laporan.akhir.download');
    Route::get('/laporan-akhir/preview/{id}', [LaporanAkhirController::class, 'preview'])->name('laporan.akhir.preview');
    // Butir 4 (keputusan audit 28 Nov 2026): verifikasi oleh LPPM
    // (pola Monev) + hapus laporan akhir (ketua/admin, belum
    // Terverifikasi).
    Route::post('/laporan-akhir/{id}/verifikasi', [LaporanAkhirController::class, 'verifikasi'])->middleware('role:lppm')->name('laporan.akhir.verifikasi');
    Route::delete('/laporan-akhir/{id}', [LaporanAkhirController::class, 'destroy'])->name('laporan.akhir.destroy');

    // Pasca-hibah (Fase 5, story 9 & 10) — hanya proposal Disetujui.
    // Otorisasi per role dicek di controller (pemilik / LPPM / dekan /
    // kepala_lppm / admin); store + verifikasi Monev khusus LPPM.
    Route::get('/logbook', [LogbookController::class, 'index'])->name('logbook.index');
    Route::get('/logbook/{proposal}', [LogbookController::class, 'show'])->name('logbook.show');
    Route::post('/logbook/{proposal}', [LogbookController::class, 'store'])->name('logbook.store');
    Route::get('/logbook/{logbook}/edit', [LogbookController::class, 'edit'])->name('logbook.edit');
    Route::put('/logbook/{logbook}', [LogbookController::class, 'update'])->name('logbook.update');
    Route::delete('/logbook/{logbook}', [LogbookController::class, 'destroy'])->name('logbook.destroy');
    Route::get('/logbook/{logbook}/download', [LogbookController::class, 'download'])->name('logbook.download');
    Route::get('/logbook/{logbook}/preview', [LogbookController::class, 'preview'])->name('logbook.preview');

    Route::get('/luaran', [LuaranController::class, 'index'])->name('luaran.index');
    Route::get('/luaran/{proposal}', [LuaranController::class, 'show'])->name('luaran.show');
    Route::post('/luaran/{proposal}', [LuaranController::class, 'store'])->name('luaran.store');
    Route::get('/luaran/{luaran}/edit', [LuaranController::class, 'edit'])->name('luaran.edit');
    Route::put('/luaran/{luaran}', [LuaranController::class, 'update'])->name('luaran.update');
    Route::delete('/luaran/{luaran}', [LuaranController::class, 'destroy'])->name('luaran.destroy');
    Route::get('/luaran/{luaran}/download', [LuaranController::class, 'download'])->name('luaran.download');
    Route::get('/luaran/{luaran}/preview', [LuaranController::class, 'preview'])->name('luaran.preview');

    Route::get('/monev', [MonevController::class, 'index'])->name('monev.index');
    Route::get('/monev/{proposal}', [MonevController::class, 'show'])->name('monev.show');
    Route::post('/monev/{proposal}', [MonevController::class, 'store'])
        ->middleware('role:lppm')->name('monev.store');
    Route::post('/monev/{finding}/rtl', [MonevController::class, 'kirimRtl'])->name('monev.kirimRtl');
    Route::post('/monev/{finding}/verifikasi', [MonevController::class, 'verifikasi'])
        ->middleware('role:lppm')->name('monev.verifikasi');
    Route::get('/monev/{finding}/download/{jenis}', [MonevController::class, 'download'])->name('monev.download');
    Route::get('/monev/{finding}/preview/{jenis}', [MonevController::class, 'preview'])->name('monev.preview');

    // Dokumen (lihat & unduh — semua role yang login)
    Route::get('/dokumen', [AdminDocumentController::class, 'index'])->name('dokumen.index');
    Route::get('/dokumen/download/{id}', [AdminDocumentController::class, 'download'])->name('dokumen.download');
    Route::get('/dokumen/preview/{id}', [AdminDocumentController::class, 'preview'])->name('dokumen.preview');

    // Dokumen resmi — unduh (Fase 6, story 8)
    Route::get('/dokumen-resmi/{id}/download', [DokumenResmiController::class, 'download'])->name('dokumen-resmi.download');
    Route::get('/dokumen-resmi/{id}/preview', [DokumenResmiController::class, 'preview'])->name('dokumen-resmi.preview');

    // Dokumentasi teknis (Fase 6, story 30)
    Route::view('/dokumentasi', 'dokumentasi')->name('dokumentasi');

    // Pelaporan error — form untuk semua user login (Fase 6, story 30)
    Route::get('/laporan-error/buat', [ErrorReportController::class, 'create'])->name('error-report.create');
    Route::post('/laporan-error', [ErrorReportController::class, 'store'])->name('error-report.store');

    // Notifikasi — SATU definisi saja, semua di belakang auth
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/fetch', [NotificationController::class, 'fetch'])->name('fetch');
        Route::get('/count', [NotificationController::class, 'count'])->name('count');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('markAllAsRead');
        Route::get('/deadline-check', [NotificationController::class, 'deadlineCheck'])->name('deadlineCheck');
        Route::post('/send-to-all', [NotificationController::class, 'sendToAll'])->name('sendToAll');
        Route::post('/send-to-user', [NotificationController::class, 'sendToUser'])->name('sendToUser');
    });

    // Notifikasi API (dipakai JavaScript)
    Route::get('/api/notifications', function () {
        $userId = auth()->user()->id;

        return \App\Models\Notification::where('user_id', $userId)
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($n) {
                return [
                    'title' => $n->title,
                    'message' => $n->message,
                    'is_read' => (bool) $n->is_read,
                    'time_ago' => $n->created_at->diffForHumans(),
                ];
            });
    });

    // Update profil user (dipakai modal edit admin).
    // CATATAN KEAMANAN (temuan lama, belum diperbaiki): method
    // AuthController@update tidak mengecek kepemilikan — user yang
    // login bisa mengubah profil user lain lewat ID di URL.
    // Perbaiki di AuthController@update bila sudah siap.
    Route::put('/user/profile/{user}', [AuthController::class, 'update'])->name('admin.user.update');

    // Penugasan reviewer oleh admin (pencarian)
    Route::prefix('admin/proposal')->group(function () {
        Route::get('/search-reviewer', [ProposalReviewerController::class, 'search'])->name('admin.proposal.search-reviewer');
    });

    // Plotting reviewer oleh LPPM (Fase 3, story 16 + 19).
    Route::get('/lppm/plotting', [ProposalReviewerController::class, 'plotting'])
        ->middleware('role:lppm')->name('lppm.plotting');

    // Tetapkan 2 reviewer + tenggat (dipakai admin & LPPM).
    Route::post('/proposal/{id}/assign-reviewer', [ProposalController::class, 'assignReviewer'])
        ->middleware('role:admin,lppm')->name('proposal.assignReviewer');

    // Master data LPPM + pengumuman + laporan error (Fase 6) — admin & LPPM.
    Route::middleware('role:admin,lppm')->group(function () {
        // Master data (story 15)
        Route::get('/admin/skema', [SkemaController::class, 'index'])->name('admin.skema.index');
        Route::get('/admin/skema/create', [SkemaController::class, 'create'])->name('admin.skema.create');
        Route::post('/admin/skema', [SkemaController::class, 'store'])->name('admin.skema.store');
        Route::get('/admin/skema/{skema}/edit', [SkemaController::class, 'edit'])->name('admin.skema.edit');
        Route::put('/admin/skema/{skema}', [SkemaController::class, 'update'])->name('admin.skema.update');
        Route::delete('/admin/skema/{skema}', [SkemaController::class, 'destroy'])->name('admin.skema.destroy');

        Route::get('/admin/periode', [PeriodeController::class, 'index'])->name('admin.periode.index');
        Route::get('/admin/periode/create', [PeriodeController::class, 'create'])->name('admin.periode.create');
        Route::post('/admin/periode', [PeriodeController::class, 'store'])->name('admin.periode.store');
        Route::get('/admin/periode/{periode}/edit', [PeriodeController::class, 'edit'])->name('admin.periode.edit');
        Route::put('/admin/periode/{periode}', [PeriodeController::class, 'update'])->name('admin.periode.update');
        Route::delete('/admin/periode/{periode}', [PeriodeController::class, 'destroy'])->name('admin.periode.destroy');

        Route::get('/admin/fakultas', [FakultasController::class, 'index'])->name('admin.fakultas.index');
        Route::get('/admin/fakultas/create', [FakultasController::class, 'create'])->name('admin.fakultas.create');
        Route::post('/admin/fakultas', [FakultasController::class, 'store'])->name('admin.fakultas.store');
        Route::get('/admin/fakultas/{fakultas}/edit', [FakultasController::class, 'edit'])->name('admin.fakultas.edit');
        Route::put('/admin/fakultas/{fakultas}', [FakultasController::class, 'update'])->name('admin.fakultas.update');
        Route::delete('/admin/fakultas/{fakultas}', [FakultasController::class, 'destroy'])->name('admin.fakultas.destroy');

        // Pengumuman (story 18)
        Route::get('/admin/pengumuman', [PengumumanController::class, 'index'])->name('admin.pengumuman.index');
        Route::get('/admin/pengumuman/buat', [PengumumanController::class, 'create'])->name('admin.pengumuman.create');
        Route::post('/admin/pengumuman', [PengumumanController::class, 'store'])->name('admin.pengumuman.store');
        Route::get('/admin/pengumuman/{id}/edit', [PengumumanController::class, 'edit'])->name('admin.pengumuman.edit');
        Route::put('/admin/pengumuman/{id}', [PengumumanController::class, 'update'])->name('admin.pengumuman.update');
        Route::delete('/admin/pengumuman/{id}', [PengumumanController::class, 'destroy'])->name('admin.pengumuman.destroy');

        // Laporan error — daftar, detail, ubah status (story 30)
        Route::get('/admin/laporan-error', [ErrorReportController::class, 'index'])->name('admin.error-report.index');
        Route::get('/admin/laporan-error/{id}', [ErrorReportController::class, 'show'])->name('admin.error-report.show');
        Route::patch('/admin/laporan-error/{id}', [ErrorReportController::class, 'update'])->name('admin.error-report.update');
    });

    // Template — preview & unduh untuk semua user login (route download
    // menggantikan link asset('storage/...') langsung di view).
    // Ditaruh di ujung grup auth agar berdekatan dengan route
    // admin.template (upload/hapus) di grup admin di bawah, tetapi
    // TIDAK di dalam grup role:admin supaya non-admin bisa mengaksesnya.
    Route::get('/template/{id}/preview', [TemplateController::class, 'preview'])->name('template.preview');
    Route::get('/template/{id}/download', [TemplateController::class, 'download'])->name('template.download');
});

/* --- ROLE: ADMIN ONLY --- */
Route::middleware(['auth', 'role:admin'])->group(function () {
    // User Management
    Route::get('/admin/users', [AdminController::class, 'indexUsers'])->name('admin.users.index');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::post('/admin/update-role/{id}', [DashboardController::class, 'updateRole'])->name('admin.updateRole');

    // Monitoring Admin
    Route::get('/riwayat-dosen', [DashboardController::class, 'riwayatDosen'])->name('admin.riwayatDosen');
    Route::get('/riwayat-dosen/{id}', [DashboardController::class, 'detailDosen'])->name('admin.dosen.detail');
    Route::get('/admin/hasil-review', [AdminController::class, 'hasilReview'])->name('admin.hasil-review');
    Route::post('/monitoring-kalender/periode', [CalendarController::class, 'updatePeriod'])->name('monitoring.kalender.periode');

    // Reviewer Management
    Route::controller(ReviewerController::class)->group(function () {
        Route::get('/admin/reviewer', 'index')->name('admin.reviewer.index');
        Route::get('/admin/reviewer/search', 'searchReviewer')->name('admin.reviewer.search');
        Route::post('/admin/reviewer/{user}', 'setReviewer')->name('admin.reviewer.set');
        Route::post('/admin/reviewer/{user}/remove', 'removeReviewer')->name('admin.reviewer.remove');
    });

    // Dokumen Management (upload, toggle, hapus)
    Route::get('/admin/dokumen', [AdminDocumentController::class, 'index'])->name('admin.dokumen.index');
    Route::post('/admin/dokumen/store', [AdminDocumentController::class, 'store'])->name('admin.dokumen.store');
    Route::patch('/admin/dokumen/toggle/{id}', [AdminDocumentController::class, 'toggleVisibility'])->name('admin.dokumen.toggle');
    Route::delete('/admin/dokumen/{id}', [AdminDocumentController::class, 'destroy'])->name('admin.dokumen.destroy');

    // Template Management
    Route::post('/admin/template/upload', [TemplateController::class, 'store'])->name('admin.template.upload');
    Route::delete('/admin/template/{id}', [TemplateController::class, 'destroy'])->name('admin.template.destroy');

    // Audit Log (Fase 6, story 29) — baca saja, admin only.
    Route::get('/admin/audit-log', [AuditLogController::class, 'index'])->name('admin.audit-log.index');

    // Setelan Admin (Fase 6, story 31) — admin only.
    Route::get('/admin/setelan', [SettingController::class, 'index'])->name('admin.settings.index');
    Route::post('/admin/setelan', [SettingController::class, 'store'])->name('admin.settings.store');
    Route::put('/admin/setelan/{id}', [SettingController::class, 'update'])->name('admin.settings.update');
    Route::delete('/admin/setelan/{id}', [SettingController::class, 'destroy'])->name('admin.settings.destroy');

    // Proposal Assignments
    Route::patch('/proposal/{id}/set-review', [ProposalController::class, 'setReview'])->name('proposal.set-review');
});

/* --- ROLE: PENGAJU --- */
Route::middleware(['auth', 'role:pengaju,reviewer'])->group(function () {
    Route::get('/proposal/create', [ProposalController::class, 'create'])->name('proposal.create');
    Route::post('/proposal/store', [ProposalController::class, 'store'])->name('proposal.store');
    // Kirim Draft / Revisi Administrasi langsung dari daftar proposal.
    Route::post('/proposal/{id}/kirim', [ProposalController::class, 'submit'])->name('proposal.kirim');
});

/* --- ROLE: REVIEWER --- */
Route::middleware(['auth', 'role:reviewer'])->group(function () {
    Route::get('/reviewer/isi-review/{id}', [ReviewerController::class, 'isiReview'])->name('reviewer.isi-review');
    Route::post('/reviewer/isi-review/{id}', [ReviewerController::class, 'submitReview'])->name('review.simpan');
});

/* --- ROLE: ADMIN & REVIEWER (SHARED MONITORING) --- */
Route::middleware(['auth', 'role:admin,reviewer'])->group(function () {
    Route::get('/proposal-perlu-direview', [ProposalController::class, 'perluDireview'])->name('monitoring.proposalPerluDireview');
    Route::get('/proposal-sedang-direview', [ProposalController::class, 'proposalSedangDireview'])->name('monitoring.proposalSedangDireview');
    Route::get('/daftar-review', [ProposalController::class, 'daftarReview'])->name('monitoring.daftarReview');
});
