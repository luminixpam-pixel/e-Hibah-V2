@extends('layouts.app')

@section('title', 'Laporkan Error')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Laporkan Error</h4>
        <p class="text-muted small mb-0">Menemukan gangguan atau kesalahan pada sistem? Laporkan di sini agar dapat ditindaklanjuti oleh admin.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    <form action="{{ route('error-report.store') }}" method="POST">
        @csrf
        <div class="mb-3">
            <label class="form-label">Judul Error</label>
            <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul') }}" placeholder="Contoh: Tidak bisa mengunggah proposal" required>
            @error('judul')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label">Deskripsi</label>
            <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" rows="5" placeholder="Jelaskan apa yang terjadi, di halaman mana, dan langkah apa yang dilakukan sebelum error muncul." required>{{ old('deskripsi') }}</textarea>
            @error('deskripsi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-success">Kirim Laporan</button>
    </form>
</div>
@endsection
