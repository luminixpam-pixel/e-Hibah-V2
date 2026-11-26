@extends('layouts.app')

@section('title','Tambah Skema')

@section('content')
<h4>Tambah Skema</h4>

<form action="{{ route('admin.skema.store') }}" method="POST">
@csrf
<div class="mb-3">
    <label>Kategori</label>
    <select name="kategori" class="form-control @error('kategori') is-invalid @enderror" required>
        <option value="">-- Pilih kategori --</option>
        @foreach(['Pembinaan', 'Kompetisi', 'Khusus'] as $kategori)
            <option value="{{ $kategori }}" {{ old('kategori') === $kategori ? 'selected' : '' }}>{{ $kategori }}</option>
        @endforeach
    </select>
    @error('kategori')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Plafon (Rp)</label>
    <input type="number" name="plafon" class="form-control @error('plafon') is-invalid @enderror" value="{{ old('plafon', 0) }}" min="0" step="0.01" required>
    @error('plafon')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Kuota (maks keikutsertaan per dosen per kategori)</label>
    <input type="number" name="kuota" class="form-control @error('kuota') is-invalid @enderror" value="{{ old('kuota', 0) }}" min="0" required>
    @error('kuota')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Deskripsi</label>
    <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" rows="3">{{ old('deskripsi') }}</textarea>
    @error('deskripsi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Syarat</label>
    <textarea name="syarat" class="form-control @error('syarat') is-invalid @enderror" rows="3">{{ old('syarat') }}</textarea>
    @error('syarat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button class="btn btn-success">Simpan</button>
<a href="{{ route('admin.skema.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
