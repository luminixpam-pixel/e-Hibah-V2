{{-- resources/views/proposal/cek-kuota.blade.php --}}
{{-- Halaman cek kuota dosen (Route GET /cek-kuota-dosen) --}}
{{-- Dibuat saat perapian: route ini dulu memanggil method cekKuota() yang tidak ada (500). --}}
@extends('layouts.app')

@section('title', 'Cek Kuota Dosen')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Cek Kuota Dosen</h4>
    <p class="text-muted">Setiap dosen maksimal terlibat dalam <strong>3 proposal</strong> (sebagai ketua atau anggota).</p>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Sebagai Ketua</h6>
                <h3 class="fw-bold mb-0">{{ $sebagaiKetua }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Sebagai Anggota</h6>
                <h3 class="fw-bold mb-0">{{ $sebagaiAnggota }}</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Total Terlibat</h6>
                <h3 class="fw-bold mb-0">{{ $total }} / 3</h3>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <h6 class="text-muted">Sisa Kuota</h6>
                <h3 class="fw-bold mb-0">{{ $sisa }}</h3>
            </div></div>
        </div>
    </div>

    @if ($sisa > 0)
        <div class="alert alert-success">
            {{ $nama }} masih bisa terlibat dalam <strong>{{ $sisa }} proposal</strong> lagi.
            <a href="{{ route('proposal.create') }}" class="alert-link">Ajukan proposal baru</a>.
        </div>
    @else
        <div class="alert alert-warning">
            {{ $nama }} sudah mencapai batas maksimal <strong>3 proposal</strong>. Tidak bisa mengajukan atau ditambahkan ke proposal baru sampai ada proposal yang selesai/dihapus.
        </div>
    @endif

    <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary">Lihat Daftar Proposal</a>
</div>
@endsection
