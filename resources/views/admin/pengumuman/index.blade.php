@extends('layouts.app')

@section('title', 'Kelola Pengumuman')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola Pengumuman</h4>
            <p class="text-muted small mb-0">Pengumuman yang diterbitkan tampil di landing page (maksimal 5 terbaru).</p>
        </div>
        <a href="{{ route('admin.pengumuman.create') }}" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> Buat Pengumuman
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success" role="alert">{{ session('success') }}</div>
    @endif

    @if ($pengumuman->isEmpty())
        <div class="alert alert-info" role="alert">Belum ada pengumuman.</div>
    @else
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Tanggal Terbit</th>
                    <th>Dibuat Oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pengumuman as $item)
                    <tr>
                        <td>{{ $item->judul }}</td>
                        <td>{{ $item->kategori === 'eksternal' ? 'Eksternal' : 'Internal' }}</td>
                        <td>{{ $item->published_at ? $item->published_at->format('d M Y H:i') : '-' }}</td>
                        <td>{{ $item->creator?->name ?? '-' }}</td>
                        <td>
                            @if ($item->file)
                                <a href="{{ asset('storage/' . $item->file) }}" class="btn btn-sm btn-outline-primary" target="_blank">Unduh File</a>
                            @endif
                            <a href="{{ route('admin.pengumuman.edit', $item) }}" class="btn btn-sm btn-warning">Ubah</a>
                            <form action="{{ route('admin.pengumuman.destroy', $item) }}" method="POST" style="display:inline;" data-ehibah-confirm="Hapus pengumuman ini?" data-ehibah-confirm-title="Hapus Pengumuman" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
