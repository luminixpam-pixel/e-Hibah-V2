@extends('layouts.app')

@section('title', 'Ubah Entri Logbook')

@section('content')
<style>
    body { background-color: #f0fdf4; } /* Tema Hijau YARSI */

    .main-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: none;
    }

    .form-section {
        background: #fcfdfd;
        border-radius: 15px;
        border: 1px solid #eef2f3;
        padding: 25px;
    }
</style>

<div class="container py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">Ubah Entri Logbook</h4>
                <p class="text-muted small mb-0">{{ $proposal->judul }}</p>
            </div>
            <a href="{{ route('logbook.show', $proposal) }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>

        {{-- ALERT MESSAGES --}}
        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        <div class="form-section">
            <form action="{{ route('logbook.update', $logbook) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tanggal Kegiatan</label>
                        <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', $logbook->tanggal ? \Carbon\Carbon::parse($logbook->tanggal)->format('Y-m-d') : '') }}" required style="border-radius: 10px; padding: 10px;">
                        @error('tanggal')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold small text-muted">File Bukti Baru (PDF, maks 2 MB, opsional — file lama dihapus bila diganti)</label>
                        <input type="file" name="file_bukti" class="form-control @error('file_bukti') is-invalid @enderror" accept=".pdf" style="border-radius: 10px; padding: 10px;">
                        @error('file_bukti')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if ($logbook->file_bukti)
                            <div class="mt-2 small">
                                File saat ini:
                                <a href="{{ route('logbook.download', $logbook) }}" class="fw-bold">Unduh PDF</a>
                                <button type="button" class="btn btn-link btn-sm fw-bold p-0" data-preview-url="{{ route('logbook.preview', $logbook) }}" data-previewable="{{ in_array(strtolower(pathinfo($logbook->file_bukti ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="Bukti Logbook - {{ $logbook->uraian_kegiatan }}" data-download-url="{{ route('logbook.download', $logbook) }}"><i class="bi bi-eye"></i> Preview PDF</button>
                            </div>
                        @endif
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label fw-bold small text-muted">Uraian Kegiatan</label>
                        <textarea name="uraian_kegiatan" class="form-control @error('uraian_kegiatan') is-invalid @enderror" rows="3" required style="border-radius: 10px;">{{ old('uraian_kegiatan', $logbook->uraian_kegiatan) }}</textarea>
                        @error('uraian_kegiatan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" style="border-radius: 10px; height: 45px;">
                    <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
