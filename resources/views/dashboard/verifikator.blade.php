{{-- resources/views/dashboard/verifikator.blade.php --}}
{{-- Dashboard Verifikator Administrasi (role: verifikator) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Verifikator')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Verifikator' }}</h4>
        <p class="text-muted mb-0">Periksa kelengkapan dokumen administrasi proposal yang masuk.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Antrean Verifikasi</h6>
                <h3 class="fw-bold mb-0">{{ $antrean_count ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Lolos Administrasi</h6>
                <h3 class="fw-bold mb-0">{{ $lolos ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Revisi Administrasi</h6>
                <h3 class="fw-bold mb-0">{{ $revisi ?? 0 }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('proposal.index') }}" class="btn btn-primary me-2 mb-2">Daftar Proposal</a>
        <a href="{{ route('dokumen.index') }}" class="btn btn-outline-primary me-2 mb-2">Dokumen</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    {{-- Antrean proposal masuk --}}
    <div class="card"><div class="card-body">
        <h6 class="fw-bold mb-3">Antrean Proposal Masuk (Status: Dikirim)</h6>
        @forelse ($antrean ?? [] as $p)
            <div class="mb-3">
                <strong>{{ $p->judul }}</strong><br>
                <small class="text-muted">
                    Pengusul: {{ $p->user->name ?? '-' }} ·
                    Dikirim: {{ $p->created_at?->format('d M Y') }}
                </small><br>
                <a href="{{ route('proposal.tinjau', $p->id) }}" class="btn btn-sm btn-primary mt-1">Periksa Berkas</a>
            </div>
        @empty
            <p class="text-muted mb-0">Tidak ada proposal yang menunggu verifikasi.</p>
        @endforelse
    </div></div>

</div>
@endsection
