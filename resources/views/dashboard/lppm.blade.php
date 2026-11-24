{{-- resources/views/dashboard/lppm.blade.php --}}
{{-- Dashboard Pengelola LPPM (role: lppm) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}

{{-- resources/views/dashboard/lppm.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Pengelola LPPM')

@section('content')
<div class="lppm-dashboard">

    {{-- Welcome Banner --}}
    <div class="lppm-welcome">
        <div class="lppm-welcome-content">
            <div class="lppm-label">RESEARCH & REVIEW MANAGEMENT</div>
            <h2>Selamat datang, {{ $user->name ?? 'Pengelola LPPM' }} 👋</h2>
            <p>
                Kelola plotting reviewer dan pantau proses penilaian
                proposal penelitian dan pengabdian.
            </p>
        </div>

        <div class="lppm-welcome-icon">
            <i class="bi bi-diagram-3"></i>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="lppm-stats">

        <div class="lppm-stat-card">
            <div class="lppm-stat-top">
                <div class="lppm-stat-icon admin">
                    <i class="bi bi-shield-check"></i>
                </div>
                <span class="lppm-stat-label">Lolos Administrasi</span>
            </div>
            <h3>{{ $lolosAdmin ?? 0 }}</h3>
            <small>Proposal siap diproses</small>
        </div>

        <div class="lppm-stat-card">
            <div class="lppm-stat-top">
                <div class="lppm-stat-icon reviewing">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <span class="lppm-stat-label">Sedang Direview</span>
            </div>
            <h3>{{ $sedangDireview ?? 0 }}</h3>
            <small>Proposal dalam penilaian</small>
        </div>

        <div class="lppm-stat-card">
            <div class="lppm-stat-top">
                <div class="lppm-stat-icon completed">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <span class="lppm-stat-label">Review Selesai</span>
            </div>
            <h3>{{ $reviewSelesai ?? 0 }}</h3>
            <small>Penilaian telah diselesaikan</small>
        </div>

        <div class="lppm-stat-card">
            <div class="lppm-stat-top">
                <div class="lppm-stat-icon approved">
                    <i class="bi bi-check-circle"></i>
                </div>
                <span class="lppm-stat-label">Disetujui</span>
            </div>
            <h3>{{ $disetujui ?? 0 }}</h3>
            <small>Proposal disetujui</small>
        </div>

    </div>

    {{-- Menu Cepat --}}
    <div class="lppm-section">

        <div class="lppm-section-heading">
            <div>
                <h4>Menu Cepat</h4>
                <p>Akses fitur pengelolaan dan monitoring review.</p>
            </div>
        </div>

        <div class="lppm-quick-grid">

            <a href="{{ route('monitoring.proposalPerluDireview') }}"
               class="lppm-quick-card">

                <div class="lppm-quick-icon green">
                    <i class="bi bi-person-plus"></i>
                </div>

                <div class="lppm-quick-content">
                    <h6>Proposal Perlu Direview</h6>
                    <p>Plotting dan penugasan reviewer</p>
                </div>

                <i class="bi bi-arrow-up-right lppm-arrow"></i>
            </a>

            <a href="{{ route('monitoring.proposalSedangDireview') }}"
               class="lppm-quick-card">

                <div class="lppm-quick-icon blue">
                    <i class="bi bi-activity"></i>
                </div>

                <div class="lppm-quick-content">
                    <h6>Proposal Sedang Direview</h6>
                    <p>Pantau progres penilaian reviewer</p>
                </div>

                <i class="bi bi-arrow-up-right lppm-arrow"></i>
            </a>

            <a href="{{ route('proposal.index') }}"
               class="lppm-quick-card">

                <div class="lppm-quick-icon orange">
                    <i class="bi bi-file-earmark-text"></i>
                </div>

                <div class="lppm-quick-content">
                    <h6>Daftar Proposal</h6>
                    <p>Lihat seluruh proposal</p>
                </div>

                <i class="bi bi-arrow-up-right lppm-arrow"></i>
            </a>

            <a href="{{ route('dokumen.index') }}"
               class="lppm-quick-card">

                <div class="lppm-quick-icon purple">
                    <i class="bi bi-folder2-open"></i>
                </div>

                <div class="lppm-quick-content">
                    <h6>Dokumen</h6>
                    <p>Akses dokumen pendukung</p>
                </div>

                <i class="bi bi-arrow-up-right lppm-arrow"></i>
            </a>

            <a href="{{ route('profile.show') }}"
               class="lppm-quick-card">

                <div class="lppm-quick-icon teal">
                    <i class="bi bi-person-circle"></i>
                </div>

                <div class="lppm-quick-content">
                    <h6>Profil Saya</h6>
                    <p>Kelola informasi akun</p>
                </div>

                <i class="bi bi-arrow-up-right lppm-arrow"></i>
            </a>

        </div>
    </div>

    {{-- Proposal Siap Plotting --}}
    <div class="lppm-panel">

        <div class="lppm-panel-header">
            <div>
                <h4>Siap Plotting Reviewer</h4>
                <p>Proposal yang telah lolos administrasi dan siap ditugaskan reviewer.</p>
            </div>

            <div class="lppm-panel-icon">
                <i class="bi bi-people"></i>
            </div>
        </div>

        <div class="lppm-panel-body">

            @forelse ($siapPlotting ?? [] as $p)

                <div class="lppm-proposal-item">

                    <div class="lppm-proposal-info">
                        <h6>{{ $p->judul }}</h6>

                        <div class="lppm-proposal-meta">
                            <span>
                                <i class="bi bi-person"></i>
                                {{ $p->user->name ?? '-' }}
                            </span>

                            <span>
                                <i class="bi bi-circle-fill"></i>
                                {{ $p->status }}
                            </span>
                        </div>
                    </div>

                    <a href="{{ route('proposal.tinjau', $p->id) }}"
                       class="lppm-action-btn">

                        <i class="bi bi-person-plus"></i>
                        <span>Tugaskan Reviewer</span>
                    </a>

                </div>

            @empty

                <div class="lppm-empty">
                    <div class="lppm-empty-icon">
                        <i class="bi bi-inbox"></i>
                    </div>

                    <h6>Belum ada proposal untuk plotting</h6>

                    <p>
                        Proposal yang lolos administrasi akan ditampilkan
                        di bagian ini.
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
   LPPM DASHBOARD
========================== */

.lppm-dashboard {
    padding: 10px 2px 30px;
    color: #24382e;
}

/* Welcome */

.lppm-welcome {
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

.lppm-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: #46805f;
    margin-bottom: 10px;
}

.lppm-welcome h2 {
    font-size: 26px;
    font-weight: 800;
    color: #20563c;
    margin-bottom: 10px;
}

.lppm-welcome p {
    margin: 0;
    color: #61796b;
    font-size: 14px;
    max-width: 600px;
}

.lppm-welcome-icon {
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

.lppm-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.lppm-stat-card {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
    transition: .25s ease;
}

.lppm-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(32, 86, 60, .08);
}

.lppm-stat-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 17px;
}

.lppm-stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.lppm-stat-icon.admin {
    background: #e5f5eb;
    color: #2e8b57;
}

.lppm-stat-icon.reviewing {
    background: #fff3df;
    color: #c58a23;
}

.lppm-stat-icon.completed {
    background: #eaf1ff;
    color: #557bc4;
}

.lppm-stat-icon.approved {
    background: #e5f5eb;
    color: #2e8b57;
}

.lppm-stat-label {
    font-size: 13px;
    font-weight: 600;
    color: #718078;
}

.lppm-stat-card h3 {
    font-size: 30px;
    font-weight: 800;
    color: #24382e;
    margin: 0 0 6px;
}

.lppm-stat-card small {
    font-size: 12px;
    color: #8b9890;
}

/* Section */

.lppm-section {
    margin-bottom: 30px;
}

.lppm-section-heading {
    margin-bottom: 17px;
}

.lppm-section-heading h4 {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 5px;
    color: #284635;
}

.lppm-section-heading p {
    color: #87938b;
    font-size: 13px;
    margin: 0;
}

/* Quick menu */

.lppm-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}

.lppm-quick-card {
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

.lppm-quick-card:hover {
    border-color: #9ac7aa;
    transform: translateY(-3px);
    box-shadow: 0 7px 20px rgba(32, 86, 60, .07);
    color: inherit;
}

.lppm-quick-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.lppm-quick-icon.green {
    background: #e6f4eb;
    color: #328254;
}

.lppm-quick-icon.blue {
    background: #eaf1ff;
    color: #5277bc;
}

.lppm-quick-icon.orange {
    background: #fff1e3;
    color: #c27a35;
}

.lppm-quick-icon.purple {
    background: #f1eaff;
    color: #8562b5;
}

.lppm-quick-icon.teal {
    background: #e3f5f4;
    color: #358b87;
}

.lppm-quick-content {
    min-width: 0;
    flex: 1;
}

.lppm-quick-content h6 {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 5px;
    color: #304a3a;
}

.lppm-quick-content p {
    font-size: 11px;
    color: #89958d;
    margin: 0;
    line-height: 1.5;
}

.lppm-arrow {
    color: #9aaba0;
    font-size: 16px;
}

/* Panel */

.lppm-panel {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
}

.lppm-panel-header {
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #edf1ee;
}

.lppm-panel-header h4 {
    font-size: 16px;
    font-weight: 800;
    color: #294736;
    margin-bottom: 5px;
}

.lppm-panel-header p {
    font-size: 12px;
    color: #8a968e;
    margin: 0;
}

.lppm-panel-icon {
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

.lppm-panel-body {
    padding: 8px 24px 20px;
}

/* Proposal */

.lppm-proposal-item {
    padding: 20px 0;
    border-bottom: 1px solid #edf1ee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.lppm-proposal-item:last-child {
    border-bottom: none;
}

.lppm-proposal-info {
    min-width: 0;
    flex: 1;
}

.lppm-proposal-info h6 {
    font-size: 14px;
    font-weight: 700;
    color: #344b3c;
    margin-bottom: 9px;
    line-height: 1.5;
}

.lppm-proposal-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
}

.lppm-proposal-meta span {
    font-size: 11px;
    color: #89958d;
}

.lppm-proposal-meta i {
    margin-right: 4px;
}

.lppm-proposal-meta span:last-child i {
    font-size: 6px;
    color: #5eae79;
    vertical-align: middle;
}

/* Action */

.lppm-action-btn {
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

.lppm-action-btn:hover {
    background: #205d3c;
    color: #fff;
}

/* Empty state */

.lppm-empty {
    padding: 45px 15px;
    text-align: center;
}

.lppm-empty-icon {
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

.lppm-empty h6 {
    font-weight: 700;
    font-size: 14px;
    color: #52665a;
    margin-bottom: 6px;
}

.lppm-empty p {
    color: #98a39b;
    font-size: 12px;
    margin: 0;
}

/* Responsive */

@media (max-width: 1200px) {
    .lppm-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .lppm-quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {

    .lppm-welcome {
        padding: 22px;
    }

    .lppm-welcome h2 {
        font-size: 21px;
    }

    .lppm-welcome-icon {
        display: none;
    }

    .lppm-stats {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .lppm-stat-card {
        padding: 16px;
    }

    .lppm-stat-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .lppm-stat-card h3 {
        font-size: 25px;
    }

    .lppm-stat-label {
        font-size: 12px;
    }

    .lppm-quick-grid {
        grid-template-columns: 1fr;
    }

    .lppm-panel-header {
        padding: 18px;
    }

    .lppm-panel-body {
        padding: 8px 18px 18px;
    }

    .lppm-proposal-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .lppm-action-btn {
        align-self: flex-start;
    }
}

</style>
@endsection

