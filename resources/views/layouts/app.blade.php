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

    /* =========================================================
   E-HIBAH SIDEBAR MODERN
   ========================================================= */

:root {
    --sidebar-width: 260px;
    --sidebar-bg: #123c2d;
    --sidebar-hover: #1c5942;
    --sidebar-active: #267451;
    --primary-green: #1fbd4c;
    --soft-green: #e8f5ed;
}

/* Sidebar */
#mainNavbar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    width: var(--sidebar-width);
    height: 100vh;
    background: var(--sidebar-bg);
    color: white;
    z-index: 3100;
    overflow-y: auto;
    overflow-x: hidden;
    border-right: 1px solid rgba(255,255,255,.08);
    transition: all .3s ease;
}

/* Logo */
#mainNavbar .navbar-brand {
    color: white !important;
    text-decoration: none;
}

.brand-logo {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px 15px;
}

.brand-logo img {
    width: 190px;
    height: auto;
    object-fit: contain;
    margin: 0;
}

/* Menu sidebar */
#mainNavbar .navbar-nav {
    display: flex;
    flex-direction: column;
    width: 100%;
}

#mainNavbar .nav-item {
    width: 100%;
}

#mainNavbar .main-nav-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px !important;
    margin: 4px 12px;
    width: calc(100% - 24px);
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,.85) !important;
    text-decoration: none;
    transition: .2s ease;
}

#mainNavbar .main-nav-link i {
    width: 22px;
    text-align: center;
    font-size: 17px;
}

#mainNavbar .main-nav-link:hover {
    background: var(--sidebar-hover);
    color: white !important;
}

#mainNavbar .main-nav-link-active {
    background: var(--sidebar-active);
    color: white !important;
}

/* Dropdown */
#mainNavbar .dropdown-menu {
    position: static !important;
    float: none;
    width: calc(100% - 30px);
    margin: 0 15px 8px;
    background: rgba(255,255,255,.06);
    border: none;
    box-shadow: none;
    border-radius: 10px;
}

#mainNavbar .dropdown-item {
    color: rgba(255,255,255,.85);
    font-size: 12px;
    padding: 10px 12px;
    border-radius: 8px;
}

#mainNavbar .dropdown-item:hover {
    background: rgba(255,255,255,.10);
    color: white;
}

#mainNavbar .dropdown-divider {
    border-color: rgba(255,255,255,.15);
}

#mainNavbar .dropdown-toggle::after {
    margin-left: auto;
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
    color: white;
    font-size: 18px;
}

.brand-title {
    font-size: 16px;
    line-height: 1.1;
}

.brand-subtitle {
    font-size: 9px;
    opacity: .75;
    letter-spacing: .2px;
}

/* User */
.user-menu {
    display: flex;
    align-items: center;
    gap: 7px;
    color: white !important;
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

/* Notification */
.notification-link {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex !important;
    align-items: center;
    justify-content: center;
    color: white !important;
}

.notification-link:hover {
    background: rgba(255,255,255,.12);
}

/* Coming soon */
.menu-coming-soon {
    color: #aaa !important;
    cursor: default;
    display: flex;
    align-items: center;
    gap: 4px;
}

.coming-badge {
    margin-left: auto;
    font-size: 9px;
    padding: 3px 7px;
    border-radius: 20px;
    background: #f1f3f5;
    color: #888;
}

/* Content */
#mainContent {
    margin-left: calc(var(--sidebar-width) + 20px);
    width: calc(100% - var(--sidebar-width) - 20px);
    padding: 20px;
    backdrop-filter: blur(10px);
    background-color: rgba(255,255,255,.80);
    border-radius: 15px;
    margin-top: 20px;
}

/* Responsive */
@media (max-width: 991.98px) {
    #mainNavbar {
        width: 220px;
    }

    #mainContent {
        margin-left: 240px;
        width: calc(100% - 240px);
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

    .notif-popup {
        width: calc(100vw - 20px);
    }
}

    /* =========================================================
       CONTENT
       ========================================================= */

  /* =========================================================
   SIDEBAR & HEADER FINAL
   ========================================================= */

/* Sidebar Brand */
.sidebar-brand {
    padding: 15px 10px 10px;
    border-bottom: 1px solid rgba(255,255,255,.10);
    margin-bottom: 10px;
}

.sidebar-menu {
    padding: 8px 0 25px;
}

.sidebar-label {
    padding: 14px 20px 8px;
    font-size: 10px;
    letter-spacing: 1.5px;
    color: rgba(255,255,255,.45);
    font-weight: 600;
}

/* Sidebar group */
.sidebar-group {
    width: 100%;
}

/* Submenu */
.sidebar-submenu {
    padding: 3px 0 8px 15px;
}

.sidebar-submenu a,
.sidebar-submenu .sidebar-disabled {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    margin: 2px 15px 2px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-size: 11.5px;
    color: rgba(255,255,255,.70);
    transition: .2s ease;
}

.sidebar-submenu a i,
.sidebar-submenu .sidebar-disabled i {
    width: 18px;
    text-align: center;
}

.sidebar-submenu a:hover {
    background: rgba(255,255,255,.10);
    color: white;
    transform: translateX(2px);
}

.sidebar-disabled {
    opacity: .55;
    cursor: default;
}

/* Header */
#topHeader {
    position: sticky;
    top: 0;
    margin-left: var(--sidebar-width);
    width: calc(100% - var(--sidebar-width));
    min-height: 75px;
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(10px);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 15px;
    padding: 10px 30px;
    z-index: 2000;
    border-bottom: 1px solid rgba(0,0,0,.05);
    box-shadow: 0 3px 15px rgba(0,0,0,.03);
}

/* Header user */
#topHeader .user-menu {
    color: #1f2937 !important;
}

#topHeader .user-avatar {
    background: #e8f5ed;
    color: #17603d;
}

#topHeader .notification-link {
    color: #17603d !important;
}

/* Content */
#mainContent {
    margin-left: calc(var(--sidebar-width) + 20px);
    width: calc(100% - var(--sidebar-width) - 40px);
    padding: 25px;
    backdrop-filter: blur(10px);
    background-color: rgba(255,255,255,.85);
    border-radius: 15px;
    margin-top: 20px;
    margin-bottom: 30px;
}

/* Dropdown profil */
#topHeader .dropdown-menu {
    border: none;
    border-radius: 12px;
    box-shadow: 0 12px 35px rgba(0,0,0,.12);
    padding: 8px;
}

/* Scrollbar sidebar */
#mainNavbar::-webkit-scrollbar {
    width: 5px;
}

#mainNavbar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,.20);
    border-radius: 10px;
}

/* Responsive */
@media (max-width: 991.98px) {

    :root {
        --sidebar-width: 220px;
    }

    #topHeader {
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
        padding: 10px 15px;
    }

    #mainContent {
        margin-left: calc(var(--sidebar-width) + 15px);
        width: calc(100% - var(--sidebar-width) - 30px);
        padding: 15px;
    }

    .user-name {
        max-width: 100px;
    }
}

@media (max-width: 575.98px) {

    #topHeader {
        min-height: 60px;
        padding: 8px 10px;
        gap: 8px;
    }

    #mainContent {
        padding: 12px;
        margin-top: 12px;
        border-radius: 10px;
    }

    .user-name {
        max-width: 90px;
    }

    .sidebar-submenu a,
    .sidebar-submenu .sidebar-disabled {
        font-size: 10.5px;
    }
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


/* ==========================================
   E-HIBAH MODERN UI ENHANCEMENT
   ========================================== */

:root {
    --sidebar-bg: #123b2e;
    --sidebar-hover: #1b503d;
    --sidebar-active: #24734f;
    --primary-green: #16834a;
    --soft-green: #edf7f0;
}

/* BACKGROUND */
body {
    background: #f3f7f4 !important;
}

/* SIDEBAR */
#mainNavbar {
    background: linear-gradient(180deg, #123b2e 0%, #0d3025 100%) !important;
    box-shadow: 5px 0 25px rgba(15, 45, 32, 0.08);
}

.sidebar-brand {
    padding: 25px 15px 22px !important;
    margin-bottom: 18px !important;
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.brand-logo img {
    width: 200px !important;
    transition: .3s ease;
}

#mainNavbar .main-nav-link {
    margin: 5px 12px !important;
    width: calc(100% - 24px) !important;
    padding: 13px 16px !important;
    border-radius: 10px !important;
    font-size: 13px !important;
}

#mainNavbar .main-nav-link:hover {
    background: rgba(255,255,255,.09) !important;
    transform: translateX(3px);
}

#mainNavbar .main-nav-link-active {
    background: linear-gradient(135deg, #278357, #319968) !important;
    box-shadow: 0 4px 12px rgba(20, 120, 70, .18);
}

.sidebar-submenu a:hover {
    background: rgba(255,255,255,.08) !important;
}

/* HEADER */
#topHeader {
    background: rgba(255,255,255,.92) !important;
    min-height: 78px !important;
    border-bottom: 1px solid #e8eee9 !important;
    box-shadow: 0 3px 15px rgba(30,60,40,.035) !important;
}

/* MAIN CONTENT */
#mainContent {
    background: #ffffff !important;
    border: 1px solid #e8eee9;
    box-shadow: 0 8px 30px rgba(30,60,40,.045);
    border-radius: 20px !important;
    padding: 32px !important;
}

/* HEADINGS */
#mainContent h1,
#mainContent h2,
#mainContent h3 {
    color: #1c3327;
    font-weight: 700;
}

/* CARDS */
#mainContent .card {
    border: 1px solid #e8eee9 !important;
    border-radius: 15px !important;
    box-shadow: 0 5px 20px rgba(30,60,40,.04) !important;
    transition: .25s ease;
}

#mainContent .card:hover {
    box-shadow: 0 8px 25px rgba(30,60,40,.08) !important;
}

/* BUTTONS */
#mainContent .btn-primary {
    background: #16834a !important;
    border-color: #16834a !important;
    border-radius: 9px !important;
}

#mainContent .btn-primary:hover {
    background: #116b3c !important;
    border-color: #116b3c !important;
}

#mainContent .btn-outline-primary {
    color: #16834a !important;
    border-color: #16834a !important;
    border-radius: 9px !important;
}

#mainContent .btn-outline-primary:hover {
    background: #16834a !important;
    color: white !important;
}

/* TABLE */
#mainContent table {
    border-radius: 12px;
    overflow: hidden;
}

#mainContent thead {
    background: #edf7f0;
}

/* NOTIFICATION */
#topHeader .notification-link:hover {
    background: #edf7f0 !important;
    border-radius: 12px;
}

/* PROFILE */
#topHeader .user-avatar {
    background: #e8f5ed !important;
    color: #16834a !important;
}

/* SMOOTH */
#mainContent,
#mainNavbar,
#topHeader,
#mainContent .card,
#mainContent .btn {
    transition: all .25s ease;
}


/* ======================================
   SIDEBAR BRAND & HEADER REFINEMENT
   ====================================== */

/* SIDEBAR LEBIH TERANG */
#mainNavbar {
    background: #246B4B !important;
}

/* BRAND SIDEBAR */
.sidebar-brand {
    min-height: 100px !important;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px !important;
    border-bottom: 1px solid rgba(255,255,255,.12) !important;
}

.sidebar-brand-text {
    font-size: 25px;
    font-weight: 800;
    letter-spacing: 1px;
    color: #ffffff;
}

/* MENU SIDEBAR */
#mainNavbar .main-nav-link {
    color: #f3fff7 !important;
}

#mainNavbar .main-nav-link:hover {
    background: rgba(255,255,255,.13) !important;
}

#mainNavbar .main-nav-link-active {
    background: #2F805A !important;
}

/* HEADER */
#topHeader {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
}

/* LOGO DI HEADER */
.header-logo {
    display: flex;
    align-items: center;
    margin-right: auto;
}

.header-logo img {
    width: 145px;
    height: auto;
    object-fit: contain;
}

.header-logo a {
    display: flex;
    align-items: center;
}

/* RESPONSIVE LOGO */
@media (max-width: 575.98px) {
    .header-logo img {
        width: 110px;
    }

    .sidebar-brand-text {
        font-size: 20px;
    }
}

/* ==================================
   COLLAPSIBLE SIDEBAR
   ================================== */

:root {
    --sidebar-width: 220px;
    --sidebar-collapsed-width: 75px;
}

#mainNavbar {
    width: var(--sidebar-width) !important;
    transition: width .3s ease;
}

#topHeader {
    margin-left: var(--sidebar-width) !important;
    width: calc(100% - var(--sidebar-width)) !important;
    transition: margin .3s ease, width .3s ease;
}

#mainContent {
    margin-left: calc(var(--sidebar-width) + 20px) !important;
    width: calc(100% - var(--sidebar-width) - 40px) !important;
    transition: margin .3s ease, width .3s ease;
}

/* Tombol toggle */
.sidebar-brand {
    display: flex !important;
    align-items: center;
    justify-content: space-between !important;
}

.sidebar-toggle {
    border: none;
    background: rgba(255,255,255,.12);
    color: white;
    width: 34px;
    height: 34px;
    border-radius: 8px;
    cursor: pointer;
    transition: .2s ease;
}

.sidebar-toggle:hover {
    background: rgba(255,255,255,.25);
}

/* SIDEBAR KECIL */
body.sidebar-collapsed {
    --sidebar-width: 75px;
}

body.sidebar-collapsed #mainNavbar {
    width: 75px !important;
}

body.sidebar-collapsed .sidebar-brand {
    justify-content: center !important;
}

body.sidebar-collapsed .sidebar-brand-title,
body.sidebar-collapsed .sidebar-label,
body.sidebar-collapsed #mainNavbar .main-nav-link span,
body.sidebar-collapsed #mainNavbar .main-nav-link .menu-text,
body.sidebar-collapsed .sidebar-submenu,
body.sidebar-collapsed #mainNavbar .dropdown-toggle::after {
    display: none !important;
}

body.sidebar-collapsed #mainNavbar .main-nav-link {
    justify-content: center !important;
    padding: 12px 0 !important;
    margin: 5px 10px !important;
    width: calc(100% - 20px) !important;
}

body.sidebar-collapsed #mainNavbar .main-nav-link i {
    margin: 0 !important;
}

body.sidebar-collapsed .sidebar-toggle {
    position: absolute;
    right: -17px;
    top: 28px;
    background: #ffffff;
    color: #246B4B;
    border: 1px solid #e2e8e3;
    box-shadow: 0 3px 10px rgba(0,0,0,.12);
    z-index: 3200;
}

/* Responsif */
@media (max-width: 991.98px) {
    #mainContent {
        padding: 15px !important;
    }
}
</style>

    @stack('styles')
    @yield('styles')
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
{{-- =========================
     SIDEBAR E-HIBAH
     ========================= --}}
<nav id="mainNavbar" class="shadow-sm">

    {{-- LOGO --}}
  {{-- BRAND SIDEBAR --}}
    <div class="sidebar-brand">
        <div class="sidebar-brand-text">
            <span class="sidebar-brand-title">E-Hibah</span>
        </div>

        <button type="button" id="sidebarToggle" class="sidebar-toggle"
                title="Perbesar / Perkecil Sidebar">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>

    {{-- MENU --}}
    <div class="sidebar-menu">

        <div class="sidebar-label">MENU UTAMA</div>

        {{-- DASHBOARD --}}
        <a href="{{ route('dashboard') }}"
           class="main-nav-link {{ $routeName === 'dashboard' ? 'main-nav-link-active' : '' }}">
            <i class="bi bi-grid-1x2"></i>
            <span>Dashboard</span>
        </a>

        {{-- INFORMASI --}}
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuInformasi">
                <i class="bi bi-megaphone"></i>
                <span>Informasi</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuInformasi">
                <a href="{{ route('proposal.pengumuman') }}">
                    <i class="bi bi-megaphone"></i> Pengumuman Hibah
                </a>
                <a href="{{ route('monitoring.kalender') }}">
                    <i class="bi bi-calendar3"></i> Kalender Hibah
                </a>
            </div>
        </div>

        {{-- PENGAJUAN --}}
        @if(in_array(Auth::user()->role, ['pengaju', 'reviewer']))
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuPengajuan">
                <i class="bi bi-file-earmark-plus"></i>
                <span>Pengajuan</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuPengajuan">
                <a href="{{ route('proposal.create') }}"
                    <i class="bi bi-plus-circle"></i> Ajukan Proposal
                </a>
                <a href="{{ route('proposal.index') }}">
                    <i class="bi bi-folder2-open"></i> Proposal Saya
                </a>
                <a href="{{ route('dosen.cek-kuota') }}">
                    <i class="bi bi-speedometer2"></i> Cek Kuota Pengajuan
                </a>
                <a href="{{ route('laporan.kemajuan.index') }}">
                    <i class="bi bi-file-earmark-arrow-up"></i> Laporan Kemajuan
                </a>
                <a href="{{ route('laporan.akhir.index') }}">
                    <i class="bi bi-file-earmark-check"></i> Laporan Akhir
                </a>
            </div>
        </div>
        @endif

        {{-- VERIFIKASI --}}
        @if(in_array(Auth::user()->role, ['verifikator', 'admin']))
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuVerifikasi">
                <i class="bi bi-check2-square"></i>
                <span>Verifikasi</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuVerifikasi">
                @if(Auth::user()->role === 'verifikator')
                <a href="{{ route('dashboard.verifikator') }}">
                    <i class="bi bi-hourglass-split"></i> Antrean Verifikasi
                </a>
                @endif
                <a href="{{ route('proposal.index') }}">
                    <i class="bi bi-file-earmark-text"></i> Daftar Proposal
                </a>
                <a href="{{ route('monitoring.proposalDisetujui') }}">
                    <i class="bi bi-check-circle"></i> Proposal Disetujui
                </a>
                <a href="{{ route('monitoring.proposalDirevisi') }}">
                    <i class="bi bi-arrow-repeat"></i> Perlu Revisi
                </a>
                <a href="{{ route('monitoring.proposalDitolak') }}">
                    <i class="bi bi-x-circle"></i> Proposal Ditolak
                </a>
            </div>
        </div>
        @endif

        {{-- PLOTTING (LPPM) --}}
        @if(Auth::user()->role === 'lppm')
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuPlotting">
                <i class="bi bi-diagram-3"></i>
                <span>Plotting</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuPlotting">
                <a href="{{ route('lppm.plotting') }}">
                    <i class="bi bi-person-plus"></i> Plotting Reviewer
                </a>
                <a href="{{ route('dashboard.lppm') }}">
                    <i class="bi bi-activity"></i> Pemantauan Review
                </a>
            </div>
        </div>
        @endif

        {{-- REVIEW --}}
        @if(in_array(Auth::user()->role, ['reviewer', 'admin']))
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuReview">
                <i class="bi bi-search"></i>
                <span>Review</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuReview">
                @if(Auth::user()->role === 'reviewer')
                <a href="{{ route('dashboard.reviewer') }}">
                    <i class="bi bi-inbox"></i> Tugas Review Saya
                </a>
                @endif
                <a href="{{ route('monitoring.proposalPerluDireview') }}">
                    <i class="bi bi-inbox"></i> Perlu Direview
                </a>
                <a href="{{ route('monitoring.proposalSedangDireview') }}">
                    <i class="bi bi-hourglass-split"></i> Sedang Direview
                </a>
                <a href="{{ route('monitoring.reviewSelesai') }}">
                    <i class="bi bi-check2-circle"></i> Review Selesai
                </a>

                @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.reviewer.index') }}">
                    <i class="bi bi-person-check"></i> Kelola Reviewer
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- MONITORING --}}
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuMonitoring">
                <i class="bi bi-graph-up-arrow"></i>
                <span>Monitoring</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuMonitoring">
                <a href="{{ route('proposal.index') }}">
                    <i class="bi bi-list-check"></i> Status Proposal
                </a>
                <a href="{{ route('monitoring.reviewSelesai') }}">
                    <i class="bi bi-check2-circle"></i> Review Selesai
                </a>
                <a href="{{ route('monitoring.hasilRevisi') }}">
                    <i class="bi bi-arrow-clockwise"></i> Hasil Revisi
                </a>
                <a href="{{ route('monitoring.kalender') }}">
                    <i class="bi bi-calendar-event"></i> Jadwal & Kalender
                </a>
            </div>
        </div>

        {{-- PELAPORAN --}}
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuPelaporan">
                <i class="bi bi-file-earmark-bar-graph"></i>
                <span>Pelaporan</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuPelaporan">
                <a href="{{ route('laporan.kemajuan.index') }}">
                    <i class="bi bi-file-earmark-arrow-up"></i> Laporan Kemajuan
                </a>
                <a href="{{ route('laporan.akhir.index') }}">
                    <i class="bi bi-file-earmark-check"></i> Laporan Akhir
                </a>
            </div>
        </div>

        {{-- DATA HIBAH --}}
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuDataHibah">
                <i class="bi bi-database"></i>
                <span>Data Hibah</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuDataHibah">
                <a href="{{ route('proposal.index') }}">
                    <i class="bi bi-folder2-open"></i> Database Proposal
                </a>

                @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.riwayatDosen') }}">
                    <i class="bi bi-person-vcard"></i> Riwayat Dosen
                </a>
                @endif
            </div>
        </div>

        {{-- LAINNYA --}}
        <div class="sidebar-group">
            <a class="main-nav-link dropdown-toggle"
               data-bs-toggle="collapse"
               href="#menuLainnya">
                <i class="bi bi-three-dots"></i>
                <span>Lainnya</span>
            </a>

            <div class="collapse sidebar-submenu" id="menuLainnya">
                <span class="sidebar-disabled">
                    <i class="bi bi-file-earmark-ruled"></i> Penetapan & Kontrak
                </span>
                <span class="sidebar-disabled">
                    <i class="bi bi-wallet2"></i> Pendanaan & Keuangan
                </span>
                <span class="sidebar-disabled">
                    <i class="bi bi-award"></i> Luaran & Dampak
                </span>
                <span class="sidebar-disabled">
                    <i class="bi bi-bar-chart-line"></i> Analitik Institusi
                </span>
                <span class="sidebar-disabled">
                    <i class="bi bi-question-circle"></i> Panduan & Layanan
                </span>

                @if(Auth::user()->role === 'admin')
                <a href="{{ route('admin.dokumen.index') }}">
                    <i class="bi bi-gear"></i> Administrasi Sistem
                </a>
                @endif
            </div>
        </div>

    </div>
</nav>

{{-- =========================
     HEADER ATAS
     ========================= --}}
<div id="topHeader">
    {{-- LOGO HEADER --}}
<div class="header-logo">
    <a href="{{ route('landing') }}">
        <img src="{{ asset('image/logo-e-hibah.png') }}"
             alt="Logo E-Hibah">
    </a>
</div>

    {{-- NOTIFIKASI --}}
    @php
        $bellCount = $notif_unread_count ?? 0;
        $bellText  = $bellCount > 99 ? '99+' : $bellCount;
    @endphp

    <a id="notifBell"
       class="notification-link position-relative"
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

    {{-- PROFIL --}}
    <div class="dropdown">
        <a class="dropdown-toggle user-menu"
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
                    <small class="text-muted d-block">Masuk sebagai</small>
                    <strong>{{ Auth::user()->name ?? 'Pengguna' }}</strong>
                </div>
            </li>

            <li><hr class="dropdown-divider"></li>

            <li>
                <a class="dropdown-item"
                   href="{{ route('profile.show') }}">
                    <i class="bi bi-person-circle me-2"></i> Profil
                </a>
            </li>

            <li>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="dropdown-item">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </li>
        </ul>
    </div>
</div>

{{-- Content --}}
<div id="mainContent" class="container content-wrapper mt-3">
    @yield('content')
</div>

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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggle = document.getElementById('sidebarToggle');

    if (!toggle) return;

    toggle.addEventListener('click', function () {
        document.body.classList.toggle('sidebar-collapsed');
    });
});
</script>
</body>
</html>
