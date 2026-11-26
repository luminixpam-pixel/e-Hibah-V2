@extends('layouts.app')

@section('title', 'Laporan Error')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Laporan Error</h4>
        <p class="text-muted small mb-0">Daftar laporan error dari pengguna sistem.</p>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if ($laporan->isEmpty())
        <div class="alert alert-info" role="alert">Belum ada laporan error.</div>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Pelapor</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($laporan as $item)
                    <tr>
                        <td>{{ $item->judul }}</td>
                        <td>{{ $item->user?->name ?? '-' }} ({{ $item->user?->role ?? '-' }})</td>
                        <td>{{ $item->status }}</td>
                        <td>{{ $item->created_at->format('d M Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.error-report.show', $item) }}" class="btn btn-sm btn-primary">Lihat</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
