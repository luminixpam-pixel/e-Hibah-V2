@extends('layouts.app')

@section('title', 'Buat Pengumuman')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Buat Pengumuman</h4>
            <p class="text-muted small mb-0">Pengumuman diterbitkan ke landing page. Bila tanggal terbit dikosongkan, diisi otomatis dengan waktu saat ini.</p>
        </div>
        <a href="{{ route('admin.pengumuman.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.pengumuman.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul') }}" required>
            @error('judul')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Isi</label>
            <textarea name="isi" class="form-control @error('isi') is-invalid @enderror" rows="5" required>{{ old('isi') }}</textarea>
            @error('isi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Kategori</label>
                <select name="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                    <option value="">— Pilih kategori —</option>
                    <option value="eksternal" {{ old('kategori') === 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                    <option value="internal" {{ old('kategori') === 'internal' ? 'selected' : '' }}>Internal</option>
                </select>
                @error('kategori')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal Terbit (opsional)</label>
                <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at') }}">
                @error('published_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">File Lampiran (opsional, PDF/DOC/DOCX, maks 2 MB)</label>
            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx">
            @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-success">Terbitkan Pengumuman</button>
    </form>
</div>
@endsection
