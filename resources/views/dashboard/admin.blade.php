{{-- resources/views/dashboard/admin.blade.php --}}
{{-- Dashboard Administrator TI (role: admin) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Admin')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Admin' }}</h4>
        <p class="text-muted mb-0">Dashboard Administrator TI — E-Hibah Universitas YARSI.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Total Pengguna</h6>
                <h3 class="fw-bold mb-0">{{ $totalUsers ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Total Proposal</h6>
                <h3 class="fw-bold mb-0">{{ $totalProposals ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Disetujui</h6>
                <h3 class="fw-bold mb-0">{{ $disetujui ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Ditolak / Direvisi</h6>
                <h3 class="fw-bold mb-0">{{ ($ditolak ?? 0) + ($direvisi ?? 0) }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary me-2 mb-2">Kelola Pengguna</a>
        <a href="{{ route('admin.riwayatDosen') }}" class="btn btn-outline-primary me-2 mb-2">Riwayat Dosen</a>
        <a href="{{ route('dokumen.index') }}" class="btn btn-outline-primary me-2 mb-2">Dokumen</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    <div class="row g-3">
        {{-- Aktivitas terbaru --}}
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Aktivitas Terbaru</h6>
                @forelse ($recentLogs ?? [] as $log)
                    <div class="mb-2">
                        <strong>{{ $log->user->name ?? ('User #' . $log->user_id) }}</strong>
                        <span class="text-muted">— {{ $log->activity }}</span><br>
                        <small class="text-muted">{{ $log->created_at?->format('d M Y H:i') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada aktivitas tercatat.</p>
                @endforelse
            </div></div>
        </div>

        {{-- Pengguna terbaru --}}
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Pengguna Terbaru</h6>
                @forelse ($recentUsers ?? [] as $u)
                    <div class="mb-2">
                        <strong>{{ $u->name }}</strong>
                        <span class="badge bg-secondary">{{ $u->role }}</span><br>
                        <small class="text-muted">{{ $u->email }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada pengguna.</p>
                @endforelse
            </div></div>
        </div>
    </div>

</div>
@endsection
