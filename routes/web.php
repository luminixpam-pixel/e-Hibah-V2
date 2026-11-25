<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\{
    DashboardController, ProposalController, ReviewerController,
    AdminController, NotificationController, CalendarController,
    ProposalReviewerController, LaporanKemajuanController, ProfileController,
    AdminDocumentController, TemplateController, LaporanAkhirController,
    RoleDashboardController, ProposalMemberController, VerificationController
};

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
        Route::delete('/proposal/{id}', 'destroy')->name('proposal.destroy');
        Route::get('/proposal/{id}/tinjau', 'tinjau')->name('proposal.tinjau');
        Route::patch('/proposal/{id}/keputusan', 'keputusan')->name('proposal.keputusan');

        // Monitoring Statuses
        Route::get('/proposal-disetujui', 'proposalDisetujui')->name('monitoring.proposalDisetujui');
        Route::get('/proposal-ditolak', 'proposalDitolak')->name('monitoring.proposalDitolak');
        Route::get('/proposal-selesai', 'reviewSelesai')->name('monitoring.reviewSelesai');
        Route::get('/proposal-direvisi', 'proposalDirevisi')->name('monitoring.proposalDirevisi');
        Route::get('/hasil-review', 'hasilRevisi')->name('monitoring.hasilRevisi');
        Route::get('/review/{id}/pdf', 'downloadReviewPdf')->name('review.pdf');
    });

    // Laporan (Kemajuan & Akhir)
    Route::get('/laporan-kemajuan', [LaporanKemajuanController::class, 'index'])->name('laporan.kemajuan.index');
    Route::post('/laporan-kemajuan', [LaporanKemajuanController::class, 'store'])->name('laporan.kemajuan.store');
    Route::get('/laporan-kemajuan/download/{id}', [LaporanKemajuanController::class, 'downloadLaporan'])->name('laporan.kemajuan.download');

    Route::get('/laporan-akhir', [LaporanAkhirController::class, 'index'])->name('laporan.akhir.index');
    Route::post('/laporan-akhir/store', [LaporanAkhirController::class, 'store'])->name('laporan.akhir.store');
    Route::get('/laporan-akhir/download/{id}', [LaporanAkhirController::class, 'download'])->name('laporan.akhir.download');

    // Dokumen (lihat & unduh — semua role yang login)
    Route::get('/dokumen', [AdminDocumentController::class, 'index'])->name('dokumen.index');
    Route::get('/dokumen/download/{id}', [AdminDocumentController::class, 'download'])->name('dokumen.download');

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
                    'title'    => $n->title,
                    'message'  => $n->message,
                    'is_read'  => (bool) $n->is_read,
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

    // Penugasan reviewer oleh admin (pencarian + submit)
    Route::prefix('admin/proposal')->group(function () {
        Route::get('/search-reviewer', [ProposalReviewerController::class, 'search'])->name('admin.proposal.search-reviewer');
        Route::post('/{id}/assign', [ProposalReviewerController::class, 'assign'])->name('admin.proposal.assign');
    });

    // Plotting reviewer oleh LPPM (Fase 3, story 16 + 19).
    Route::get('/lppm/plotting', [ProposalReviewerController::class, 'plotting'])
        ->middleware('role:lppm')->name('lppm.plotting');

    // Tetapkan 2 reviewer + tenggat (dipakai admin & LPPM).
    Route::post('/proposal/{id}/assign-reviewer', [ProposalController::class, 'assignReviewer'])
        ->middleware('role:admin,lppm')->name('proposal.assignReviewer');
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
        Route::post('/admin/reviewer/{user}/remove', 'removeReviewer')->name('reviewer.remove');
    });

    // Dokumen Management (upload, toggle, hapus)
    Route::get('/admin/dokumen', [AdminDocumentController::class, 'index'])->name('admin.dokumen.index');
    Route::post('/admin/dokumen/store', [AdminDocumentController::class, 'store'])->name('admin.dokumen.store');
    Route::patch('/admin/dokumen/toggle/{id}', [AdminDocumentController::class, 'toggleVisibility'])->name('admin.dokumen.toggle');
    Route::delete('/admin/dokumen/{id}', [AdminDocumentController::class, 'destroy'])->name('admin.dokumen.destroy');

    // Template Management
    Route::post('/admin/template/upload', [TemplateController::class, 'store'])->name('admin.template.upload');
    Route::delete('/admin/template/{id}', [TemplateController::class, 'destroy'])->name('admin.template.destroy');

    // Proposal Assignments
    Route::patch('/proposal/{id}/set-review', [ProposalController::class, 'setReview'])->name('proposal.set-review');
});

/* --- ROLE: PENGAJU --- */
Route::middleware(['auth', 'role:pengaju,reviewer'])->group(function () {
    Route::get('/proposal/create', [ProposalController::class, 'create'])->name('proposal.create');
    Route::post('/proposal/store', [ProposalController::class, 'store'])->name('proposal.store');
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
});
