{{-- resources/views/dashboard/pengaju.blade.php --}}
{{-- Dashboard Dosen / Ketua Pengusul & Anggota Tim (role: pengaju) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Pengaju')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Pengaju' }}</h4>
        <p class="text-muted mb-0">Kelola pengajuan hibah Anda di E-Hibah Universitas YARSI.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Proposal Saya</h6>
                <h3 class="fw-bold mb-0">{{ $total ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Draft</h6>
                <h3 class="fw-bold mb-0">{{ $draft ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Dikirim</h6>
                <h3 class="fw-bold mb-0">{{ $dikirim ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Disetujui / Ditolak</h6>
                <h3 class="fw-bold mb-0">{{ ($disetujui ?? 0) }} / {{ ($ditolak ?? 0) }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('proposal.create') }}" class="btn btn-primary me-2 mb-2">Buat Proposal Baru</a>
        <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary me-2 mb-2">Daftar Proposal Saya</a>
        <a href="{{ route('laporan.kemajuan.index') }}" class="btn btn-outline-primary me-2 mb-2">Laporan Kemajuan</a>
        <a href="{{ route('laporan.akhir.index') }}" class="btn btn-outline-primary me-2 mb-2">Laporan Akhir</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    <div class="row g-3">
        {{-- Proposal saya --}}
        <div class="col-md-7">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Proposal Saya</h6>
                @forelse ($myProposals ?? [] as $p)
                    <div class="mb-2">
                        <strong>{{ $p->judul }}</strong><br>
                        <span class="badge bg-secondary">{{ $p->status }}</span>
                        @if ($p->status_pendanaan)
                            <span class="badge bg-info">{{ $p->status_pendanaan }}</span>
                        @endif
                        <small class="text-muted">{{ $p->created_at?->format('d M Y') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada proposal. <a href="{{ route('proposal.create') }}">Buat proposal pertama Anda</a>.</p>
                @endforelse
            </div></div>
        </div>

        {{-- Notifikasi --}}
        <div class="col-md-5">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Notifikasi Terbaru</h6>
                @forelse ($notifications ?? [] as $n)
                    <div class="mb-2">
                        <strong>{{ $n->title }}</strong><br>
                        <small class="text-muted">{{ $n->created_at?->format('d M Y H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Tidak ada notifikasi.</p>
                @endforelse
            </div></div>
        </div>
    </div>

</div>
@endsection
