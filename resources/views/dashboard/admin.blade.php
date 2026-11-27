{{-- resources/views/dashboard/admin.blade.php --}}
{{-- Dashboard Administrator TI (role: admin) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}

{{-- resources/views/dashboard/admin.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    {{-- WELCOME --}}
    <div class="admin-welcome mb-4">
        <div>
            <span class="admin-label">ADMINISTRATOR DASHBOARD</span>
            <h2 class="fw-bold mt-2 mb-2">
                Selamat datang, <span class="text-nowrap">{{ $user->name ?? 'Admin' }} 👋</span>
            </h2>
            <p class="mb-0">
                Kelola pengguna, pantau aktivitas sistem, dan administrasikan
                E-Hibah Universitas YARSI.
            </p>
        </div>

        <div class="welcome-admin-icon">
            <i class="bi bi-shield-check"></i>
        </div>
    </div>

    {{-- STATISTIK --}}
    <div class="row g-4 mb-4">

        <div class="col-md-3">
            <div class="admin-stat-card stat-users">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <p class="stat-label">Total Pengguna</p>
                    <h2>{{ $totalUsers ?? 0 }}</h2>
                    <span class="stat-caption">Pengguna terdaftar</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="admin-stat-card stat-proposals">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <p class="stat-label">Total Proposal</p>
                    <h2>{{ $totalProposals ?? 0 }}</h2>
                    <span class="stat-caption">Proposal dalam sistem</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="admin-stat-card stat-approved">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <p class="stat-label">Disetujui</p>
                    <h2>{{ $disetujui ?? 0 }}</h2>
                    <span class="stat-caption">Proposal disetujui</span>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="admin-stat-card stat-revision">
                <div class="stat-icon">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <p class="stat-label">Ditolak / Direvisi</p>
                    <h2>{{ ($ditolak ?? 0) + ($direvisi ?? 0) }}</h2>
                    <span class="stat-caption">Perlu tindak lanjut</span>
                </div>
            </div>
        </div>

    </div>

    {{-- MENU ADMIN --}}
    <div class="mb-4">

        <div class="section-heading mb-3">
            <h5 class="fw-bold mb-1">Menu Administrasi</h5>
            <p class="text-muted small mb-0">
                Akses pengelolaan utama sistem E-Hibah
            </p>
        </div>

        <div class="row g-3">

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('admin.users.index') }}"
                   class="admin-menu-card">
                    <div class="quick-icon green">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6>Kelola Pengguna</h6>
                        <p>Kelola akun dan akses pengguna.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('admin.riwayatDosen') }}"
                   class="admin-menu-card">
                    <div class="quick-icon blue">
                        <i class="bi bi-person-lines-fill"></i>
                    </div>
                    <div>
                        <h6>Riwayat Dosen</h6>
                        <p>Telusuri riwayat dosen.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('dokumen.index') }}"
                   class="admin-menu-card">
                    <div class="quick-icon orange">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <h6>Dokumen</h6>
                        <p>Akses pengelolaan dokumen.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('profile.show') }}"
                   class="admin-menu-card">
                    <div class="quick-icon purple">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div>
                        <h6>Profil Saya</h6>
                        <p>Kelola informasi akun admin.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

        </div>

    </div>

    {{-- AKTIVITAS DAN PENGGUNA --}}
    <div class="row g-4">

        {{-- AKTIVITAS --}}
        <div class="col-xl-7">

            <div class="admin-panel h-100">

                <div class="panel-header">
                    <div>
                        <h5 class="fw-bold mb-1">Aktivitas Terbaru</h5>
                        <p class="text-muted small mb-0">
                            Ringkasan aktivitas pengguna dalam sistem
                        </p>
                    </div>

                    <div class="panel-icon">
                        <i class="bi bi-activity"></i>
                    </div>
                </div>

                <div class="activity-list">

                    @forelse ($recentLogs ?? [] as $log)

                        <div class="activity-item">

                            <div class="activity-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>

                            <div class="activity-detail">

                                <div class="activity-description">
                                    <strong>
                                        {{ $log->user->name ?? ('User #' . $log->user_id) }}
                                    </strong>

                                    <span class="activity-text">
                                        {{ $log->activity }}
                                    </span>
                                </div>

                                <small class="activity-date">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $log->created_at?->format('d M Y H:i') }}
                                </small>

                            </div>

                        </div>

                    @empty

                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-activity"></i>
                            </div>

                            <h6 class="fw-bold">Belum Ada Aktivitas</h6>

                            <p class="text-muted small mb-0">
                                Aktivitas sistem akan muncul di sini.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

        {{-- PENGGUNA TERBARU --}}
        <div class="col-xl-5">

            <div class="admin-panel h-100">

                <div class="panel-header">
                    <div>
                        <h5 class="fw-bold mb-1">Pengguna Terbaru</h5>
                        <p class="text-muted small mb-0">
                            Akun terbaru yang terdaftar
                        </p>
                    </div>

                    <div class="panel-icon">
                        <i class="bi bi-person-plus"></i>
                    </div>
                </div>

                <div class="user-list">

                    @forelse ($recentUsers ?? [] as $u)

                        <div class="user-item">

                            <div class="user-avatar">
                                <i class="bi bi-person"></i>
                            </div>

                            <div class="user-detail">

                                <h6 class="fw-bold mb-1">
                                    {{ $u->name }}
                                </h6>

                                <small class="user-email">
                                    {{ $u->email }}
                                </small>

                            </div>

                            <div class="user-role">
                                {{ $u->role }}
                            </div>

                        </div>

                    @empty

                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-people"></i>
                            </div>

                            <h6 class="fw-bold">Belum Ada Pengguna</h6>

                            <p class="text-muted small mb-0">
                                Pengguna terbaru akan ditampilkan di sini.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</div>

<style>

/* WELCOME */
.admin-welcome {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    padding: 28px 30px;
    background: linear-gradient(135deg, #e8f3ec, #f6faf7);
    border: 1px solid #dcebe1;
    border-radius: 18px;
}

.admin-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 2px;
    color: #40815f;
}

.admin-welcome h2 {
    color: #214f3a;
    font-size: 26px;
}

.admin-welcome p {
    color: #688273;
    font-size: 14px;
}

.welcome-admin-icon {
    width: 70px;
    height: 70px;
    min-width: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 20px;
    background: rgba(36, 107, 75, .10);
    color: #246b4b;
    font-size: 34px;
}

/* STATISTICS — L6: 4 kartu sebaris mulai lg (992px), bukan baru di xl
   (1200px); padding/ikon/gap dikecilkan supaya label satu baris. */
.admin-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(36, 107, 75, .04);
    transition: .25s ease;
    height: 100%;
}

/* Kartu statistik kompak di bawah 1200px: kolom grid kini 4 sebaris
   mulai md (768px), jadi pada lebar efektif ~768-1070px (jendela
   1341px dengan zoom/skala tampilan) kartu horizontal hanya menyisakan
   ~45px untuk teks dan label melipat 2-3 baris. Di bawah 1200px kartu
   disusun vertikal (ikon di atas, label di bawah selebar kartu) supaya
   label tetap satu baris; min-height label 2 baris menjaga angka
   sejajar antar kartu bila satu label masih melipat. */
@media (max-width: 1199px) {

    .admin-stat-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        padding: 16px;
    }

    .admin-stat-card .stat-icon {
        width: 44px;
        height: 44px;
        min-width: 44px;
        font-size: 18px;
    }

    .admin-stat-card > div:last-child {
        width: 100%;
    }

    .admin-stat-card .stat-label {
        min-height: 2.7em;
    }
}

/* Teks boleh menyusut supaya label tidak mendorong kartu melebar. */
.admin-stat-card > div:last-child {
    min-width: 0;
    flex: 1;
}

.admin-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(36, 107, 75, .10);
}

.stat-icon {
    width: 50px;
    height: 50px;
    min-width: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    font-size: 21px;
}

.stat-users .stat-icon {
    background: #e5f2eb;
    color: #246b4b;
}

.stat-proposals .stat-icon {
    background: #eaf1fa;
    color: #517ca9;
}

.stat-approved .stat-icon {
    background: #e4f2f1;
    color: #358b85;
}

.stat-revision .stat-icon {
    background: #fff3df;
    color: #d28b27;
}

.stat-label {
    font-size: 12px;
    color: #718078;
    margin-bottom: 7px;
}

.admin-stat-card h2 {
    font-size: 29px;
    font-weight: 800;
    color: #244e3a;
    margin-bottom: 5px;
}

.stat-caption {
    font-size: 11px;
    color: #9aa59e;
}

/* QUICK MENU */
.admin-menu-card {
    position: relative;
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 20px;
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 16px;
    text-decoration: none;
    color: inherit;
    height: 100%;
    transition: .25s ease;
}

.admin-menu-card:hover {
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

.quick-icon.purple {
    background: #f0eafa;
    color: #8061a8;
}

.admin-menu-card h6 {
    font-weight: 700;
    font-size: 13px;
    color: #294e3b;
    margin-bottom: 5px;
}

.admin-menu-card p {
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

/* PANEL */
.admin-panel {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(36, 107, 75, .03);
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    padding: 22px;
    border-bottom: 1px solid #eef2ef;
}

.panel-header h5 {
    color: #244e3a;
}

.panel-icon {
    width: 42px;
    height: 42px;
    min-width: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3ec;
    color: #246b4b;
    border-radius: 13px;
    font-size: 18px;
}

/* ACTIVITY */
.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 20px 22px;
    border-bottom: 1px solid #eef2ef;
    transition: .2s ease;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-item:hover {
    background: #f9fbf9;
}

.activity-icon {
    width: 43px;
    height: 43px;
    min-width: 43px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3ec;
    color: #246b4b;
    border-radius: 13px;
    font-size: 18px;
}

.activity-detail {
    flex: 1;
    min-width: 0;
}

.activity-description {
    font-size: 13px;
    color: #355542;
    line-height: 1.7;
}

.activity-text {
    color: #718078;
}

.activity-date {
    display: inline-block;
    margin-top: 7px;
    font-size: 11px;
    color: #97a39b;
}

/* USERS */
.user-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 19px 22px;
    border-bottom: 1px solid #eef2ef;
    transition: .2s ease;
}

.user-item:last-child {
    border-bottom: none;
}

.user-item:hover {
    background: #f9fbf9;
}

.user-avatar {
    width: 45px;
    height: 45px;
    min-width: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3ec;
    color: #246b4b;
    border-radius: 50%;
    font-size: 20px;
}

.user-detail {
    flex: 1;
    min-width: 0;
}

.user-detail h6 {
    font-size: 13px;
    color: #294e3b;
}

.user-email {
    font-size: 11px;
    color: #97a39b;
    word-break: break-word;
}

.user-role {
    font-size: 10px;
    font-weight: 700;
    color: #317553;
    background: #e6f3ed;
    border-radius: 20px;
    padding: 6px 10px;
    white-space: nowrap;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 45px 20px;
}

.empty-icon {
    font-size: 40px;
    color: #c6d8cd;
    margin-bottom: 12px;
}

.empty-state h6 {
    color: #42654f;
}

.empty-state p {
    font-size: 13px;
}

/* RESPONSIVE */
@media (max-width: 768px) {

    .admin-welcome {
        padding: 22px;
    }

    .admin-welcome h2 {
        font-size: 21px;
    }

    .welcome-admin-icon {
        width: 52px;
        height: 52px;
        min-width: 52px;
        font-size: 25px;
    }

    .panel-header {
        padding: 18px;
    }

    .activity-item,
    .user-item {
        padding: 18px;
    }

    .admin-menu-card {
        padding: 16px;
    }
}

</style>
@endsection
