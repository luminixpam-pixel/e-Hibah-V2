<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- ✅ CSRF --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'E-Hibah')</title>

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    {{-- FullCalendar --}}
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />

   <style>
    /* =========================================================
       GLOBAL
       ========================================================= */

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #d9fcd4, #f6fef7);
        min-height: 100vh;
    }

    /* =========================================================
       NAVBAR E-HIBAH
       ========================================================= */

    #mainNavbar {
        background: #1fbd4c;
        min-height: 68px;
        z-index: 3100;
        border-bottom: 1px solid rgba(255,255,255,.10);
    }

    #mainNavbar .navbar-brand {
        color: #fff !important;
        text-decoration: none;
        min-width: 170px;
    }

    #mainNavbar .nav-link {
        color: #fff !important;
    }

    #mainNavbar .nav-link i {
        font-size: 1.1rem;
    }

    /* Brand */

    .brand-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.15);
        color: #fff;
        font-size: 18px;
    }

    .brand-title {
        font-size: 16px;
        line-height: 1.1;
    }

    .brand-subtitle {
        font-size: 9px;
        opacity: .75;
        font-weight: 400;
        letter-spacing: .2px;
    }

    /* Menu utama */

    #mainNavbar .main-nav-link {
        display: flex;
        align-items: center;
        gap: 5px;
        border-radius: 8px;
        padding: 8px 9px !important;
        font-size: 12.5px;
        font-weight: 500;
        color: rgba(255,255,255,.92) !important;
        white-space: nowrap;
        transition: background-color .2s ease, color .2s ease;
    }

    #mainNavbar .main-nav-link:hover {
        background: rgba(255,255,255,.12);
        color: #fff !important;
    }

    #mainNavbar .main-nav-link-active {
        background: rgba(15,23,42,.20);
        color: #fff !important;
        box-shadow: 0 0 0 1px rgba(255,255,255,.20);
    }

    /* Dropdown */

    #mainNavbar .smooth-dropdown {
        border: 0;
        border-radius: 12px;
        padding: 7px;
        min-width: 235px;
        box-shadow: 0 12px 35px rgba(0,0,0,.14);

        animation: dropdownSmooth .16s ease-out;
    }

    #mainNavbar .dropdown-item {
        border-radius: 8px;
        padding: 9px 11px;
        font-size: 13px;

        transition:
            background-color .18s ease,
            padding-left .18s ease;
    }

    #mainNavbar .dropdown-item:hover {
        padding-left: 15px;
    }

    #mainNavbar .dropdown-item i {
        width: 19px;
        text-align: center;
    }

    @keyframes dropdownSmooth {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* User */

    .user-menu {
        display: flex;
        align-items: center;
        gap: 7px;
        color: #fff !important;
        font-size: 13px;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255,255,255,.15);
    }

    .user-name {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .user-info {
        min-width: 190px;
    }

    /* Notifikasi */

    .notification-link {
        width: 42px;
        height: 42px;
        border-radius: 10px;

        display: flex !important;
        align-items: center;
        justify-content: center;

        color: #fff !important;

        transition:
            background-color .2s ease,
            transform .2s ease;
    }

    .notification-link:hover {
        background: rgba(255,255,255,.12);
        transform: translateY(-1px);
    }

    /* Menu yang belum tersedia */

    .menu-coming-soon {
        color: #777 !important;
        cursor: default;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .menu-coming-soon:hover {
        background: transparent !important;
        padding-left: 11px !important;
    }

    .coming-badge {
        margin-left: auto;
        font-size: 9px;
        padding: 3px 7px;
        border-radius: 20px;
        background: #f1f3f5;
        color: #888;
    }

    /* =========================================================
       CONTENT
       ========================================================= */

    #mainContent {
        padding: 20px;

        backdrop-filter: blur(10px);

        background-color: rgba(255,255,255,.80);

        border-radius: 15px;

        margin-top: 20px;
    }

    /* =========================================================
       POPUP PROPOSAL
       ========================================================= */

    .popup-overlay {
        display: none;

        position: fixed;

        top: 0;
        left: 0;

        width: 100%;
        height: 100%;

        background: transparent;

        z-index: 5000;

        justify-content: center;
        align-items: center;
    }

    .popup-overlay.active {
        display: flex;
    }

    .popup-content {
        background: #fff;

        padding: 20px;

        border-radius: 12px;

        max-width: 500px;
        width: 90%;

        box-shadow: 0 5px 15px rgba(0,0,0,.30);

        position: relative;
    }

    .close-popup {
        position: absolute;

        top: 10px;
        right: 10px;

        font-size: 1.5rem;

        cursor: pointer;
    }

    /* =========================================================
       NOTIFIKASI
       ========================================================= */

    .notif-popup-overlay {
        display: none;

        position: fixed;

        inset: 0;

        background: transparent;

        z-index: 4000;
    }

    .notif-popup {
        background: #fff;

        border-radius: 12px;

        width: 350px;
        max-height: 80vh;

        overflow: hidden;

        display: flex;
        flex-direction: column;

        box-shadow: 0 5px 15px rgba(0,0,0,.30);

        position: fixed;

        top: 0;
        left: 0;

        right: auto;

        margin: 0 !important;

        transform: none !important;
    }

    .notif-popup.large {
        width: 600px;
        max-height: 90vh;
    }

    .notif-header {
        padding: 10px 15px;

        border-bottom: 1px solid #ddd;

        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notif-list {
        overflow-y: auto;
        flex: 1;
    }

    .notif-item {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
    }

    .notif-item.unread {
        background: #eef7ff;
        border-left: 4px solid #0d6efd;
    }

    .notif-item.unread .notif-title {
        font-weight: 700;
        color: #111827;
    }

    .notif-item.read {
        background: #f8f8f8;
        color: #999;
    }

    .notif-item.read .notif-title {
        font-weight: 500;
    }

    .mark-read {
        cursor: pointer;
        color: #0d6efd;
        font-size: .9rem;
    }

    .mark-read.disabled {
        color: gray;
        cursor: default;
        pointer-events: none;
    }

    /* =========================================================
       KALENDER
       ========================================================= */

    #calendar {
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        border-radius: 12px;
    }

    /* =========================================================
       TOAST FILE ERROR
       ========================================================= */

    #toastFileError {
        position: fixed;

        top: 70px;
        right: 20px;

        z-index: 99999;

        display: none;
    }

    /* =========================================================
       MOBILE / TABLET
       ========================================================= */

    @media (max-width: 1199.98px) {

        #mainNavbar .navbar-nav {
            padding-top: 8px;
            padding-bottom: 8px;
        }

        #mainNavbar .main-nav-link {
            width: 100%;
        }

        #mainNavbar .dropdown-menu {
            box-shadow: none;
            border: 1px solid rgba(0,0,0,.06);
        }

        .user-name {
            max-width: none;
        }

        #mainNavbar .navbar-left {
            max-height: 70vh;
            overflow-y: auto;
        }
    }

    @media (max-width: 575.98px) {

        #mainContent {
            padding: 12px;
            margin-top: 12px;
            border-radius: 10px;
        }

        .brand-subtitle {
            display: none !important;
        }

        #mainNavbar .navbar-brand {
            min-width: auto;
        }

        .notif-popup {
            width: calc(100vw - 20px);
        }
    }
    .brand-logo img {
    width: 180px;
    height: 65px;
    object-fit: contain;
    display: block;
    margin-left: -200px;
}
</style>

    @stack('styles')
</head>
<body>

@php
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $isMonitoringMenuActive = in_array($routeName, [
        'monitoring.proposalDikirim',
        'monitoring.proposalPerluDireview',
        'monitoring.proposalSedangDireview',
        'monitoring.reviewSelesai',
        'monitoring.proposalDisetujui',
        'monitoring.proposalDitolak',
        'monitoring.proposalDirevisi',
        'monitoring.hasilRevisi',
    ]);

    $isReviewerMenuActive = ($routeName === 'admin.reviewer.index');
    $isRiwayatMenuActive = ($routeName === 'admin.riwayatDosen' || $routeName === 'admin.dosen.detail');
@endphp

{{-- =========================
     NAVBAR E-HIBAH
     ========================= --}}
<nav id="mainNavbar" class="navbar navbar-expand-xl shadow-sm">
    <div class="container-fluid px-4">

        {{-- BRAND --}}
        <a class="navbar-brand fw-semibold d-flex align-items-center gap-2"
           href="{{ route('dashboard') }}">

         <a href="{{ route('landing') }}" class="brand-logo">
            <img
                src="{{ asset('image/logo-e-hibah.png') }}"
                alt="Logo E-Hibah YARSI"
            >
        </a>
        </a>

        {{-- MOBILE BUTTON --}}
        <button class="navbar-toggler border-0 shadow-none"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarContent">

            <i class="bi bi-list fs-2 text-white"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">

            {{-- =========================
                 MENU KIRI
                 ========================= --}}
            <ul class="navbar-nav navbar-left align-items-xl-center gap-xl-1">

                {{-- DASHBOARD --}}
                <li class="nav-item">
                    <a class="nav-link main-nav-link
                        {{ $routeName === 'dashboard' ? 'main-nav-link-active' : '' }}"
                       href="{{ route('dashboard') }}">

                        <i class="bi bi-grid-1x2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>


                {{-- INFORMASI --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-megaphone"></i>
                        <span>Informasi</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('proposal.pengumuman') }}">

                                <i class="bi bi-megaphone me-2"></i>
                                Pengumuman Hibah
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.kalender') }}">

                                <i class="bi bi-calendar3 me-2"></i>
                                Kalender Hibah
                            </a>
                        </li>

                    </ul>
                </li>


                {{-- PENGAJUAN --}}
                @if(in_array(Auth::user()->role, ['pengaju', 'reviewer']))

                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-file-earmark-plus"></i>
                        <span>Pengajuan</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="#"
                               id="openPopupBtn">

                                <i class="bi bi-plus-circle me-2"></i>
                                Ajukan Proposal
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('proposal.index') }}">

                                <i class="bi bi-folder2-open me-2"></i>
                                Proposal Saya
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('dosen.cek-kuota') }}">

                                <i class="bi bi-speedometer2 me-2"></i>
                                Cek Kuota Pengajuan
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.kemajuan.index') }}">

                                <i class="bi bi-file-earmark-arrow-up me-2"></i>
                                Laporan Kemajuan
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.akhir.index') }}">

                                <i class="bi bi-file-earmark-check me-2"></i>
                                Laporan Akhir
                            </a>
                        </li>

                    </ul>
                </li>

                @endif


                {{-- VERIFIKASI --}}
                @if(Auth::user()->role === 'admin')

                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-check2-square"></i>
                        <span>Verifikasi</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('proposal.index') }}">

                                <i class="bi bi-file-earmark-text me-2"></i>
                                Daftar Proposal
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.proposalDisetujui') }}">

                                <i class="bi bi-check-circle me-2"></i>
                                Proposal Disetujui
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.proposalDirevisi') }}">

                                <i class="bi bi-arrow-repeat me-2"></i>
                                Perlu Revisi
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.proposalDitolak') }}">

                                <i class="bi bi-x-circle me-2"></i>
                                Proposal Ditolak
                            </a>
                        </li>

                    </ul>
                </li>

                @endif


                {{-- REVIEW --}}
                @if(Auth::user()->role !== 'pengaju')

                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link
                        {{ $isReviewerMenuActive ? 'main-nav-link-active' : '' }}"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-search"></i>
                        <span>Review</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.proposalPerluDireview') }}">

                                <i class="bi bi-inbox me-2"></i>
                                Perlu Direview
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.proposalSedangDireview') }}">

                                <i class="bi bi-hourglass-split me-2"></i>
                                Sedang Direview
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.reviewSelesai') }}">

                                <i class="bi bi-check2-circle me-2"></i>
                                Review Selesai
                            </a>
                        </li>

                        @if(Auth::user()->role === 'admin')

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('admin.reviewer.index') }}">

                                <i class="bi bi-person-check me-2"></i>
                                Kelola Reviewer
                            </a>
                        </li>

                        @endif

                    </ul>
                </li>

                @endif


                {{-- MONITORING --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link
                        {{ $isMonitoringMenuActive ? 'main-nav-link-active' : '' }}"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Monitoring</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('proposal.index') }}">

                                <i class="bi bi-list-check me-2"></i>
                                Status Proposal
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.hasilRevisi') }}">

                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Hasil Revisi
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('monitoring.kalender') }}">

                                <i class="bi bi-calendar-event me-2"></i>
                                Jadwal & Kalender
                            </a>
                        </li>

                    </ul>
                </li>


                {{-- PELAPORAN --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-file-earmark-bar-graph"></i>
                        <span>Pelaporan</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.kemajuan.index') }}">

                                <i class="bi bi-file-earmark-arrow-up me-2"></i>
                                Laporan Kemajuan
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('laporan.akhir.index') }}">

                                <i class="bi bi-file-earmark-check me-2"></i>
                                Laporan Akhir
                            </a>
                        </li>

                    </ul>
                </li>


                {{-- DATA HIBAH --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-database"></i>
                        <span>Data Hibah</span>
                    </a>

                    <ul class="dropdown-menu smooth-dropdown">

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('proposal.index') }}">

                                <i class="bi bi-folder2-open me-2"></i>
                                Database Proposal
                            </a>
                        </li>

                        @if(Auth::user()->role === 'admin')

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('admin.riwayatDosen') }}">

                                <i class="bi bi-person-vcard me-2"></i>
                                Riwayat Dosen
                            </a>
                        </li>

                        @endif

                    </ul>
                </li>


                {{-- LAINNYA --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle main-nav-link"
                       href="#"
                       data-bs-toggle="dropdown">

                        <i class="bi bi-three-dots"></i>
                        <span>Lainnya</span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end smooth-dropdown">

                        <li>
                            <span class="dropdown-item menu-coming-soon">
                                <i class="bi bi-file-earmark-ruled me-2"></i>
                                Penetapan & Kontrak
                                <span class="coming-badge">Segera</span>
                            </span>
                        </li>

                        <li>
                            <span class="dropdown-item menu-coming-soon">
                                <i class="bi bi-wallet2 me-2"></i>
                                Pendanaan & Keuangan
                                <span class="coming-badge">Segera</span>
                            </span>
                        </li>

                        <li>
                            <span class="dropdown-item menu-coming-soon">
                                <i class="bi bi-award me-2"></i>
                                Luaran & Dampak
                                <span class="coming-badge">Segera</span>
                            </span>
                        </li>

                        <li>
                            <span class="dropdown-item menu-coming-soon">
                                <i class="bi bi-bar-chart-line me-2"></i>
                                Analitik Institusi
                                <span class="coming-badge">Segera</span>
                            </span>
                        </li>

                        <li>
                            <span class="dropdown-item menu-coming-soon">
                                <i class="bi bi-question-circle me-2"></i>
                                Panduan & Layanan
                                <span class="coming-badge">Segera</span>
                            </span>
                        </li>

                        @if(Auth::user()->role === 'admin')

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('admin.dokumen.index') }}">

                                <i class="bi bi-gear me-2"></i>
                                Administrasi Sistem
                            </a>
                        </li>

                        @endif

                    </ul>
                </li>

            </ul>


            {{-- =========================
                 MENU KANAN
                 ========================= --}}
            <ul class="navbar-nav ms-auto align-items-xl-center">

                {{-- NOTIFIKASI --}}
                <li class="nav-item">

                    @php
                        $bellCount = $notif_unread_count ?? 0;
                        $bellText  = $bellCount > 99 ? '99+' : $bellCount;
                    @endphp

                    <a id="notifBell"
                       class="nav-link notification-link position-relative"
                       style="cursor:pointer;"
                       title="Notifikasi">

                        <i class="bi bi-bell"></i>

                        <span id="notifCount"
                              class="position-absolute badge rounded-pill bg-danger"
                              style="
                                display: {{ $bellCount > 0 ? 'inline-block' : 'none' }};
                                font-size:10px;
                                padding:4px 6px;
                                top:2px;
                                right:2px;
                              ">
                            {{ $bellText }}
                        </span>

                    </a>
                </li>


                {{-- PROFIL --}}
                <li class="nav-item dropdown">

                    <a class="nav-link dropdown-toggle user-menu"
                       href="#"
                       data-bs-toggle="dropdown">

                        <span class="user-avatar">
                            <i class="bi bi-person"></i>
                        </span>

                        <span class="user-name">
                            {{ Auth::user()->name ?? 'Pengguna' }}
                        </span>

                    </a>

                    <ul class="dropdown-menu dropdown-menu-end smooth-dropdown">

                        <li>
                            <div class="px-3 py-2 user-info">

                                <small class="text-muted d-block">
                                    Masuk sebagai
                                </small>

                                <strong>
                                    {{ Auth::user()->name ?? 'Pengguna' }}
                                </strong>

                            </div>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <a class="dropdown-item"
                               href="{{ route('profile.show') }}">

                                <i class="bi bi-person-circle me-2"></i>
                                Profil Saya
                            </a>
                        </li>

                        <li>

                            <form action="{{ route('logout') }}" method="POST">

                                @csrf

                                <button class="dropdown-item text-danger"
                                        type="submit">

                                    <i class="bi bi-box-arrow-right me-2"></i>
                                    Keluar

                                </button>

                            </form>

                        </li>

                    </ul>

                </li>

            </ul>

        </div>
    </div>
</nav>

{{-- Alert placeholder --}}
<div id="alertPlaceholder" class="position-fixed top-0 end-0 p-3" style="z-index:9999;"></div>



{{-- Content --}}
<div id="mainContent" class="container content-wrapper mt-3">
    @yield('content')
</div>

{{-- Popup Proposal --}}
@if(Auth::check() && in_array(Auth::user()->role, ['pengaju', 'reviewer']))
<div id="proposalPopup" class="popup-overlay">
    <div class="popup-content">
        <span class="close-popup" id="closePopupBtn">&times;</span>
        <h5 class="fw-bold mb-3">Form Pengajuan Proposal</h5>

        <form action="{{ route('proposal.store') }}" method="POST" enctype="multipart/form-data" id="mainProposalForm">
            @csrf

            <div class="mb-3">
                <label class="form-label small fw-bold">Nama Ketua</label>
                <input type="text" name="nama_ketua" class="form-control" list="dosenList" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Anggota Peneliti</label>
                <div id="anggota-container">
                    <div class="input-group mb-2">
                        <input type="text" name="anggota[]" class="form-control" list="dosenList" placeholder="Ketik Nama Anggota...">
                        <button type="button" class="btn btn-outline-secondary disabled" style="width: 45px; opacity: 0;">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary" id="addAnggotaBtn">
                    <i class="bi bi-plus"></i> Tambah Anggota
                </button>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Fakultas / Prodi</label>
                <select name="fakultas_prodi" class="form-select" required>
                    <option value="" selected disabled>-- Pilih Fakultas --</option>
                    @isset($list_fakultas)
                        @foreach($list_fakultas as $fakultas)
                            <option value="{{ $fakultas->id }}">{{ $fakultas->nama_fakultas }}</option>
                        @endforeach
                    @endisset
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold text-secondary">Estimasi Biaya</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-light fw-bold text-success">Rp</span>
                    <input type="text" id="biaya_display" class="form-control" placeholder="0" required>
                    <input type="hidden" name="biaya" id="biaya_hidden">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">Judul Proposal</label>
                <input type="text" name="judul" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-bold">File Proposal (PDF/DOCX)</label>
                <input type="file" name="file" class="form-control" accept=".pdf,.doc,.docx" required>
            </div>

            <button class="btn btn-success w-100 fw-bold py-2" type="submit" id="submitBtn">Kirim Proposal</button>
        </form>
    </div>
</div>
@endif

<datalist id="dosenList">
    @isset($all_dosen)
        @foreach($all_dosen as $dosen)
            <option value="{{ $dosen->name }}">
        @endforeach
    @endisset
</datalist>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. LOGIKA FORMAT RUPIAH (HANYA SATU SCRIPT)
    const biayaDisplay = document.getElementById('biaya_display');
    const biayaHidden = document.getElementById('biaya_hidden');

    if (biayaDisplay) {
        biayaDisplay.addEventListener('input', function(e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (biayaHidden) biayaHidden.value = value;

            if (value) {
                const formatted = new Intl.NumberFormat('id-ID').format(value);
                this.value = 'Rp ' + formatted;
            } else {
                this.value = '';
            }
        });
    }

    // 2. LOGIKA TAMBAH/HAPUS ANGGOTA
    const addBtn = document.getElementById('addAnggotaBtn');
    const container = document.getElementById('anggota-container');

    if (addBtn && container) {
        addBtn.addEventListener('click', function() {
            const count = container.querySelectorAll('input').length + 1;
            const div = document.createElement('div');
            div.className = 'd-flex gap-2 mb-2';
            div.innerHTML = `
                <input type="text" name="anggota[]" class="form-control"
                    list="dosenList" placeholder="Nama Anggota ${count}">
                <button type="button" class="btn btn-danger btn-sm remove-anggota">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild(div);
        });
    }
});

// Script untuk mendeteksi pilihan dari datalist
document.querySelector('input[name="nama_ketua"]').addEventListener('input', function(e) {
    const val = e.target.value;
    const options = document.getElementById('dosenList').childNodes;

    for (let i = 0; i < options.length; i++) {
        if (options[i].value === val) {
            console.log("Dosen terpilih: " + val);
            break;
        }
    }
});
</script>

<script>
    const biayaDisplay = document.getElementById('biaya_display');
    const biayaHidden = document.getElementById('biaya_hidden');

    biayaDisplay.addEventListener('keyup', function(e) {
        let numberString = this.value.replace(/[^,\d]/g, '').toString();
        let split = numberString.split(',');
        let sisa = split[0].length % 3;
        let rupiah = split[0].substr(0, sisa);
        let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;

        this.value = 'Rp ' + rupiah;
        biayaHidden.value = numberString.replace(/\./g, '');
    });
</script>

{{-- Notifikasi --}}
<div id="notifPopup" class="notif-popup-overlay">
    <div id="notifBox" class="notif-popup">
        <div class="notif-header">
            <h5 class="m-0 fw-bold">Notifikasi</h5>
            <div class="d-flex align-items-center gap-3">
                <span class="notif-expand" style="cursor:pointer;"><i class="bi bi-arrows-fullscreen"></i></span>
                <span id="markRead" class="mark-read">Sudah dibaca semua</span>
            </div>
        </div>
        <div id="notifList" class="notif-list">
            <div class="text-center text-secondary py-3">Memuat notifikasi...</div>
        </div>
    </div>
</div>

{{-- Toast File Error --}}
<div id="toastFileError" class="toast align-items-center text-white bg-danger border-0 position-fixed" style="top:70px;right:20px;z-index:99999;" role="alert">
    <div class="d-flex">
        <div class="toast-body">Format file tidak valid! Hanya PDF/DOC/DOCX yang diperbolehkan.</div>
    </div>
</div>

{{-- Scripts --}}
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>

<script>
document.addEventListener("DOMContentLoaded",()=>{

    // FLASH MESSAGE
    const alertPlaceholder=document.getElementById('alertPlaceholder');
    const success=@json(session('success'));
    const error=@json(session('error'));
    function showAlert(message,type='success'){
        const wrapper=document.createElement('div');
        wrapper.innerHTML=`<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>`;
        alertPlaceholder.appendChild(wrapper);
        setTimeout(()=>{const alert=bootstrap.Alert.getOrCreateInstance(wrapper.querySelector('.alert'));alert.close();},5000);
    }
    if(success) showAlert(success,'success');
    if(error) showAlert(error,'danger');

    // POPUP PROPOSAL
    const popup=document.getElementById("proposalPopup");
    const openBtn = document.getElementById("openPopupBtn");
    const closeBtn=document.getElementById("closePopupBtn");
    if(openBtn && popup){
        openBtn.addEventListener("click",(e)=>{
            e.preventDefault();
            popup.classList.add("active");
        });
    }
    if(closeBtn && popup) closeBtn.addEventListener("click",()=>popup.classList.remove("active"));

    // ✅ LOGIKA TAMBAH/HAPUS ANGGOTA (VERSI KONSISTEN)
    const addBtn = document.getElementById("addAnggotaBtn");
    const anggotaContainer = document.getElementById("anggota-container");

    if (addBtn && anggotaContainer) {
        addBtn.onclick = function() {
            const div = document.createElement("div");
            div.className = "input-group mb-2";

            div.innerHTML = `
                <input type="text" name="anggota[]" class="form-control" list="dosenList" placeholder="Nama anggota">
                <button type="button" class="btn btn-danger remove-anggota" style="width: 45px;">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            anggotaContainer.appendChild(div);
        };
    }

    // Delegasi hapus yang benar
    document.addEventListener("click", function(e) {
        if (e.target.closest(".remove-anggota")) {
            e.target.closest(".input-group").remove();
        }
    });

    // Delegasi klik untuk tombol hapus
    document.addEventListener("click", e => {
        const btn = e.target.closest(".remove-anggota");
        if (btn) {
            btn.closest(".input-group").remove();
        }
    });

    // =========================
    // NOTIFIKASI (BELL + COUNT)
    // =========================
    const notifBell=document.getElementById("notifBell");
    const notifPopup=document.getElementById("notifPopup");
    const notifBox=document.getElementById("notifBox");
    const notifList=document.getElementById("notifList");
    const notifCount=document.getElementById("notifCount");
    const markReadBtn=document.getElementById("markRead");

    const initialUnreadFromBlade = parseInt(@json($notif_unread_count ?? 0)) || 0;
    let lastUnreadFromList = initialUnreadFromBlade;

    const setBadge = (count) => {
        if (!notifCount) return;
        if (!count || count <= 0) {
            notifCount.style.display = "none";
            notifCount.textContent = "0";
            return;
        }
        notifCount.style.display = "inline-block";
        notifCount.textContent = count > 99 ? "99+" : String(count);
    };

    const updateNotifCount = async (force = false) => {
        try {
            const res = await fetch("{{ route('notifications.count') }}", {
                cache: "no-store",
                credentials: "include",
                headers: { "Accept": "application/json" }
            });

            if (!res.ok) {
                if (force) setBadge(lastUnreadFromList);
                return;
            }

            const data = await res.json();
            const apiUnread = parseInt((data.unread ?? data.count ?? 0));

            // ✅ jaga biar badge ga tiba2 ilang sebelum list kebaca
            const finalUnread = Number.isFinite(apiUnread) ? Math.max(apiUnread, lastUnreadFromList) : lastUnreadFromList;
            setBadge(finalUnread);
        } catch(e) {
            if (force) setBadge(lastUnreadFromList);
        }
    };

    const loadNotifications = async () => {
        try {
            const res = await fetch("{{ route('notifications.fetch') }}", {
                cache: "no-store",
                credentials: "include",
                headers: { "Accept": "application/json" }
            });

            if (!res.ok) {
                notifList.innerHTML = '<div class="text-center py-3 text-danger">Gagal memuat notifikasi.</div>';
                return;
            }

            const data = await res.json();

            notifList.innerHTML = data.length ? '' : '<div class="text-center py-3">Tidak ada notifikasi.</div>';

            lastUnreadFromList = (data || []).filter(n => !n.is_read).length;
            setBadge(lastUnreadFromList);

            data.forEach(notif => {
                const item = document.createElement("div");
                item.className = `notif-item ${notif.is_read ? 'read' : 'unread'}`;

                const remainHtml = notif.remaining_text
                    ? `<div class="small text-primary mt-1">${notif.remaining_text}</div>`
                    : '';

                item.innerHTML = `
                    <div class="notif-title">${notif.title}</div>
                    <div class="small text-muted">${notif.message || ''}</div>
                    ${remainHtml}
                `;
                notifList.appendChild(item);
            });

            updateNotifCount();
        } catch(e) {
            notifList.innerHTML = '<div class="text-center py-3 text-danger">Gagal memuat notifikasi.</div>';
            setBadge(lastUnreadFromList);
        }
    };

    // ✅ posisikan popup tepat di bawah bell (sudut kanan popup nempel ke icon bell)
    const positionNotifBox = () => {
        if (!notifBell || !notifBox) return;

        const icon = notifBell.querySelector('i.bi-bell') || notifBell;
        const rect = icon.getBoundingClientRect();

        const boxW = notifBox.offsetWidth || 350;
        const boxH = notifBox.offsetHeight || 300;

        let top = rect.bottom;
        let left = rect.right - boxW;

        if (left < 8) left = 8;
        if (left + boxW > window.innerWidth - 8) left = window.innerWidth - boxW - 8;

        if (top + boxH > window.innerHeight - 8) {
            top = rect.top - boxH;
            if (top < 8) top = 8;
        }

        notifBox.style.top = `${Math.round(top)}px`;
        notifBox.style.left = `${Math.round(left)}px`;
        notifBox.style.right = 'auto';
    };

    // ✅ badge nongol tanpa klik bell
    setBadge(initialUnreadFromBlade);
    updateNotifCount(true);
    setInterval(updateNotifCount, 30000);

    if(notifBell) notifBell.addEventListener("click", async (e) => {
        e.preventDefault();
        notifPopup.style.display="block";
        positionNotifBox();
        await loadNotifications();
        requestAnimationFrame(() => positionNotifBox());
    });

    if(notifPopup) notifPopup.addEventListener("click", e => {
        if(e.target===notifPopup) notifPopup.style.display="none";
    });

    window.addEventListener('resize', () => {
        if (notifPopup && notifPopup.style.display === 'block') positionNotifBox();
    });
    window.addEventListener('scroll', () => {
        if (notifPopup && notifPopup.style.display === 'block') positionNotifBox();
    }, true);

    // ✅ MARK ALL AS READ (pakai route POST + token CSRF)
    if(markReadBtn){
        markReadBtn.addEventListener("click", async (e) => {
            e.preventDefault();
            e.stopPropagation();

            try {
                const res = await fetch("{{ route('notifications.markAllAsRead') }}", {
                    method: "POST",
                    credentials: "include",
                    headers: {
                        "Accept": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!res.ok) {
                    showAlert(`Gagal menandai notifikasi dibaca (HTTP ${res.status}).`, 'danger');
                    return;
                }

                // ✅ sukses -> set 0 dulu biar instan
                lastUnreadFromList = 0;
                setBadge(0);

                await loadNotifications();

                // setelah mark read, biar count ga ke-max balik ke angka lama
                // (karena lastUnreadFromList sudah 0)
                await updateNotifCount(true);

                requestAnimationFrame(() => positionNotifBox());
            } catch(e) {
                showAlert('Gagal menandai notifikasi dibaca.', 'danger');
            }
        });
    }

    // ==========================================
    // ✅ NOTIF TENGGAT MUNCUL SAAT LOGIN (TETAP)
    // ==========================================
    const showDeadlinePopup = (title, message) => {
        if (!alertPlaceholder) return;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = `
            <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert" style="min-width:320px;">
                <div class="fw-bold">${title}</div>
                <div class="small">${message || ''}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        alertPlaceholder.appendChild(wrapper);

        setTimeout(() => {
            try {
                const alert = bootstrap.Alert.getOrCreateInstance(wrapper.querySelector('.alert'));
                alert.close();
            } catch(e) {}
        }, 8000);
    };

    const runDeadlineCheckOnce = async () => {
        try {
            if (sessionStorage.getItem('deadline_check_done') === '1') return;
            sessionStorage.setItem('deadline_check_done', '1');

            const res = await fetch("{{ route('notifications.deadlineCheck') }}", {
                cache: "no-store",
                credentials: "include",
                headers: { "Accept": "application/json" }
            });

            const data = await res.json();
            const popups = data.popups || [];

            if (popups.length > 0) {
                popups.forEach(p => showDeadlinePopup(p.title, p.message));
                updateNotifCount();
            }
        } catch(e) {}
    };

    runDeadlineCheckOnce();

    // CALENDAR
    const calendarEl=document.getElementById('calendar');
    if(calendarEl){
        new FullCalendar.Calendar(calendarEl,{
            initialView:'dayGridMonth',
            events: [
                @foreach(\App\Models\Proposal::all() as $p)
                { title:'{{ $p->judul }}', start:'{{ $p->created_at->format("Y-m-d") }}', color:'{{ $p->status=="Disetujui"?"green":"blue" }}' },
                @endforeach
            ]
        }).render();
    }
});
</script>

@stack('scripts')
</body>
</html>
