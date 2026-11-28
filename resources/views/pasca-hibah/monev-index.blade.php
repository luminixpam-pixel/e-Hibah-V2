@extends('layouts.app')

@section('title', 'Temuan Monev')

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

    /* TABEL (BUG #1 + BUG #2 uji ke-4, 28 Nov): TANPA
       table-layout: fixed — kolom menyesuaikan isi (lebar minimum
       di <th>). Anggaran lebar minimum total ~700px di konten
       efektif ~950px (viewport 1341 - sidebar 220 - padding konten
       & .main-card 20px), jadi header "Status Temuan" tidak lagi
       terpotong di tepi kanan — bahkan saat tabel masih kosong
       ("Belum ada temuan monev."), karena lebar tabel saat kosong
       hanya ditentukan header: Judul 220→150px, Uraian 200→150px,
       Status 100px (badge "In Review" muat), Diperbarui 96→80px
       (tanggal nowrap), Aksi 110→100px (tombol "Lihat"). */
    .table-responsive .table {
        width: 100%;
    }
</style>

<div class="container py-4">
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
            <table class="table table-hover align-middle table-compact">
                <thead>
                    <tr>
                        <th style="width: 36px; border-top-left-radius: 15px;">No</th>
                        <th class="text-start" style="min-width: 150px;">Judul Proposal &amp; Pengusul</th>
                        <th class="text-start" style="min-width: 150px;">Uraian Temuan</th>
                        <th style="min-width: 100px;">Status Temuan</th>
                        <th style="min-width: 80px;">Diperbarui</th>
                        <th style="min-width: 100px; border-top-right-radius: 15px;">Aksi</th>
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
                                <a href="{{ route('monev.show', $finding->proposal_id) }}" class="btn-lihat shadow-sm">
                                    <i class="bi bi-eye-fill"></i>
                                    <span>Lihat</span>
                                </a>
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
