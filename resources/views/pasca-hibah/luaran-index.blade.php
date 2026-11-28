@extends('layouts.app')

@section('title', 'Luaran Pasca-Hibah')

@section('content')
<style>
    body { background-color: #f0fdf4; } /* Tema Hijau YARSI */

    .main-card {
        background: white;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: none;
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

    .badge-empty {
        background-color: #f8f9fa;
        color: #adb5bd;
        border: 1px dashed #dee2e6;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
    }

    /* TABEL (BUG #1 uji ke-4, 28 Nov): TANPA table-layout: fixed —
       kolom menyesuaikan isi (lebar minimum di <th>). Anggaran lebar
       minimum total ~605px di konten efektif ~950px (viewport 1341
       - sidebar 220 - padding konten & .main-card 20px): Judul
       240→170px, No. Pengajuan 120→104px, Jumlah 110→96px (badge
       "Belum ada luaran" yang nowrap tetap muat karena kolom
       mengembang mengikuti badge), Aksi 140→96px (tombol dipersingkat
       "Lihat Luaran" → "Lihat"). */
    .table-responsive .table {
        width: 100%;
    }
</style>

<div class="container py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">Luaran Pasca-Hibah</h4>
                <p class="text-muted small">Proposal yang disetujui pendanaannya. Pilih proposal untuk melihat dan mencatat luarannya (jurnal, prosiding, HKI, paten, buku).</p>
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
                <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        {{-- TABEL PROPOSAL --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle table-compact">
                <thead>
                    <tr>
                        <th style="width: 36px; border-top-left-radius: 15px;">No</th>
                        <th class="text-start" style="min-width: 170px;">Judul & Pengusul</th>
                        <th style="min-width: 104px;">No. Pengajuan</th>
                        <th style="min-width: 96px;">Jumlah Luaran</th>
                        <th style="min-width: 96px; border-top-right-radius: 15px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $index => $proposal)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark mb-1">{{ Str::limit($proposal->judul, 70) }}</div>
                                <div class="text-primary small">
                                    <i class="bi bi-person-circle me-1"></i> {{ $proposal->user->name ?? '-' }}
                                </div>
                            </td>
                            <td class="text-center">{{ $proposal->no_pengajuan ?? '-' }}</td>
                            <td class="text-center">
                                @if (($jumlah[$proposal->id] ?? 0) > 0)
                                    <span class="badge bg-success">{{ $jumlah[$proposal->id] }} luaran</span>
                                @else
                                    <span class="badge-empty">Belum ada luaran</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('luaran.show', $proposal) }}" class="btn btn-sm btn-success fw-bold px-2" style="border-radius: 8px;">
                                    <i class="bi bi-award me-1"></i> Lihat
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                Belum ada proposal yang disetujui.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
