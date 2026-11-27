    {{-- resources/views/dashboard/verifikator.blade.php --}}
    {{-- Dashboard Verifikator Administrasi (role: verifikator) --}}
    {{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}

@extends('layouts.app')

@section('title', 'Dashboard Verifikator')

@section('content')
<div class="verif-dashboard">

    {{-- Welcome Banner --}}
    <div class="verif-welcome">
        <div class="verif-welcome-content">
            <div class="verif-label">ADMINISTRATIVE VERIFICATION</div>
            <h2>Selamat datang, <span class="text-nowrap">{{ $user->name ?? 'Verifikator' }} 👋</span></h2>
            <p>
                Periksa kelengkapan dokumen administrasi proposal
                untuk memastikan proposal siap memasuki tahap penilaian.
            </p>
        </div>

        <div class="verif-welcome-icon">
            <i class="bi bi-shield-check"></i>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="verif-stats">

        <div class="verif-stat-card">
            <div class="verif-stat-top">
                <div class="verif-stat-icon waiting">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <span class="verif-stat-label">Antrean Verifikasi</span>
            </div>
            <h3>{{ $antrean_count ?? 0 }}</h3>
            <small>Proposal menunggu pemeriksaan</small>
        </div>

        <div class="verif-stat-card">
            <div class="verif-stat-top">
                <div class="verif-stat-icon approved">
                    <i class="bi bi-check-circle"></i>
                </div>
                <span class="verif-stat-label">Lolos Administrasi</span>
            </div>
            <h3>{{ $lolos ?? 0 }}</h3>
            <small>Proposal memenuhi persyaratan</small>
        </div>

        <div class="verif-stat-card">
            <div class="verif-stat-top">
                <div class="verif-stat-icon revision">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <span class="verif-stat-label">Revisi Administrasi</span>
            </div>
            <h3>{{ $revisi ?? 0 }}</h3>
            <small>Proposal perlu diperbaiki</small>
        </div>

    </div>

    {{-- Menu Cepat --}}
    <div class="verif-section">

        <div class="verif-section-heading">
            <div>
                <h4>Menu Cepat</h4>
                <p>Akses fitur verifikasi administrasi proposal.</p>
            </div>
        </div>

        <div class="verif-quick-grid">

            <a href="{{ route('proposal.index') }}"
               class="verif-quick-card">

                <div class="verif-quick-icon green">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div class="verif-quick-content">
                    <h6>Daftar Proposal</h6>
                    <p>Lihat seluruh proposal yang masuk</p>
                </div>

                <i class="bi bi-arrow-up-right verif-arrow"></i>
            </a>

            <a href="{{ route('dokumen.index') }}"
               class="verif-quick-card">

                <div class="verif-quick-icon orange">
                    <i class="bi bi-folder2-open"></i>
                </div>

                <div class="verif-quick-content">
                    <h6>Dokumen</h6>
                    <p>Akses dokumen pendukung</p>
                </div>

                <i class="bi bi-arrow-up-right verif-arrow"></i>
            </a>

            <a href="{{ route('profile.show') }}"
               class="verif-quick-card">

                <div class="verif-quick-icon purple">
                    <i class="bi bi-person-circle"></i>
                </div>

                <div class="verif-quick-content">
                    <h6>Profil Saya</h6>
                    <p>Kelola informasi akun</p>
                </div>

                <i class="bi bi-arrow-up-right verif-arrow"></i>
            </a>

        </div>
    </div>

    {{-- Antrean Proposal --}}
    <div class="verif-panel">

        <div class="verif-panel-header">
            <div>
                <h4>Antrean Proposal Masuk</h4>
                <p>Proposal berstatus Dikirim yang menunggu pemeriksaan administrasi.</p>
            </div>

            <div class="verif-panel-icon">
                <i class="bi bi-clipboard2-check"></i>
            </div>
        </div>

        <div class="verif-panel-body">

            @forelse ($antrean ?? [] as $p)

                <div class="verif-proposal-item">

                    <div class="verif-proposal-info">
                        <h6>{{ $p->judul }}</h6>

                        <div class="verif-proposal-meta">
                            <span>
                                <i class="bi bi-person"></i>
                                {{ $p->user->name ?? '-' }}
                            </span>

                            <span>
                                <i class="bi bi-calendar3"></i>
                                {{ $p->created_at?->format('d M Y') }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('proposal.tinjau', $p->id) }}"
                       class="verif-action-btn">

                        <i class="bi bi-search"></i>
                        <span>Periksa Berkas</span>
                    </a>

                </div>

            @empty

                <div class="verif-empty">
                    <div class="verif-empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>

                    <h6>Antrean verifikasi kosong</h6>

                    <p>
                        Belum ada proposal yang menunggu pemeriksaan administrasi.
                    </p>
                </div>

            @endforelse

        </div>
    </div>

</div>
@endsection

@section('styles')
<style>

/* ==========================
   VERIFIKATOR DASHBOARD
========================== */

.verif-dashboard {
    padding: 10px 2px 30px;
    color: #24382e;
}

/* Welcome */

.verif-welcome {
    background: linear-gradient(135deg, #e4f3e9, #f4faf6);
    border: 1px solid #d4e9db;
    border-radius: 20px;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
}

.verif-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: #46805f;
    margin-bottom: 10px;
}

.verif-welcome h2 {
    font-size: 26px;
    font-weight: 800;
    color: #20563c;
    margin-bottom: 10px;
}

.verif-welcome p {
    margin: 0;
    color: #61796b;
    font-size: 14px;
    max-width: 600px;
}

.verif-welcome-icon {
    width: 90px;
    height: 90px;
    border-radius: 24px;
    background: rgba(255,255,255,.65);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #39805a;
    font-size: 42px;
    flex-shrink: 0;
}

/* Statistics */

.verif-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.verif-stat-card {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
    transition: .25s ease;
}

.verif-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(32, 86, 60, .08);
}

.verif-stat-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 17px;
}

.verif-stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.verif-stat-icon.waiting {
    background: #fff4df;
    color: #c58a23;
}

.verif-stat-icon.approved {
    background: #e5f5eb;
    color: #2e8b57;
}

.verif-stat-icon.revision {
    background: #eaf1ff;
    color: #557bc4;
}

.verif-stat-label {
    font-size: 13px;
    font-weight: 600;
    color: #718078;
}

.verif-stat-card h3 {
    font-size: 30px;
    font-weight: 800;
    color: #24382e;
    margin: 0 0 6px;
}

.verif-stat-card small {
    font-size: 12px;
    color: #8b9890;
}

/* Section */

.verif-section {
    margin-bottom: 30px;
}

.verif-section-heading {
    margin-bottom: 17px;
}

.verif-section-heading h4 {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 5px;
    color: #284635;
}

.verif-section-heading p {
    color: #87938b;
    font-size: 13px;
    margin: 0;
}

/* Quick menu */

.verif-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.verif-quick-card {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 19px;
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 17px;
    text-decoration: none;
    color: inherit;
    transition: .25s ease;
}

.verif-quick-card:hover {
    border-color: #9ac7aa;
    transform: translateY(-3px);
    box-shadow: 0 7px 20px rgba(32, 86, 60, .07);
    color: inherit;
}

.verif-quick-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.verif-quick-icon.green {
    background: #e6f4eb;
    color: #328254;
}

.verif-quick-icon.orange {
    background: #fff1e3;
    color: #c27a35;
}

.verif-quick-icon.purple {
    background: #f1eaff;
    color: #8562b5;
}

.verif-quick-content {
    min-width: 0;
    flex: 1;
}

.verif-quick-content h6 {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 5px;
    color: #304a3a;
}

.verif-quick-content p {
    font-size: 11px;
    color: #89958d;
    margin: 0;
    line-height: 1.5;
}

.verif-arrow {
    color: #9aaba0;
    font-size: 16px;
}

/* Panel */

.verif-panel {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
}

.verif-panel-header {
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #edf1ee;
}

.verif-panel-header h4 {
    font-size: 16px;
    font-weight: 800;
    color: #294736;
    margin-bottom: 5px;
}

.verif-panel-header p {
    font-size: 12px;
    color: #8a968e;
    margin: 0;
}

.verif-panel-icon {
    width: 42px;
    height: 42px;
    border-radius: 13px;
    background: #e7f4ec;
    color: #39845a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

.verif-panel-body {
    padding: 8px 24px 20px;
}

/* Proposal */

.verif-proposal-item {
    padding: 20px 0;
    border-bottom: 1px solid #edf1ee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.verif-proposal-item:last-child {
    border-bottom: none;
}

.verif-proposal-info {
    min-width: 0;
    flex: 1;
}

.verif-proposal-info h6 {
    font-size: 14px;
    font-weight: 700;
    color: #344b3c;
    margin-bottom: 9px;
    line-height: 1.5;
}

.verif-proposal-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
}

.verif-proposal-meta span {
    font-size: 11px;
    color: #89958d;
}

.verif-proposal-meta i {
    margin-right: 4px;
}

/* Action */

.verif-action-btn {
    background: #28764d;
    color: #fff;
    padding: 10px 15px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 9px;
    white-space: nowrap;
    transition: .2s;
}

.verif-action-btn:hover {
    background: #205d3c;
    color: #fff;
}

/* Empty */

.verif-empty {
    padding: 45px 15px;
    text-align: center;
}

.verif-empty-icon {
    width: 55px;
    height: 55px;
    border-radius: 16px;
    background: #f0f5f2;
    color: #83a28d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin: 0 auto 14px;
}

.verif-empty h6 {
    font-weight: 700;
    font-size: 14px;
    color: #52665a;
    margin-bottom: 6px;
}

.verif-empty p {
    color: #98a39b;
    font-size: 12px;
    margin: 0;
}

/* Responsive */

@media (max-width: 1200px) {
    .verif-quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .verif-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {

    .verif-welcome {
        padding: 22px;
    }

    .verif-welcome h2 {
        font-size: 21px;
    }

    .verif-welcome-icon {
        display: none;
    }

    .verif-stats {
        grid-template-columns: 1fr;
        gap: 12px;
    }

    .verif-stat-card {
        padding: 16px;
    }

    .verif-stat-card h3 {
        font-size: 25px;
    }

    .verif-quick-grid {
        grid-template-columns: 1fr;
    }

    .verif-panel-header {
        padding: 18px;
    }

    .verif-panel-body {
        padding: 8px 18px 18px;
    }

    .verif-proposal-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .verif-action-btn {
        align-self: flex-start;
    }
}

</style>
@endsection

