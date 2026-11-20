{{-- resources/views/dashboard/lppm.blade.php --}}
{{-- Dashboard Pengelola LPPM (role: lppm) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard LPPM')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Pengelola LPPM' }}</h4>
        <p class="text-muted mb-0">Kelola plotting reviewer dan pantau progres penilaian proposal.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Lolos Administrasi</h6>
                <h3 class="fw-bold mb-0">{{ $lolosAdmin ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Sedang Direview</h6>
                <h3 class="fw-bold mb-0">{{ $sedangDireview ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Review Selesai</h6>
                <h3 class="fw-bold mb-0">{{ $reviewSelesai ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Disetujui</h6>
                <h3 class="fw-bold mb-0">{{ $disetujui ?? 0 }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('monitoring.proposalPerluDireview') }}" class="btn btn-primary me-2 mb-2">Proposal Perlu Direview</a>
        <a href="{{ route('monitoring.proposalSedangDireview') }}" class="btn btn-outline-primary me-2 mb-2">Proposal Sedang Direview</a>
        <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary me-2 mb-2">Daftar Proposal</a>
        <a href="{{ route('dokumen.index') }}" class="btn btn-outline-primary me-2 mb-2">Dokumen</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    {{-- Siap plotting --}}
    <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-3">Siap Plotting Reviewer (Lolos Administrasi)</h6>
        @forelse ($siapPlotting ?? [] as $p)
            <div class="mb-3">
                <strong>{{ $p->judul }}</strong><br>
                <small class="text-muted">
                    Pengusul: {{ $p->user->name ?? '-' }} ·
                    Status: {{ $p->status }}
                </small><br>
                <a href="{{ route('proposal.tinjau', $p->id) }}" class="btn btn-sm btn-primary mt-1">Tugaskan Reviewer</a>
            </div>
        @empty
            <p class="text-muted mb-0">Tidak ada proposal yang menunggu plotting reviewer.</p>
        @endforelse
    </div></div>

</div>
@endsection
