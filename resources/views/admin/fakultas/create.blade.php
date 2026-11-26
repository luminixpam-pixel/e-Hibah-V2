@extends('layouts.app')

@section('title','Tambah Fakultas')

@section('content')
<h4>Tambah Fakultas</h4>

<form action="{{ route('admin.fakultas.store') }}" method="POST">
@csrf
<div class="mb-3">
    <label>Nama Fakultas</label>
    <input type="text" name="nama_fakultas" class="form-control @error('nama_fakultas') is-invalid @enderror" value="{{ old('nama_fakultas') }}" placeholder="mis. Fakultas Teknologi Informasi" required>
    @error('nama_fakultas')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button class="btn btn-success">Simpan</button>
<a href="{{ route('admin.fakultas.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
