{{-- resources/views/proposal/undangan-anggota.blade.php --}}
{{-- Halaman konfirmasi undangan keanggotaan proposal (Route GET /anggota/undangan) --}}
@extends('layouts.app')

@section('title', 'Undangan Keanggotaan')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Undangan Keanggotaan</h4>
    <p class="text-muted">Proposal yang mengundang Anda sebagai anggota tim. Konfirmasi keikutsertaan Anda di sini.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h5 class="fw-bold mt-4 mb-3">Menunggu Jawaban ({{ $undanganMenunggu->count() }})</h5>

    @forelse ($undanganMenunggu as $m)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $m->proposal->judul }}</h6>
                <p class="text-muted small mb-3">
                    Ketua: {{ $m->proposal->nama_ketua }}
                    @if ($m->proposal->skema)
                        · Skema {{ $m->proposal->skema->kategori }}
                    @endif
                    @if ($m->proposal->no_pengajuan)
                        · {{ $m->proposal->no_pengajuan }}
                    @endif
                </p>
                <div class="d-flex gap-2">
                    <form action="{{ route('anggota.konfirmasi', $m->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="aksi" value="setuju">
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-lg me-1"></i>Setuju
                        </button>
                    </form>
                    <form action="{{ route('anggota.konfirmasi', $m->id) }}" method="POST" class="d-inline">
                        @csrf
                        <input type="hidden" name="aksi" value="tolak">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-lg me-1"></i>Tolak
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info">Tidak ada undangan yang menunggu jawaban.</div>
    @endforelse

    @if ($riwayat->count() > 0)
        <h5 class="fw-bold mt-5 mb-3">Riwayat Jawaban</h5>
        <div class="list-group">
            @foreach ($riwayat as $m)
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold">{{ $m->proposal->judul }}</div>
                        <small class="text-muted">{{ $m->proposal->nama_ketua }} · {{ $m->updated_at?->format('d M Y H:i') }}</small>
                    </div>
                    @if ($m->status_konfirmasi === 'Diterima')
                        <span class="badge bg-success">Diterima</span>
                    @else
                        <span class="badge bg-danger">Ditolak</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <a href="{{ route('dashboard.pengaju') }}" class="btn btn-outline-primary mt-4">Kembali ke Dashboard</a>
</div>
@endsection
