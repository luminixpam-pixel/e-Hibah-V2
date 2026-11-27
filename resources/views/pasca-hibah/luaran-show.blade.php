@extends('layouts.app')

@section('title', 'Luaran — ' . Str::limit($proposal->judul, 40))

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
</style>

<div class="container py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">Luaran Penelitian / Pengabdian</h4>
                <p class="text-muted small mb-1">{{ $proposal->judul }}</p>
                <p class="text-muted small mb-0">
                    Pengusul: {{ $proposal->user->name ?? '-' }}
                    @if ($proposal->no_pengajuan)
                        &middot; No. Pengajuan: {{ $proposal->no_pengajuan }}
                    @endif
                </p>
            </div>
            <a href="{{ route('luaran.index') }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Kembali
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
                <i class="bi bi-exclamation-circle-fill me-2"></i> {{ session('error') }}
            </div>
        @endif

        {{-- FORM TAMBAH (HANYA PENGAJU PEMILIK) --}}
        @if ($isOwner)
        <div class="form-section mb-5">
            <h5 class="fw-bold text-dark mb-4">Tambah Luaran</h5>
            <form action="{{ route('luaran.store', $proposal) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Jenis Luaran</label>
                        <select name="jenis" class="form-select @error('jenis') is-invalid @enderror" required style="border-radius: 10px; padding: 10px;">
                            <option value="">-- Pilih Jenis --</option>
                            @foreach (['Jurnal', 'Prosiding', 'HKI', 'Paten', 'Buku'] as $jenis)
                                <option value="{{ $jenis }}" {{ old('jenis') == $jenis ? 'selected' : '' }}>{{ $jenis }}</option>
                            @endforeach
                        </select>
                        @error('jenis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Status</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required style="border-radius: 10px; padding: 10px;">
                            <option value="">-- Pilih Status --</option>
                            @foreach (['Draft', 'Submitted', 'Published', 'Granted'] as $status)
                                <option value="{{ $status }}" {{ old('status') == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">File (PDF, maks 2 MB, opsional)</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf" style="border-radius: 10px; padding: 10px;">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label fw-bold small text-muted">Judul Luaran</label>
                        <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul') }}" placeholder="Contoh: Sistem Informasi Laboratorium Berbasis Web" maxlength="255" required style="border-radius: 10px; padding: 10px;">
                        @error('judul')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" style="border-radius: 10px; height: 45px;">
                    <i class="bi bi-plus-circle me-2"></i>Simpan Luaran
                </button>
            </form>
        </div>
        @endif

        {{-- TABEL LUARAN --}}
        <h5 class="fw-bold text-dark mb-3">Daftar Luaran</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th width="5%" style="border-top-left-radius: 15px;">No</th>
                        <th width="12%">Jenis</th>
                        <th class="text-start" width="38%">Judul</th>
                        <th width="12%">Status</th>
                        <th width="13%">File</th>
                        <th width="20%" style="border-top-right-radius: 15px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($luarans as $index => $luaran)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td class="text-center"><span class="badge bg-success">{{ $luaran->jenis }}</span></td>
                            <td>{{ $luaran->judul }}</td>
                            <td class="text-center"><span class="badge bg-secondary">{{ $luaran->status }}</span></td>
                            <td class="text-center">
                                @if ($luaran->file)
                                    <a href="{{ route('luaran.download', $luaran) }}" class="btn btn-sm btn-outline-primary fw-bold px-3" style="border-radius: 8px;">
                                        <i class="bi bi-download me-1"></i> Unduh PDF
                                    </a>
                                @else
                                    <span class="badge-empty">Tanpa file</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if ($isOwner)
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="{{ route('luaran.edit', $luaran) }}" class="btn btn-sm btn-outline-warning fw-bold px-3" style="border-radius: 8px;">
                                            <i class="bi bi-pencil me-1"></i> Ubah
                                        </a>
                                        <form action="{{ route('luaran.destroy', $luaran) }}" method="POST" data-ehibah-confirm="Hapus luaran ini beserta filenya?" data-ehibah-confirm-title="Hapus Luaran" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-3" style="border-radius: 8px;">
                                                <i class="bi bi-trash me-1"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                Belum ada luaran untuk proposal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
