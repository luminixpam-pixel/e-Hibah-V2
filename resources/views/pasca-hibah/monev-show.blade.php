@extends('layouts.app')

@section('title', 'Temuan Monev — ' . Str::limit($proposal->judul, 40))

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

    .finding-card {
        background: #fcfdfd;
        border-radius: 15px;
        border: 1px solid #eef2f3;
        padding: 25px;
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

    .btn-unduh {
        background-color: #e3f2fd;
        color: #1565c0;
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

    .btn-unduh:hover {
        background-color: #bbdefb;
        color: #0d47a1;
        transform: translateY(-2px);
    }

    .badge-empty {
        background-color: #f8f9fa;
        color: #adb5bd;
        border: 1px dashed #dee2e6;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
    }
</style>

<div class="container py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">Temuan Monev</h4>
                <p class="text-muted small mb-0">{{ $proposal->judul }}</p>
                <p class="text-muted small mb-0">
                    <i class="bi bi-person-circle me-1"></i> {{ $proposal->user->name ?? '-' }}
                    @if ($proposal->no_pengajuan)
                        &middot; No. Pengajuan: {{ $proposal->no_pengajuan }}
                    @endif
                    &middot; Status proposal: <span class="fw-bold text-success">{{ $proposal->status }}</span>
                </p>
            </div>
            <a href="{{ route('monev.index') }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Daftar Temuan
            </a>
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

        @if ($errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-4" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM BUAT TEMUAN (KHUSUS LPPM) --}}
        @if ($user->role === 'lppm')
            <div class="form-section mb-5">
                <h5 class="fw-bold text-dark mb-4">Catat Temuan Monev Baru</h5>
                <form action="{{ route('monev.store', $proposal) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Uraian Temuan</label>
                        <textarea name="uraian_temuan" class="form-control @error('uraian_temuan') is-invalid @enderror" rows="3" placeholder="Jelaskan temuan monitoring &amp; evaluasi..." required style="border-radius: 10px;">{{ old('uraian_temuan') }}</textarea>
                        @error('uraian_temuan')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold small text-muted">Rekomendasi / Rencana Tindak Lanjut (RTL)</label>
                        <textarea name="rekomendasi" class="form-control @error('rekomendasi') is-invalid @enderror" rows="3" placeholder="Tuliskan rekomendasi perbaikan yang harus dilakukan pengaju..." required style="border-radius: 10px;">{{ old('rekomendasi') }}</textarea>
                        @error('rekomendasi')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" style="border-radius: 10px; height: 45px;">
                        <i class="bi bi-plus-circle me-2"></i>Simpan Temuan
                    </button>
                </form>
            </div>
        @endif

        {{-- DAFTAR TEMUAN --}}
        <h5 class="fw-bold text-dark mb-3">Daftar Temuan ({{ $findings->count() }})</h5>

        @forelse ($findings as $finding)
            <div class="finding-card mb-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <div class="fw-bold text-dark">{{ $finding->uraian_temuan }}</div>
                        <div class="text-muted small mt-1">
                            <span class="fw-bold">Rekomendasi:</span> {{ $finding->rekomendasi }}
                        </div>
                        <div class="text-muted small mt-1">Dicatat: {{ $finding->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    @if ($finding->status_temuan === 'Open')
                        <span class="badge-status badge-open">Open</span>
                    @elseif ($finding->status_temuan === 'In_Review')
                        <span class="badge-status badge-in-review">In Review</span>
                    @else
                        <span class="badge-status badge-closed">Closed</span>
                    @endif
                </div>

                {{-- BERKAS RTL & BUKTI --}}
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @if ($finding->file_rtl)
                        <a href="{{ route('monev.download', [$finding, 'rtl']) }}" class="btn-unduh shadow-sm">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            <span>Unduh RTL</span>
                        </a>
                        <button type="button" class="btn-unduh shadow-sm" data-preview-url="{{ route('monev.preview', [$finding, 'rtl']) }}" data-previewable="{{ in_array(strtolower(pathinfo($finding->file_rtl ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="RTL - {{ $proposal->judul }}" data-download-url="{{ route('monev.download', [$finding, 'rtl']) }}">
                            <i class="bi bi-eye-fill"></i>
                            <span>Preview RTL</span>
                        </button>
                    @else
                        <span class="badge-empty"><i class="bi bi-info-circle me-1"></i> RTL belum dikirim</span>
                    @endif

                    @if ($finding->bukti_perbaikan)
                        <a href="{{ route('monev.download', [$finding, 'bukti']) }}" class="btn-unduh shadow-sm">
                            <i class="bi bi-file-earmark-arrow-down-fill"></i>
                            <span>Unduh Bukti Perbaikan</span>
                        </a>
                        <button type="button" class="btn-unduh shadow-sm" data-preview-url="{{ route('monev.preview', [$finding, 'bukti']) }}" data-previewable="{{ in_array(strtolower(pathinfo($finding->bukti_perbaikan ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="Bukti Perbaikan - {{ $proposal->judul }}" data-download-url="{{ route('monev.download', [$finding, 'bukti']) }}">
                            <i class="bi bi-eye-fill"></i>
                            <span>Preview Bukti Perbaikan</span>
                        </button>
                    @else
                        <span class="badge-empty"><i class="bi bi-info-circle me-1"></i> Bukti perbaikan belum dikirim</span>
                    @endif
                </div>

                {{-- FORM KIRIM RTL (PENGAJU PEMILIK, TEMUAN OPEN / IN_REVIEW) --}}
                @if ($user->role === 'pengaju' && (int) $proposal->user_id === (int) $user->id && in_array($finding->status_temuan, ['Open', 'In_Review'], true))
                    <form action="{{ route('monev.kirimRtl', $finding) }}" method="POST" enctype="multipart/form-data" class="border-top pt-3">
                        @csrf
                        <h6 class="fw-bold text-dark mb-3">Kirim RTL &amp; Bukti Perbaikan (PDF, maks 2 MB)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">File RTL (PDF)</label>
                                <input type="file" name="file_rtl" class="form-control @error('file_rtl') is-invalid @enderror" accept=".pdf" required style="border-radius: 10px; padding: 10px;">
                                @error('file_rtl')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold small text-muted">Bukti Perbaikan (PDF)</label>
                                <input type="file" name="bukti_perbaikan" class="form-control @error('bukti_perbaikan') is-invalid @enderror" accept=".pdf" required style="border-radius: 10px; padding: 10px;">
                                @error('bukti_perbaikan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                            <i class="bi bi-cloud-arrow-up-fill me-2"></i>Kirim RTL
                        </button>
                    </form>
                @endif

                {{-- VERIFIKASI LPPM (TEMUAN IN_REVIEW) --}}
                @if ($user->role === 'lppm' && $finding->status_temuan === 'In_Review')
                    <form action="{{ route('monev.verifikasi', $finding) }}" method="POST" class="border-top pt-3">
                        @csrf
                        <h6 class="fw-bold text-dark mb-3">Verifikasi RTL</h6>
                        <div class="d-flex gap-2">
                            <button type="submit" name="keputusan" value="tutup" class="btn btn-success px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                                <i class="bi bi-check-circle-fill me-2"></i>Tutup Temuan
                            </button>
                            <button type="submit" name="keputusan" value="kembalikan" class="btn btn-warning px-4 fw-bold shadow-sm" style="border-radius: 10px;">
                                <i class="bi bi-arrow-counterclockwise me-2"></i>Kembalikan (RTL Kurang)
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @empty
            <div class="text-center py-5 text-muted">
                Belum ada temuan monev untuk proposal ini.
            </div>
        @endforelse
    </div>
</div>
@endsection
