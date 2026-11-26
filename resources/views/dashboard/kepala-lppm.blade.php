{{-- resources/views/dashboard/kepala-lppm.blade.php --}}
{{-- Dashboard Kepala LPPM (role: kepala_lppm) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}


{{-- resources/views/dashboard/kepala-lppm.blade.php --}}
@extends('layouts.app')

@section('title', 'Dashboard Kepala LPPM')

@section('content')
<div class="klppm-dashboard">

    {{-- Welcome Banner --}}
    <div class="klppm-welcome">
        <div class="klppm-welcome-content">
            <div class="klppm-label">LEMBAGA PENELITIAN DAN PENGABDIAN MASYARAKAT</div>
            <h2>Selamat datang, {{ $user->name ?? 'Kepala LPPM' }} 👋</h2>
            <p>
                Kelola dan tetapkan keputusan pendanaan proposal berdasarkan
                hasil penilaian reviewer.
            </p>
        </div>
        <div class="klppm-welcome-icon">
            <i class="bi bi-clipboard2-check"></i>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="klppm-stats">

        <div class="klppm-stat-card">
            <div class="klppm-stat-top">
                <div class="klppm-stat-icon waiting">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <span class="klppm-stat-label">Menunggu Keputusan</span>
            </div>
            <h3>{{ $menungguKeputusan ?? 0 }}</h3>
            <small>Proposal perlu ditindaklanjuti</small>
        </div>

        <div class="klppm-stat-card">
            <div class="klppm-stat-top">
                <div class="klppm-stat-icon approved">
                    <i class="bi bi-check-circle"></i>
                </div>
                <span class="klppm-stat-label">Disetujui</span>
            </div>
            <h3>{{ $disetujui ?? 0 }}</h3>
            <small>Proposal yang telah disetujui</small>
        </div>

        <div class="klppm-stat-card">
            <div class="klppm-stat-top">
                <div class="klppm-stat-icon rejected">
                    <i class="bi bi-x-circle"></i>
                </div>
                <span class="klppm-stat-label">Ditolak</span>
            </div>
            <h3>{{ $ditolak ?? 0 }}</h3>
            <small>Proposal yang tidak disetujui</small>
        </div>

        <div class="klppm-stat-card">
            <div class="klppm-stat-top">
                <div class="klppm-stat-icon revised">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <span class="klppm-stat-label">Direvisi</span>
            </div>
            <h3>{{ $direvisi ?? 0 }}</h3>
            <small>Proposal yang perlu revisi</small>
        </div>

    </div>

    {{-- Menu Cepat --}}
    <div class="klppm-section">
        <div class="klppm-section-heading">
            <div>
                <h4>Menu Cepat</h4>
                <p>Akses fitur pengelolaan proposal dengan mudah.</p>
            </div>
        </div>

        <div class="klppm-quick-grid">

            <a href="{{ route('proposal.index') }}" class="klppm-quick-card">
                <div class="klppm-quick-icon green">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="klppm-quick-content">
                    <h6>Daftar Proposal</h6>
                    <p>Lihat dan kelola seluruh proposal</p>
                </div>
                <i class="bi bi-arrow-up-right klppm-arrow"></i>
            </a>

            <a href="{{ route('monitoring.reviewSelesai') }}" class="klppm-quick-card">
                <div class="klppm-quick-icon blue">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div class="klppm-quick-content">
                    <h6>Review Selesai</h6>
                    <p>Lihat hasil penilaian reviewer</p>
                </div>
                <i class="bi bi-arrow-up-right klppm-arrow"></i>
            </a>

            <a href="{{ route('dokumen.index') }}" class="klppm-quick-card">
                <div class="klppm-quick-icon orange">
                    <i class="bi bi-folder2-open"></i>
                </div>
                <div class="klppm-quick-content">
                    <h6>Dokumen</h6>
                    <p>Akses dokumen pendukung</p>
                </div>
                <i class="bi bi-arrow-up-right klppm-arrow"></i>
            </a>

            <a href="{{ route('profile.show') }}" class="klppm-quick-card">
                <div class="klppm-quick-icon purple">
                    <i class="bi bi-person-circle"></i>
                </div>
                <div class="klppm-quick-content">
                    <h6>Profil Saya</h6>
                    <p>Kelola informasi akun</p>
                </div>
                <i class="bi bi-arrow-up-right klppm-arrow"></i>
            </a>

        </div>
    </div>

    {{-- Konten Utama --}}
    <div class="klppm-main-grid">

        {{-- Proposal Siap Diputuskan --}}
        <div class="klppm-panel">
            <div class="klppm-panel-header">
                <div>
                    <h4>Siap Diputuskan</h4>
                    <p>Proposal dengan proses review yang telah selesai</p>
                </div>
                <div class="klppm-panel-icon">
                    <i class="bi bi-journal-check"></i>
                </div>
            </div>

            <div class="klppm-panel-body">

                @forelse ($siapDiputuskan ?? [] as $p)

                    <div class="klppm-proposal-item">

                        <div class="klppm-proposal-info">
                            <h6>{{ $p->judul }}</h6>

                            <div class="klppm-proposal-meta">
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
                           class="klppm-action-btn">
                            <span>Tinjau</span>
                            <i class="bi bi-arrow-right"></i>
                        </a>

                    </div>

                @empty

                    <div class="klppm-empty">
                        <div class="klppm-empty-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <h6>Tidak ada proposal</h6>
                        <p>Belum ada proposal yang menunggu keputusan pendanaan.</p>
                    </div>

                @endforelse

            </div>
        </div>

        {{-- Riwayat Keputusan --}}
        <div class="klppm-panel">
            <div class="klppm-panel-header">
                <div>
                    <h4>Riwayat Keputusan</h4>
                    <p>Keputusan pendanaan yang telah dicatat</p>
                </div>
                <div class="klppm-panel-icon history">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>

            <div class="klppm-panel-body">

                @forelse ($riwayatKeputusan ?? [] as $p)

                    <div class="klppm-history-item">

                        <div class="klppm-history-dot"></div>

                        <div class="klppm-history-content">
                            <h6>{{ $p->judul }}</h6>

                            <div class="klppm-history-bottom">
                                <span class="klppm-status-badge
                                    @if($p->status_pendanaan == 'Disetujui')
                                        success
                                    @elseif($p->status_pendanaan == 'Ditolak')
                                        danger
                                    @elseif($p->status_pendanaan == 'Direvisi')
                                        warning
                                    @else
                                        info
                                    @endif
                                ">
                                    {{ $p->status_pendanaan }}
                                </span>

                                <small>
                                    <i class="bi bi-calendar3"></i>
                                    {{ $p->updated_at?->format('d M Y') }}
                                </small>
                            </div>
                        </div>

                    </div>

                @empty

                    <div class="klppm-empty">
                        <div class="klppm-empty-icon">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h6>Belum ada riwayat</h6>
                        <p>Riwayat keputusan akan ditampilkan di sini.</p>
                    </div>

                @endforelse

            </div>
        </div>

    </div>

    {{-- Analitik per Periode --}}
    <div class="klppm-section">
        <div class="klppm-section-heading">
            <div>
                <h4>Analitik per Periode</h4>
                <p>Jumlah proposal per status untuk setiap periode hibah.</p>
            </div>
        </div>

        <div class="klppm-panel">
            <div class="klppm-panel-body">

                @if (count($analitikPeriode ?? []) > 0)

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead>
                                <tr class="border-b border-gray-200 text-gray-500">
                                    <th class="px-3 py-3 font-semibold whitespace-nowrap">Periode</th>
                                    <th class="px-3 py-3 font-semibold text-center">Total</th>
                                    @foreach ($analitikStatus ?? [] as $status)
                                        <th class="px-3 py-3 font-semibold text-center whitespace-nowrap">{{ $status }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($analitikPeriode as $baris)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="px-3 py-3 font-semibold whitespace-nowrap">{{ $baris['periode'] }}</td>
                                        <td class="px-3 py-3 text-center font-bold">{{ $baris['total'] }}</td>
                                        @foreach ($analitikStatus ?? [] as $status)
                                            <td class="px-3 py-3 text-center">{{ $baris['status'][$status] ?? 0 }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                @else

                    <div class="klppm-empty">
                        <div class="klppm-empty-icon">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <h6>Belum ada data analitik</h6>
                        <p>Belum ada periode hibah atau proposal yang tercatat.</p>
                    </div>

                @endif

            </div>
        </div>
    </div>

</div>
@endsection

@section('styles')
<style>
/* =========================
   KEPALA LPPM DASHBOARD
========================= */

.klppm-dashboard {
    padding: 10px 2px 30px;
    color: #24382e;
}

/* Welcome */

.klppm-welcome {
    background: linear-gradient(135deg, #e4f3e9, #f4faf6);
    border: 1px solid #d4e9db;
    border-radius: 20px;
    padding: 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 25px;
    overflow: hidden;
}

.klppm-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.4px;
    color: #46805f;
    margin-bottom: 10px;
}

.klppm-welcome h2 {
    font-size: 26px;
    font-weight: 800;
    color: #20563c;
    margin-bottom: 10px;
}

.klppm-welcome p {
    margin: 0;
    color: #61796b;
    font-size: 14px;
    max-width: 600px;
}

.klppm-welcome-icon {
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

.klppm-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 30px;
}

.klppm-stat-card {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
    transition: .25s ease;
}

.klppm-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(32, 86, 60, .08);
}

.klppm-stat-top {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 17px;
}

.klppm-stat-icon {
    width: 45px;
    height: 45px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.klppm-stat-icon.waiting {
    background: #fff4df;
    color: #c58a23;
}

.klppm-stat-icon.approved {
    background: #e5f5eb;
    color: #2e8b57;
}

.klppm-stat-icon.rejected {
    background: #fdeaea;
    color: #c84b4b;
}

.klppm-stat-icon.revised {
    background: #eaf1ff;
    color: #557bc4;
}

.klppm-stat-label {
    font-size: 13px;
    font-weight: 600;
    color: #718078;
}

.klppm-stat-card h3 {
    font-size: 30px;
    font-weight: 800;
    color: #24382e;
    margin: 0 0 6px;
}

.klppm-stat-card small {
    font-size: 12px;
    color: #8b9890;
}

/* Section */

.klppm-section {
    margin-bottom: 30px;
}

.klppm-section-heading {
    margin-bottom: 17px;
}

.klppm-section-heading h4 {
    font-size: 18px;
    font-weight: 800;
    margin-bottom: 5px;
    color: #284635;
}

.klppm-section-heading p {
    color: #87938b;
    font-size: 13px;
    margin: 0;
}

/* Quick menu */

.klppm-quick-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
}

.klppm-quick-card {
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

.klppm-quick-card:hover {
    border-color: #9ac7aa;
    transform: translateY(-3px);
    box-shadow: 0 7px 20px rgba(32, 86, 60, .07);
    color: inherit;
}

.klppm-quick-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 21px;
    flex-shrink: 0;
}

.klppm-quick-icon.green {
    background: #e6f4eb;
    color: #328254;
}

.klppm-quick-icon.blue {
    background: #eaf1ff;
    color: #5277bc;
}

.klppm-quick-icon.orange {
    background: #fff1e3;
    color: #c27a35;
}

.klppm-quick-icon.purple {
    background: #f1eaff;
    color: #8562b5;
}

.klppm-quick-content {
    min-width: 0;
    flex: 1;
}

.klppm-quick-content h6 {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 5px;
    color: #304a3a;
}

.klppm-quick-content p {
    font-size: 11px;
    color: #89958d;
    margin: 0;
    line-height: 1.5;
}

.klppm-arrow {
    color: #9aaba0;
    font-size: 16px;
}

/* Main panels */

.klppm-main-grid {
    display: grid;
    grid-template-columns: 1.4fr 1fr;
    gap: 20px;
}

.klppm-panel {
    background: #fff;
    border: 1px solid #e8eee9;
    border-radius: 19px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(32, 86, 60, .04);
}

.klppm-panel-header {
    padding: 22px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #edf1ee;
}

.klppm-panel-header h4 {
    font-size: 16px;
    font-weight: 800;
    color: #294736;
    margin-bottom: 5px;
}

.klppm-panel-header p {
    font-size: 12px;
    color: #8a968e;
    margin: 0;
}

.klppm-panel-icon {
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

.klppm-panel-icon.history {
    background: #edf1fb;
    color: #617ab1;
}

.klppm-panel-body {
    padding: 8px 24px 20px;
}

/* Proposal list */

.klppm-proposal-item {
    padding: 18px 0;
    border-bottom: 1px solid #edf1ee;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
}

.klppm-proposal-item:last-child {
    border-bottom: none;
}

.klppm-proposal-info {
    min-width: 0;
    flex: 1;
}

.klppm-proposal-info h6 {
    font-size: 14px;
    font-weight: 700;
    color: #344b3c;
    margin-bottom: 9px;
    line-height: 1.5;
}

.klppm-proposal-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 13px;
}

.klppm-proposal-meta span {
    font-size: 11px;
    color: #89958d;
}

.klppm-proposal-meta i {
    margin-right: 4px;
}

.klppm-proposal-meta span:last-child i {
    font-size: 6px;
    color: #5eae79;
    vertical-align: middle;
}

.klppm-action-btn {
    background: #28764d;
    color: #fff;
    padding: 9px 14px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    transition: .2s;
}

.klppm-action-btn:hover {
    background: #205d3c;
    color: #fff;
}

/* History */

.klppm-history-item {
    display: flex;
    gap: 13px;
    padding: 17px 0;
    border-bottom: 1px solid #edf1ee;
}

.klppm-history-item:last-child {
    border-bottom: none;
}

.klppm-history-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #72b58b;
    margin-top: 5px;
    flex-shrink: 0;
}

.klppm-history-content {
    flex: 1;
    min-width: 0;
}

.klppm-history-content h6 {
    font-size: 13px;
    font-weight: 700;
    color: #344b3c;
    line-height: 1.5;
    margin-bottom: 9px;
}

.klppm-history-bottom {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
}

.klppm-history-bottom small {
    color: #8b968f;
    font-size: 11px;
}

.klppm-history-bottom small i {
    margin-right: 4px;
}

.klppm-status-badge {
    font-size: 10px;
    font-weight: 700;
    padding: 5px 10px;
    border-radius: 20px;
}

.klppm-status-badge.success {
    background: #e5f5eb;
    color: #328254;
}

.klppm-status-badge.danger {
    background: #fdeaea;
    color: #c44b4b;
}

.klppm-status-badge.warning {
    background: #fff3df;
    color: #bd8125;
}

.klppm-status-badge.info {
    background: #eaf1ff;
    color: #5578bc;
}

/* Empty state */

.klppm-empty {
    padding: 40px 15px;
    text-align: center;
}

.klppm-empty-icon {
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

.klppm-empty h6 {
    font-weight: 700;
    font-size: 14px;
    color: #52665a;
    margin-bottom: 6px;
}

.klppm-empty p {
    color: #98a39b;
    font-size: 12px;
    margin: 0;
}

/* Responsive */

@media (max-width: 1200px) {
    .klppm-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .klppm-quick-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 992px) {
    .klppm-main-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 576px) {
    .klppm-welcome {
        padding: 22px;
    }

    .klppm-welcome h2 {
        font-size: 21px;
    }

    .klppm-welcome-icon {
        display: none;
    }

    .klppm-stats {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .klppm-stat-card {
        padding: 16px;
    }

    .klppm-stat-top {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
    }

    .klppm-stat-card h3 {
        font-size: 25px;
    }

    .klppm-stat-label {
        font-size: 12px;
    }

    .klppm-quick-grid {
        grid-template-columns: 1fr;
    }

    .klppm-panel-header {
        padding: 18px;
    }

    .klppm-panel-body {
        padding: 8px 18px 18px;
    }

    .klppm-proposal-item {
        align-items: flex-start;
        flex-direction: column;
    }

    .klppm-action-btn {
        align-self: flex-start;
    }
}
</style>
@endsection

