@extends('layouts.app')

@section('title', 'Detail Laporan Error')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $laporan->judul }}</h4>
            <p class="text-muted small mb-0">
                Dilaporkan oleh {{ $laporan->user?->name ?? '-' }} ({{ $laporan->user?->role ?? '-' }})
                pada {{ $laporan->created_at->format('d M Y H:i') }}
            </p>
        </div>
        <a href="{{ route('admin.error-report.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <p class="mb-0">{{ $laporan->deskripsi }}</p>
        </div>
    </div>

    <form action="{{ route('admin.error-report.update', $laporan) }}" method="POST">
        @csrf
        @method('PATCH')
        <div class="row align-items-end">
            <div class="col-md-4 mb-3">
                <label class="form-label">Status Penanganan</label>
                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="Open" {{ old('status', $laporan->status) === 'Open' ? 'selected' : '' }}>Open</option>
                    <option value="Diproses" {{ old('status', $laporan->status) === 'Diproses' ? 'selected' : '' }}>Diproses</option>
                    <option value="Selesai" {{ old('status', $laporan->status) === 'Selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
                @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-8 mb-3">
                <button type="submit" class="btn btn-success">Simpan Status</button>
            </div>
        </div>
    </form>
</div>
@endsection
