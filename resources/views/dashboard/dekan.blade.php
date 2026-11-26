{{-- resources/views/dashboard/dekan.blade.php --}}
{{-- Dashboard Pimpinan Fakultas (role: dekan) --}}
{{-- Proposal difilter berdasarkan fakultas user (fakultas_id). --}}
{{-- Kalau fakultas user belum diisi, tampilkan semua proposal. --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Pimpinan Fakultas')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Pimpinan Fakultas' }}</h4>
        <p class="text-muted mb-0">Pantau proposal dosen dan laporan akhir di fakultas Anda.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Proposal Fakultas</h6>
                <h3 class="fw-bold mb-0">{{ $total ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Disetujui</h6>
                <h3 class="fw-bold mb-0">{{ $disetujui ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Ditolak</h6>
                <h3 class="fw-bold mb-0">{{ $ditolak ?? 0 }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('proposal.index') }}" class="btn btn-primary me-2 mb-2">Daftar Proposal</a>
        <a href="{{ route('laporan.akhir.index') }}" class="btn btn-outline-primary me-2 mb-2">Laporan Akhir</a>
        <a href="{{ route('dokumen.index') }}" class="btn btn-outline-primary me-2 mb-2">Dokumen</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    {{-- Proposal fakultas --}}
    <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-3">Proposal di Fakultas Anda</h6>
        @forelse ($proposals ?? [] as $p)
            <div class="mb-3">
                <strong>{{ $p->judul }}</strong><br>
                <small class="text-muted">
                    Pengusul: {{ $p->user->name ?? '-' }} ·
                    Status: {{ $p->status }}
                    @if ($p->status_pendanaan)
                        · Pendanaan: {{ $p->status_pendanaan }}
                    @endif
                </small><br>
                <a href="{{ route('proposal.tinjau', $p->id) }}" class="btn btn-sm btn-primary mt-1">Tinjau</a>
            </div>
        @empty
            <p class="text-muted mb-0">Belum ada proposal di fakultas Anda.</p>
        @endforelse
    </div></div>

    {{-- Laporan akhir fakultas --}}
    <div class="card mt-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Laporan Akhir Fakultas</h6>
        @forelse ($laporanAkhir ?? [] as $p)
            <div class="mb-3">
                <strong>{{ $p->judul }}</strong><br>
                <small class="text-muted">
                    Pengusul: {{ $p->user->name ?? '-' }} ·
                    Status: {{ $p->status }}
                </small><br>
                <a href="{{ route('laporan.akhir.download', $p->id) }}" class="btn btn-sm btn-outline-primary mt-1">Unduh Laporan Akhir</a>
            </div>
        @empty
            <p class="text-muted mb-0">Belum ada laporan akhir di fakultas Anda.</p>
        @endforelse
    </div></div>

</div>
@endsection
