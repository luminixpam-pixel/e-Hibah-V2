{{-- resources/views/dashboard/reviewer.blade.php --}}
{{-- Dashboard Reviewer (role: reviewer) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Reviewer')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Reviewer' }}</h4>
        <p class="text-muted mb-0">Dashboard khusus reviewer — nilai proposal yang ditugaskan kepada Anda.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Total Tugas Review</h6>
                <h3 class="fw-bold mb-0">{{ $totalTugas ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Menunggu Dinilai</h6>
                <h3 class="fw-bold mb-0">{{ $menunggu ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Selesai Dinilai</h6>
                <h3 class="fw-bold mb-0">{{ $selesai ?? 0 }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('monitoring.proposalPerluDireview') }}" class="btn btn-primary me-2 mb-2">Proposal Perlu Direview</a>
        <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary me-2 mb-2">Daftar Proposal</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    {{-- Tugas review --}}
    <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-3">Tugas Review Saya</h6>
        @forelse ($tugasReview ?? [] as $p)
            <div class="mb-3">
                <strong>{{ $p->judul }}</strong><br>
                <small class="text-muted">
                    Pengusul: {{ $p->user->name ?? '-' }} ·
                    Status: {{ $p->status }}
                    @if ($p->review_deadline)
                        · Tenggat: {{ \Carbon\Carbon::parse($p->review_deadline)->format('d M Y') }}
                    @endif
                </small><br>
                <a href="{{ route('reviewer.isi-review', $p->id) }}" class="btn btn-sm btn-primary mt-1">Isi Review</a>
            </div>
        @empty
            <p class="text-muted mb-0">Belum ada proposal yang ditugaskan kepada Anda.</p>
        @endforelse
    </div></div>

</div>
@endsection
