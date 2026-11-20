{{-- resources/views/dashboard/kepala-lppm.blade.php --}}
{{-- Dashboard Kepala LPPM (role: kepala_lppm) --}}
{{-- Tampilan sederhana — nanti dimodifikasi frontend. --}}
@extends('layouts.app')

@section('title', 'Dashboard Kepala LPPM')

@section('content')
<div class="container mt-4 mb-5">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Selamat datang, {{ $user->name ?? 'Kepala LPPM' }}</h4>
        <p class="text-muted mb-0">Tetapkan keputusan pendanaan berdasarkan rekap nilai reviewer.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Menunggu Keputusan</h6>
                <h3 class="fw-bold mb-0">{{ $menungguKeputusan ?? 0 }}</h3>
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
                <h6 class="text-muted">Ditolak</h6>
                <h3 class="fw-bold mb-0">{{ $ditolak ?? 0 }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Direvisi</h6>
                <h3 class="fw-bold mb-0">{{ $direvisi ?? 0 }}</h3>
            </div></div>
        </div>
    </div>

    {{-- Menu cepat --}}
    <div class="card mb-4"><div class="card-body">
        <h6 class="fw-bold mb-3">Menu Cepat</h6>
        <a href="{{ route('proposal.index') }}" class="btn btn-primary me-2 mb-2">Daftar Proposal</a>
        <a href="{{ route('monitoring.reviewSelesai') }}" class="btn btn-outline-primary me-2 mb-2">Review Selesai</a>
        <a href="{{ route('dokumen.index') }}" class="btn btn-outline-primary me-2 mb-2">Dokumen</a>
        <a href="{{ route('profile.show') }}" class="btn btn-outline-secondary mb-2">Profil Saya</a>
    </div></div>

    <div class="row g-3">
        {{-- Siap diputuskan --}}
        <div class="col-md-7">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Siap Diputuskan (Review Selesai)</h6>
                @forelse ($siapDiputuskan ?? [] as $p)
                    <div class="mb-3">
                        <strong>{{ $p->judul }}</strong><br>
                        <small class="text-muted">
                            Pengusul: {{ $p->user->name ?? '-' }} ·
                            Status: {{ $p->status }}
                        </small><br>
                        <a href="{{ route('proposal.tinjau', $p->id) }}" class="btn btn-sm btn-primary mt-1">Tinjau & Putuskan</a>
                    </div>
                @empty
                    <p class="text-muted mb-0">Tidak ada proposal yang menunggu keputusan.</p>
                @endforelse
            </div></div>
        </div>

        {{-- Riwayat keputusan --}}
        <div class="col-md-5">
            <div class="card"><div class="card-body">
                <h6 class="fw-bold mb-3">Riwayat Keputusan</h6>
                @forelse ($riwayatKeputusan ?? [] as $p)
                    <div class="mb-2">
                        <strong>{{ $p->judul }}</strong><br>
                        <span class="badge bg-info">{{ $p->status_pendanaan }}</span>
                        <small class="text-muted">{{ $p->updated_at?->format('d M Y') }}</small>
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada keputusan yang dicatat.</p>
                @endforelse
            </div></div>
        </div>
    </div>

</div>
@endsection
