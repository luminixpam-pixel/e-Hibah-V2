{{-- resources/views/dashboard/reviewer.blade.php --}}
{{-- Dashboard Reviewer (role: reviewer) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Reviewer')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    {{-- HEADER --}}
    <div class="dashboard-welcome mb-4">
        <div>
            <span class="welcome-label">REVIEWER DASHBOARD</span>
            <h2 class="fw-bold mt-2 mb-2">
                Selamat datang, <span class="text-nowrap">{{ $user->name ?? 'Reviewer' }} 👋</span>
            </h2>
            <p class="mb-0">
                Kelola dan evaluasi proposal hibah yang ditugaskan kepada Anda.
            </p>
        </div>
    </div>

    {{-- STATISTIK — kartu berupa tautan (uji ke-5 butir 5). Ketiga
         kartu mengarah ke daftar tugas review. --}}
    <div class="row g-4 mb-4">

        <div class="col-md-4">
            <a href="{{ route('monitoring.proposalPerluDireview') }}" class="review-stat-card stat-total">
                <div class="stat-icon">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div>
                    <p class="stat-label">Total Tugas</p>
                    <h2>{{ $totalTugas ?? 0 }}</h2>
                    <span class="stat-caption">Proposal ditugaskan</span>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('monitoring.proposalPerluDireview') }}" class="review-stat-card stat-pending">
                <div class="stat-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div>
                    <p class="stat-label">Menunggu</p>
                    <h2>{{ $menunggu ?? 0 }}</h2>
                    <span class="stat-caption">Review belum diselesaikan</span>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="{{ route('monitoring.proposalPerluDireview') }}" class="review-stat-card stat-completed">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <p class="stat-label">Selesai</p>
                    <h2>{{ $selesai ?? 0 }}</h2>
                    <span class="stat-caption">Review telah diselesaikan</span>
                </div>
            </a>
        </div>

    </div>

    {{-- MENU CEPAT --}}
    <div class="mb-4">
        <div class="section-heading mb-3">
            <div>
                <h5 class="fw-bold mb-1">Menu Cepat</h5>
                <p class="text-muted mb-0 small">
                    Akses menu yang sering digunakan
                </p>
            </div>
        </div>

        <div class="row g-3">

            <div class="col-md-4">
                <a href="{{ route('monitoring.proposalPerluDireview') }}"
                   class="quick-menu-card">
                    <div class="quick-icon green">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <h6>Proposal Perlu Direview</h6>
                        <p>Lihat proposal yang perlu Anda evaluasi.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-4">
                <a href="{{ route('proposal.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon blue">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <h6>Daftar Proposal</h6>
                        <p>Jelajahi daftar proposal hibah.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-4">
                <a href="{{ route('profile.show') }}"
                   class="quick-menu-card">
                    <div class="quick-icon orange">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div>
                        <h6>Profil Saya</h6>
                        <p>Kelola informasi akun Anda.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

        </div>
    </div>

    {{-- DAFTAR TUGAS REVIEW --}}
    <div class="review-task-section">

        <div class="task-header mb-3">
            <div>
                <h5 class="fw-bold mb-1">Tugas Review Saya</h5>
                <p class="text-muted small mb-0">
                    Daftar proposal yang ditugaskan kepada Anda
                </p>
            </div>

            <a href="{{ route('monitoring.proposalPerluDireview') }}"
               class="task-link">
                Lihat semua <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="review-task-card">

            @forelse ($tugasReview ?? [] as $p)

                <div class="review-task-item">

                    <div class="task-document-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="task-detail">
                        <h6 class="fw-bold mb-2">{{ $p->judul }}</h6>

                        <div class="task-meta">
                            <span>
                                <i class="bi bi-person me-1"></i>
                                {{ $p->user->name ?? '-' }}
                            </span>

                            <span>
                                <i class="bi bi-circle-fill status-dot me-1"></i>
                                {{ $p->status }}
                            </span>

                            @if ($p->review_deadline)
                                <span>
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Tenggat:
                                    {{ \Carbon\Carbon::parse($p->review_deadline)->format('d M Y') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="task-action">
                        <a href="{{ route('reviewer.isi-review', $p->id) }}"
                           class="btn-review">
                            <i class="bi bi-pencil-square me-1"></i>
                            Isi Review
                        </a>
                    </div>

                </div>

            @empty

                <div class="empty-task">
                    <div class="empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h6 class="fw-bold">Belum Ada Tugas Review</h6>
                    <p class="text-muted mb-0">
                        Belum ada proposal yang ditugaskan kepada Anda saat ini.
                    </p>
                </div>

            @endforelse

        </div>
    </div>

</div>

<style>
/* WELCOME */
.dashboard-welcome {
    padding: 28px 30px;
    background: linear-gradient(135deg, #e8f3ec, #f6faf7);
    border: 1px solid #dcebe1;
    border-radius: 18px;
}

.welcome-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #40815f;
}

.dashboard-welcome h2 {
    color: #214f3a;
    font-size: 26px;
}

.dashboard-welcome p {
    color: #688273;
}

/* STATISTICS — L6: padding/ikon/gap disamakan dengan aturan layout
   supaya label ("Total Tugas Review") cukup satu baris di desktop. */
.review-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #e8eee9;
    box-shadow: 0 4px 18px rgba(36, 107, 75, .04);
    transition: .25s ease;
    height: 100%;
    /* Kartu berupa tautan (uji ke-5 butir 5): tampil seperti kartu,
       bukan seperti tautan teks. */
    text-decoration: none;
    color: inherit;
}

.review-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(36, 107, 75, .10);
}

.stat-icon {
    width: 52px;
    height: 52px;
    min-width: 52px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.stat-total .stat-icon {
    background: #e5f2eb;
    color: #246b4b;
}

.stat-pending .stat-icon {
    background: #fff3df;
    color: #d28b27;
}

.stat-completed .stat-icon {
    background: #e4f2f1;
    color: #358b85;
}

.stat-label {
    font-size: 12px;
    color: #718078;
    margin-bottom: 7px;
}

.review-stat-card h2 {
    font-size: 30px;
    font-weight: 800;
    color: #244e3a;
    margin-bottom: 5px;
}

.stat-caption {
    font-size: 11px;
    color: #9aa59e;
}

/* QUICK MENU */
.section-heading h5,
.task-header h5 {
    color: #244e3a;
}

.quick-menu-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 16px;
    text-decoration: none;
    color: inherit;
    height: 100%;
    transition: .25s ease;
}

.quick-menu-card:hover {
    transform: translateY(-3px);
    border-color: #b9d5c5;
    box-shadow: 0 8px 20px rgba(36, 107, 75, .08);
}

.quick-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 14px;
    font-size: 21px;
}

.quick-icon.green {
    background: #e7f3ec;
    color: #246b4b;
}

.quick-icon.blue {
    background: #eaf1fa;
    color: #517ca9;
}

.quick-icon.orange {
    background: #fff2e5;
    color: #c88843;
}

.quick-menu-card h6 {
    font-weight: 700;
    font-size: 13px;
    color: #294e3b;
    margin-bottom: 5px;
}

.quick-menu-card p {
    font-size: 11px;
    color: #8b9890;
    margin-bottom: 0;
}

.quick-arrow {
    position: absolute;
    right: 15px;
    top: 15px;
    font-size: 13px;
    color: #9eaea4;
}

/* TASK SECTION */
.review-task-section {
    margin-top: 30px;
}

.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.task-link {
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    color: #246b4b;
}

.review-task-card {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    overflow: hidden;
}

.review-task-item {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 22px;
    border-bottom: 1px solid #eef2ef;
    transition: background .2s ease;
}

.review-task-item:last-child {
    border-bottom: none;
}

.review-task-item:hover {
    background: #f9fbf9;
}

.task-document-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3ec;
    color: #246b4b;
    border-radius: 14px;
    font-size: 22px;
}

.task-detail {
    flex: 1;
    min-width: 0;
}

.task-detail h6 {
    color: #2a503b;
    font-size: 14px;
    line-height: 1.5;
}

.task-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    color: #89968e;
    font-size: 11px;
}

.status-dot {
    font-size: 6px;
    color: #d7a34c;
}

.btn-review {
    display: inline-flex;
    align-items: center;
    text-decoration: none;
    padding: 10px 16px;
    background: #246b4b;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    border-radius: 10px;
    transition: .2s ease;
    white-space: nowrap;
    /* Defensif (bug tautan dashboard tidak merespons klik): tegaskan
       tautan ini dapat diklik dan berada di atas elemen sebaris di
       dalam kartu. Ini TIDAK bisa mengalahkan overlay global
       #notifPopup di layout (z-index 4000, menutupi seluruh viewport
       saat popup notifikasi terbuka) — perbaikan overlay itu harus
       dilakukan di layouts/app.blade.php, lihat laporan. */
    position: relative;
    z-index: 1;
    pointer-events: auto;
}

.btn-review:hover {
    background: #195538;
    color: #fff;
    transform: translateY(-1px);
}

/* EMPTY */
.empty-task {
    text-align: center;
    padding: 55px 20px;
}

.empty-icon {
    font-size: 42px;
    color: #c6d8cd;
    margin-bottom: 15px;
}

.empty-task h6 {
    color: #42654f;
}

.empty-task p {
    font-size: 13px;
}

/* RESPONSIVE — kartu statistik kompak di bawah 1200px: pada lebar
   efektif ~992-1070px (jendela 1341px dengan zoom/skala tampilan),
   kartu horizontal menyisakan ~45px untuk teks sehingga label melipat
   2-3 baris. Di bawah 1200px kartu disusun vertikal (ikon di atas,
   label di bawah selebar kartu) supaya label tetap satu baris dan
   angka sejajar. Selektor memakai #mainContent agar mengalahkan
   aturan padding/ikon/gap di layouts/app.blade.php. */
@media (max-width: 1199px) {

    #mainContent .review-stat-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 16px;
    }

    #mainContent .review-stat-card .stat-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        font-size: 18px;
    }

    #mainContent .review-stat-card > div:last-child {
        width: 100%;
    }
}

@media (max-width: 768px) {

    .dashboard-welcome {
        padding: 22px;
    }

    .dashboard-welcome h2 {
        font-size: 21px;
    }

    .review-task-item {
        flex-wrap: wrap;
    }

    .task-action {
        width: 100%;
    }

    .btn-review {
        width: 100%;
        justify-content: center;
    }

    .task-meta {
        gap: 8px;
        flex-direction: column;
    }

    .quick-menu-card {
        padding: 16px;
    }
}
</style>
@endsection
