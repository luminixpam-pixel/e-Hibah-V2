@extends('layouts.app')

@section('title', 'Temuan Monev')

@section('content')
<style>
    body { background-color: #f0fdf4; } /* Tema Hijau YARSI */

    .main-card {
        background: white;
        border-radius: 20px;
        padding: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: none;
        min-width: 0;
    }

    .table thead th {
        background-color: #28a745;
        color: white;
        border: none;
        font-size: 13px;
        font-weight: 600;
        padding: 15px;
        text-align: center;
    }

    .table tbody td {
        padding: 15px;
        vertical-align: middle;
        font-size: 13px;
    }

    .badge-status {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
    }

    .badge-open {
        background-color: #fff3cd;
        color: #856404;
    }

    .badge-in-review {
        background-color: #e3f2fd;
        color: #1565c0;
    }

    .badge-closed {
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .btn-lihat {
        background-color: #e8f5e9;
        color: #2e7d32;
        font-weight: 700;
        font-size: 11px;
        border-radius: 8px;
        padding: 8px 15px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        border: none;
        text-decoration: none;
    }

    .btn-lihat:hover {
        background-color: #c8e6c9;
        color: #1b5e20;
        transform: translateY(-2px);
    }

    /* Tombol berkas RTL/bukti (UB5, butir 6): pola yang sama dengan
       monev-show (soft biru), ditumpuk vertikal di bawah "Lihat"
       supaya kolom Aksi tetap ramping. */
    .aksi-monev {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: stretch;
    }

    .btn-monev-file {
        background-color: #e3f2fd;
        color: #1565c0;
        font-weight: 700;
        font-size: 11px;
        border-radius: 8px;
        padding: 8px 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: 0.3s;
        border: none;
        text-decoration: none;
        white-space: nowrap;
    }

    .btn-monev-file:hover {
        background-color: #bbdefb;
        color: #0d47a1;
        transform: translateY(-2px);
    }

    /* TABEL (BUG #1 + BUG #2 uji ke-4, 28 Nov; UB5 uji ke-5):
       TANPA table-layout: fixed — kolom menyesuaikan isi (lebar
       minimum di <th>). Perbaikan UB5 bersifat struktural, bukan
       anggaran lebar: kolom Judul/Uraian dipangkas ke 120px
       (teksnya memang dipotong Str::limit), Status/Diperbarui ke
       lebar isi sebenarnya, tabel memakai .table-padat, dan
       pembungkus .container diganti .container-fluid px-0 supaya
       padding 12px kiri-kanan tidak memakan lebar tabel. Kolom
       Aksi sedikit dilebarkan (112px) untuk tombol berkas baru
       (butir 6) yang ditumpuk vertikal. */
    .table-responsive .table {
        width: 100%;
    }
</style>

<div class="container-fluid px-0 py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">Temuan Monitoring &amp; Evaluasi</h4>
                <p class="text-muted small">Temuan monev pada proposal yang sudah Disetujui, beserta tindak lanjut RTL.</p>
            </div>
            <div class="text-end">
                <div class="text-success small fw-bold">
                    <i class="bi bi-calendar-check me-1"></i> {{ now()->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        {{-- ALERT MESSAGES --}}
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        {{-- TABEL TEMUAN --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle table-compact table-padat">
                <thead>
                    <tr>
                        <th style="width: 32px; border-top-left-radius: 15px;">No</th>
                        <th class="text-start" style="min-width: 120px;">Judul Proposal &amp; Pengusul</th>
                        <th class="text-start" style="min-width: 120px;">Uraian Temuan</th>
                        <th style="min-width: 84px;">Status Temuan</th>
                        <th style="min-width: 72px;">Diperbarui</th>
                        <th style="min-width: 112px; border-top-right-radius: 15px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($findings as $index => $finding)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark mb-1">{{ Str::limit($finding->proposal->judul ?? '-', 70) }}</div>
                                <div class="text-primary small">
                                    <i class="bi bi-person-circle me-1"></i> {{ $finding->proposal->user->name ?? '-' }}
                                </div>
                            </td>
                            <td>{{ Str::limit($finding->uraian_temuan, 120) }}</td>
                            <td class="text-center">
                                @if ($finding->status_temuan === 'Open')
                                    <span class="badge-status badge-open">Open</span>
                                @elseif ($finding->status_temuan === 'In_Review')
                                    <span class="badge-status badge-in-review">In Review</span>
                                @else
                                    <span class="badge-status badge-closed">Closed</span>
                                @endif
                            </td>
                            <td class="text-center small text-muted text-nowrap">{{ $finding->updated_at->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <div class="aksi-monev">
                                    <a href="{{ route('monev.show', $finding->proposal_id) }}" class="btn-lihat shadow-sm">
                                        <i class="bi bi-eye-fill"></i>
                                        <span>Lihat</span>
                                    </a>
                                    {{-- Berkas RTL & bukti perbaikan (UB5, butir 6):
                                         tombol Preview + Unduh mengikuti pola
                                         ehibahPreview yang sama dengan halaman
                                         lain (atribut data-preview-*; handler
                                         global di layouts/app.blade.php yang
                                         membuka modal bersama). Hanya tampil
                                         bila berkasnya ada; route download/
                                         preview sudah ada (monev.download /
                                         monev.preview) dengan otorisasi yang
                                         sama seperti "Lihat" (canViewProposal
                                         di MonevController). --}}
                                    @if ($finding->file_rtl)
                                        <a href="{{ route('monev.download', [$finding, 'rtl']) }}" class="btn-monev-file shadow-sm">
                                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                                            <span>Unduh RTL</span>
                                        </a>
                                        <button type="button" class="btn-monev-file shadow-sm" data-preview-url="{{ route('monev.preview', [$finding, 'rtl']) }}" data-previewable="{{ in_array(strtolower(pathinfo($finding->file_rtl ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="RTL - {{ $finding->proposal->judul ?? '' }}" data-download-url="{{ route('monev.download', [$finding, 'rtl']) }}">
                                            <i class="bi bi-eye-fill"></i>
                                            <span>Preview RTL</span>
                                        </button>
                                    @endif
                                    @if ($finding->bukti_perbaikan)
                                        <a href="{{ route('monev.download', [$finding, 'bukti']) }}" class="btn-monev-file shadow-sm">
                                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                                            <span>Unduh Bukti</span>
                                        </a>
                                        <button type="button" class="btn-monev-file shadow-sm" data-preview-url="{{ route('monev.preview', [$finding, 'bukti']) }}" data-previewable="{{ in_array(strtolower(pathinfo($finding->bukti_perbaikan ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="Bukti Perbaikan - {{ $finding->proposal->judul ?? '' }}" data-download-url="{{ route('monev.download', [$finding, 'bukti']) }}">
                                            <i class="bi bi-eye-fill"></i>
                                            <span>Preview Bukti</span>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Belum ada temuan monev.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
