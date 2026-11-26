{{-- resources/views/dashboard/pengaju.blade.php --}}
{{-- Dashboard Dosen / Ketua Pengusul & Anggota Tim (role: pengaju) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Pengaju')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    {{-- WELCOME --}}
    <div class="dashboard-welcome mb-4">
        <div>
            <span class="welcome-label">E-HIBAH DASHBOARD</span>
            <h2 class="fw-bold mt-2 mb-2">
                Selamat datang, {{ $user->name ?? 'Pengaju' }} 👋
            </h2>
            <p class="mb-0">
                Kelola pengajuan hibah, pantau status proposal,
                dan lengkapi laporan kegiatan Anda.
            </p>
        </div>
    </div>

    {{-- STATISTIK --}}
    <div class="row g-4 mb-4">

        <div class="col-md-6 col-xl-3">
            <div class="pengaju-stat-card stat-proposal">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div>
                    <p class="stat-label">Proposal Saya</p>
                    <h2>{{ $total ?? 0 }}</h2>
                    <span class="stat-caption">Total proposal</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="pengaju-stat-card stat-draft">
                <div class="stat-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <p class="stat-label">Draft</p>
                    <h2>{{ $draft ?? 0 }}</h2>
                    <span class="stat-caption">Belum dikirim</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="pengaju-stat-card stat-sent">
                <div class="stat-icon">
                    <i class="bi bi-send"></i>
                </div>
                <div>
                    <p class="stat-label">Dikirim</p>
                    <h2>{{ $dikirim ?? 0 }}</h2>
                    <span class="stat-caption">Dalam proses</span>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-xl-3">
            <div class="pengaju-stat-card stat-result">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <p class="stat-label">Disetujui / Ditolak</p>
                    <h2>{{ ($disetujui ?? 0) }} / {{ ($ditolak ?? 0) }}</h2>
                    <span class="stat-caption">Hasil keputusan</span>
                </div>
            </div>
        </div>

    </div>

    {{-- MENU CEPAT --}}
    <div class="mb-4">

        <div class="section-heading mb-3">
            <h5 class="fw-bold mb-1">Menu Cepat</h5>
            <p class="text-muted small mb-0">
                Akses fitur utama pengajuan hibah
            </p>
        </div>

        <div class="row g-3">

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('proposal.create') }}"
                   class="quick-menu-card">
                    <div class="quick-icon green">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div>
                        <h6>Buat Proposal Baru</h6>
                        <p>Mulai pengajuan hibah baru.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('proposal.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon blue">
                        <i class="bi bi-folder2-open"></i>
                    </div>
                    <div>
                        <h6>Proposal Saya</h6>
                        <p>Kelola semua proposal Anda.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('anggota.undangan') }}"
                   class="quick-menu-card">
                    <div class="quick-icon orange">
                        <i class="bi bi-envelope"></i>
                    </div>
                    <div>
                        <h6>Undangan Keanggotaan</h6>
                        <p>Konfirmasi undangan tim proposal.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('laporan.kemajuan.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon orange">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                    <div>
                        <h6>Laporan Kemajuan</h6>
                        <p>Kelola laporan kemajuan hibah.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('laporan.akhir.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon purple">
                        <i class="bi bi-journal-check"></i>
                    </div>
                    <div>
                        <h6>Laporan Akhir</h6>
                        <p>Lengkapi laporan akhir kegiatan.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('logbook.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon green">
                        <i class="bi bi-journal-text"></i>
                    </div>
                    <div>
                        <h6>Logbook</h6>
                        <p>Catat kegiatan pelaksanaan hibah.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('luaran.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon blue">
                        <i class="bi bi-award"></i>
                    </div>
                    <div>
                        <h6>Luaran</h6>
                        <p>Kelola luaran hibah Anda.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

            <div class="col-md-6 col-xl-3">
                <a href="{{ route('monev.index') }}"
                   class="quick-menu-card">
                    <div class="quick-icon orange">
                        <i class="bi bi-clipboard-check"></i>
                    </div>
                    <div>
                        <h6>Monev & RTL</h6>
                        <p>Lihat temuan Monev dan tindak lanjut.</p>
                    </div>
                    <i class="bi bi-arrow-up-right quick-arrow"></i>
                </a>
            </div>

        </div>

    </div>

    {{-- PROPOSAL DAN NOTIFIKASI --}}
    <div class="row g-4">

        {{-- PROPOSAL --}}
        <div class="col-xl-7">

            <div class="dashboard-panel h-100">

                <div class="panel-header">
                    <div>
                        <h5 class="fw-bold mb-1">Proposal Saya</h5>
                        <p class="text-muted small mb-0">
                            Pantau perkembangan pengajuan Anda
                        </p>
                    </div>

                    <a href="{{ route('proposal.index') }}"
                       class="panel-link">
                        Lihat semua <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="proposal-list">

                    @forelse ($myProposals ?? [] as $p)

                        <div class="proposal-item">

                            <div class="proposal-icon">
                                <i class="bi bi-file-earmark-richtext"></i>
                            </div>

                            <div class="proposal-detail">

                                <h6 class="fw-bold mb-2">
                                    {{ $p->judul }}
                                </h6>

                                @if ($p->no_pengajuan)
                                    <small class="proposal-no">
                                        <i class="bi bi-hash me-1"></i>{{ $p->no_pengajuan }}
                                        @if ($p->skema)
                                            · {{ $p->skema->kategori }}
                                        @endif
                                    </small>
                                @elseif ($p->skema)
                                    <small class="proposal-no">
                                        <i class="bi bi-award me-1"></i>{{ $p->skema->kategori }}
                                    </small>
                                @endif

                                <div class="proposal-meta">

                                    <span class="proposal-status">
                                        {{ $p->status }}
                                    </span>

                                    @if ($p->status_pendanaan)
                                        <span class="funding-status">
                                            {{ $p->status_pendanaan }}
                                        </span>
                                    @endif

                                </div>

                                @php
                                    $reviewBercatatan = $p->reviews
                                        ->sortByDesc('created_at')
                                        ->first(fn ($r) => trim((string) $r->catatan) !== '');
                                @endphp
                                @if ($reviewBercatatan)
                                    <small class="proposal-revision-note">
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        Catatan revisi: {{ $reviewBercatatan->catatan }}
                                    </small>
                                @endif

                                @php
                                    $verifikasiTerakhir = $p->verifications
                                        ->sortByDesc('created_at')
                                        ->first();
                                @endphp
                                @if ($verifikasiTerakhir && trim((string) $verifikasiTerakhir->catatan) !== '')
                                    <small class="proposal-revision-note">
                                        <i class="bi bi-shield-check me-1"></i>
                                        Catatan verifikasi ({{ $verifikasiTerakhir->hasil === 'Lolos' ? 'Lolos Administrasi' : 'Revisi Administrasi' }}): {{ $verifikasiTerakhir->catatan }}
                                    </small>
                                @endif

                                <small class="proposal-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $p->created_at?->format('d M Y') }}
                                </small>

                            </div>

                        </div>

                    @empty

                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-folder2-open"></i>
                            </div>

                            <h6 class="fw-bold">Belum Ada Proposal</h6>

                            <p class="text-muted small">
                                Anda belum membuat proposal hibah.
                            </p>

                            <a href="{{ route('proposal.create') }}"
                               class="btn-create">
                                <i class="bi bi-plus-lg me-1"></i>
                                Buat Proposal
                            </a>
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

        {{-- NOTIFIKASI --}}
        <div class="col-xl-5">

            <div class="dashboard-panel h-100">

                <div class="panel-header">
                    <div>
                        <h5 class="fw-bold mb-1">Notifikasi Terbaru</h5>
                        <p class="text-muted small mb-0">
                            Informasi terbaru terkait pengajuan Anda
                        </p>
                    </div>
                </div>

                <div class="notification-list">

                    @forelse ($notifications ?? [] as $n)

                        <div class="notification-item">

                            <div class="notification-icon">
                                <i class="bi bi-bell"></i>
                            </div>

                            <div class="notification-detail">
                                <h6 class="fw-bold mb-1">
                                    {{ $n->title }}
                                </h6>

                                <small class="notification-date">
                                    <i class="bi bi-clock me-1"></i>
                                    {{ $n->created_at?->format('d M Y H:i') }}
                                </small>
                            </div>

                        </div>

                    @empty

                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-bell-slash"></i>
                            </div>

                            <h6 class="fw-bold">Tidak Ada Notifikasi</h6>

                            <p class="text-muted small mb-0">
                                Notifikasi terbaru akan muncul di sini.
                            </p>
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

    {{-- PROPOSAL YANG SAYA IKUTI (SEBAGAI ANGGOTA TIM) --}}
    <div class="row g-4 mt-1">

        <div class="col-12">

            <div class="dashboard-panel">

                <div class="panel-header">
                    <div>
                        <h5 class="fw-bold mb-1">Proposal yang Saya Ikuti</h5>
                        <p class="text-muted small mb-0">
                            Proposal yang Anda ikuti sebagai anggota tim
                        </p>
                    </div>

                    <a href="{{ route('anggota.undangan') }}"
                       class="panel-link">
                        Undangan <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="proposal-list">

                    @forelse ($proposalDiikuti ?? [] as $p)

                        @php $keanggotaanSaya = $p->members->first(); @endphp

                        <div class="proposal-item">

                            <div class="proposal-icon">
                                <i class="bi bi-people"></i>
                            </div>

                            <div class="proposal-detail">

                                <h6 class="fw-bold mb-2">
                                    {{ $p->judul }}
                                </h6>

                                <small class="proposal-no">
                                    <i class="bi bi-person me-1"></i>Ketua: {{ $p->nama_ketua }}
                                    @if ($p->skema)
                                        · {{ $p->skema->kategori }}
                                    @endif
                                    @if ($p->no_pengajuan)
                                        · {{ $p->no_pengajuan }}
                                    @endif
                                </small>

                                <div class="proposal-meta">

                                    <span class="proposal-status">
                                        {{ $p->status }}
                                    </span>

                                    @if ($keanggotaanSaya && $keanggotaanSaya->status_konfirmasi === 'Diterima')
                                        <span class="badge bg-success">Anggota — Diterima</span>
                                    @elseif ($keanggotaanSaya)
                                        <span class="badge bg-warning text-dark">Menunggu konfirmasi</span>
                                    @endif

                                </div>

                                <div class="d-flex gap-2 mt-2">
                                    <a href="{{ route('anggota.proposal.show', $p->id) }}"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-eye me-1"></i>Lihat Detail
                                    </a>

                                    @if ($keanggotaanSaya && $keanggotaanSaya->status_konfirmasi === 'Menunggu')
                                        <a href="{{ route('anggota.undangan') }}"
                                           class="btn btn-outline-secondary btn-sm">
                                            Jawab Undangan
                                        </a>
                                    @endif
                                </div>

                                <small class="proposal-date">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    {{ $p->created_at?->format('d M Y') }}
                                </small>

                            </div>

                        </div>

                    @empty

                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="bi bi-people"></i>
                            </div>

                            <h6 class="fw-bold">Belum Ada Proposal yang Diikuti</h6>

                            <p class="text-muted small">
                                Anda belum terdaftar sebagai anggota tim di proposal mana pun.
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

/* STATISTICS */
.pengaju-stat-card {
    display: flex;
    align-items: center;
    gap: 18px;
    padding: 24px;
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    box-shadow: 0 4px 18px rgba(36, 107, 75, .04);
    transition: .25s ease;
    height: 100%;
}

.pengaju-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 25px rgba(36, 107, 75, .10);
}

.stat-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 16px;
    font-size: 23px;
}

.stat-proposal .stat-icon {
    background: #e5f2eb;
    color: #246b4b;
}

.stat-draft .stat-icon {
    background: #fff3df;
    color: #d28b27;
}

.stat-sent .stat-icon {
    background: #eaf1fa;
    color: #517ca9;
}

.stat-result .stat-icon {
    background: #e4f2f1;
    color: #358b85;
}

.stat-label {
    font-size: 13px;
    color: #718078;
    margin-bottom: 7px;
}

.pengaju-stat-card h2 {
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
.quick-menu-card {
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

.quick-icon.purple {
    background: #f0eafa;
    color: #8061a8;
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

/* PANEL */
.dashboard-panel {
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

.panel-link {
    text-decoration: none;
    font-size: 12px;
    font-weight: 600;
    color: #246b4b;
    white-space: nowrap;
}

/* PROPOSAL */
.proposal-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px 22px;
    border-bottom: 1px solid #eef2ef;
    transition: .2s ease;
}

.proposal-item:last-child {
    border-bottom: none;
}

.proposal-item:hover {
    background: #f9fbf9;
}

.proposal-icon {
    width: 48px;
    height: 48px;
    min-width: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f3ec;
    color: #246b4b;
    border-radius: 14px;
    font-size: 21px;
}

.proposal-detail {
    flex: 1;
    min-width: 0;
}

.proposal-detail h6 {
    font-size: 14px;
    line-height: 1.5;
    color: #294e3b;
}

.proposal-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 8px;
}

.proposal-status,
.funding-status {
    display: inline-block;
    padding: 5px 10px;
    font-size: 10px;
    font-weight: 600;
    border-radius: 20px;
}

.proposal-status {
    background: #edf2ef;
    color: #61776a;
}

.funding-status {
    background: #e6f3ed;
    color: #317553;
}

.proposal-no {
    display: block;
    font-size: 11px;
    color: #517ca9;
    font-weight: 600;
    margin-bottom: 6px;
}

.proposal-revision-note {
    display: block;
    font-size: 11px;
    color: #a33e3e;
    background: #fff5f5;
    border: 1px solid #f2d1d1;
    border-radius: 8px;
    padding: 8px 10px;
    margin-top: 8px;
    line-height: 1.6;
}

.proposal-date {
    font-size: 11px;
    color: #97a39b;
}

/* NOTIFICATION */
.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 20px 22px;
    border-bottom: 1px solid #eef2ef;
    transition: .2s ease;
}

.notification-item:last-child {
    border-bottom: none;
}

.notification-item:hover {
    background: #f9fbf9;
}

.notification-icon {
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

.notification-detail {
    flex: 1;
}

.notification-detail h6 {
    font-size: 13px;
    line-height: 1.5;
    color: #294e3b;
}

.notification-date {
    font-size: 11px;
    color: #97a39b;
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

.btn-create {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    padding: 10px 16px;
    background: #246b4b;
    color: #fff;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    transition: .2s ease;
}

.btn-create:hover {
    background: #195538;
    color: #fff;
}

/* RESPONSIVE */
@media (max-width: 768px) {

    .dashboard-welcome {
        padding: 22px;
    }

    .dashboard-welcome h2 {
        font-size: 21px;
    }

    .panel-header {
        padding: 18px;
    }

    .proposal-item,
    .notification-item {
        padding: 18px;
    }

    .quick-menu-card {
        padding: 16px;
    }
}

</style>
@endsection
