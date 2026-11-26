@extends('layouts.app')

@section('title','Tambah Periode')

@section('content')
<h4>Tambah Periode</h4>

<form action="{{ route('admin.periode.store') }}" method="POST">
@csrf
<div class="mb-3">
    <label>Nama Periode</label>
    <input type="text" name="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama') }}" placeholder="mis. 2026 Ganjil" required>
    @error('nama')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Tanggal Mulai</label>
    <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
    @error('start_date')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Tanggal Selesai</label>
    <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
    @error('end_date')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button class="btn btn-success">Simpan</button>
<a href="{{ route('admin.periode.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
