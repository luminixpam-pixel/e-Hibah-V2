@extends('layouts.app')

@section('title', 'Ubah Pengumuman')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Ubah Pengumuman</h4>
            <p class="text-muted small mb-0">{{ $pengumuman->judul }}</p>
        </div>
        <a href="{{ route('admin.pengumuman.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <form action="{{ route('admin.pengumuman.update', $pengumuman) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul', $pengumuman->judul) }}" required>
            @error('judul')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Isi</label>
            <textarea name="isi" class="form-control @error('isi') is-invalid @enderror" rows="5" required>{{ old('isi', $pengumuman->isi) }}</textarea>
            @error('isi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Kategori</label>
                <select name="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                    <option value="eksternal" {{ old('kategori', $pengumuman->kategori) === 'eksternal' ? 'selected' : '' }}>Eksternal</option>
                    <option value="internal" {{ old('kategori', $pengumuman->kategori) === 'internal' ? 'selected' : '' }}>Internal</option>
                </select>
                @error('kategori')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Tanggal Terbit</label>
                <input type="datetime-local" name="published_at" class="form-control @error('published_at') is-invalid @enderror" value="{{ old('published_at', $pengumuman->published_at ? $pengumuman->published_at->format('Y-m-d\TH:i') : '') }}">
                @error('published_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">File Lampiran Baru (opsional, PDF/DOC/DOCX, maks 2 MB — file lama dihapus bila diganti)</label>
            <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx">
            @error('file')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            @if ($pengumuman->file)
                <div class="mt-2 small">
                    File saat ini:
                    <a href="{{ asset('storage/' . $pengumuman->file) }}" target="_blank">Unduh File</a>
                </div>
            @endif
        </div>

        <button type="submit" class="btn btn-success">Simpan Perubahan</button>
    </form>
</div>
@endsection
