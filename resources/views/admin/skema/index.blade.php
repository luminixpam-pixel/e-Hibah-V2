@extends('layouts.app')

@section('title','Master Skema')

@section('content')
<h4>Master Skema</h4>
<p class="text-muted small">Plafon dan kuota per kategori dipakai validasi pengajuan proposal (Fase 2). Nilai 0 berarti belum diisi dari panduan resmi LPPM.</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<a href="{{ route('admin.skema.create') }}" class="btn btn-success mb-3">Tambah Skema</a>

<table class="table table-bordered">
<thead>
<tr>
    <th>Kategori</th>
    <th>Plafon (Rp)</th>
    <th>Kuota</th>
    <th>Deskripsi</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
@forelse($skemas as $skema)
<tr>
    <td>{{ $skema->kategori }}</td>
    <td>{{ number_format($skema->plafon, 0, ',', '.') }}</td>
    <td>{{ $skema->kuota }}</td>
    <td>{{ $skema->deskripsi }}</td>
    <td>
        <a href="{{ route('admin.skema.edit', $skema) }}" class="btn btn-sm btn-warning">Ubah</a>
        <form action="{{ route('admin.skema.destroy', $skema) }}" method="POST" style="display:inline;" onsubmit="return confirm('Hapus skema {{ $skema->kategori }}? Proposal yang memakai skema ini akan kehilangan skemanya.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
        </form>
    </td>
</tr>
@empty
<tr>
    <td colspan="5" class="text-center text-muted">Belum ada skema. Tambahkan tiga kategori: Pembinaan, Kompetisi, Khusus.</td>
</tr>
@endforelse
</tbody>
</table>
@endsection
