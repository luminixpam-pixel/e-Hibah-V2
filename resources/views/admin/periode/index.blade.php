@extends('layouts.app')

@section('title','Master Periode')

@section('content')
<h4>Master Periode</h4>
<p class="text-muted small">Periode pengajuan hibah. Catatan: halaman kalender (Jadwal &amp; Kalender) masih memakai periode pertama di tabel ini — ubah tanggal lewat halaman kalender hanya mengubah periode pertama.</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<a href="{{ route('admin.periode.create') }}" class="btn btn-success mb-3">Tambah Periode</a>

<table class="table table-bordered">
<thead>
<tr>
    <th>Nama Periode</th>
    <th>Tanggal Mulai</th>
    <th>Tanggal Selesai</th>
    <th>Aksi</th>
</tr>
</thead>
<tbody>
@forelse($periodes as $periode)
<tr>
    <td>{{ $periode->nama }}</td>
    <td>{{ $periode->start_date ? $periode->start_date->format('d M Y') : '-' }}</td>
    <td>{{ $periode->end_date ? $periode->end_date->format('d M Y') : '-' }}</td>
    <td>
        <a href="{{ route('admin.periode.edit', $periode) }}" class="btn btn-sm btn-warning">Ubah</a>
        <form action="{{ route('admin.periode.destroy', $periode) }}" method="POST" style="display:inline;" data-ehibah-confirm="Hapus periode {{ $periode->nama }}? Proposal yang memakai periode ini akan kehilangan periodenya." data-ehibah-confirm-title="Hapus Periode" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
        </form>
    </td>
</tr>
@empty
<tr>
    <td colspan="4" class="text-center text-muted">Belum ada periode.</td>
</tr>
@endforelse
</tbody>
</table>
@endsection
