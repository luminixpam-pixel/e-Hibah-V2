@extends('layouts.app')

@section('title','Master Fakultas')

@section('content')
<h4>Master Fakultas</h4>
<p class="text-muted small">Fakultas dipakai sebagai pilihan pada form pengajuan proposal dan filter dasbor dekan.</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<a href="{{ route('admin.fakultas.create') }}" class="btn btn-success mb-3">Tambah Fakultas</a>

<table class="table table-bordered">
<thead>
<tr>
    <th>Nama Fakultas</th>
    <th>Jumlah Proposal</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
@forelse($fakultas as $item)
<tr>
    <td>{{ $item->nama_fakultas }}</td>
    <td>{{ $item->proposals_count }}</td>
    <td>
        <a href="{{ route('admin.fakultas.edit', $item) }}" class="btn btn-sm btn-warning">Ubah</a>
        <form action="{{ route('admin.fakultas.destroy', $item) }}" method="POST" style="display:inline;" data-ehibah-confirm="Hapus fakultas {{ $item->nama_fakultas }}? Proposal dan user yang merujuk fakultas ini akan kehilangan fakultasnya." data-ehibah-confirm-title="Hapus Fakultas" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
        </form>
    </td>
</tr>
@empty
<tr>
    <td colspan="3" class="text-center text-muted">Belum ada fakultas.</td>
</tr>
@endforelse
</tbody>
</table>
@endsection
