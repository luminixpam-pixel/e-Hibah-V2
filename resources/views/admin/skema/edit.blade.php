@extends('layouts.app')

@section('title','Ubah Skema')

@section('content')
<h4>Ubah Skema — {{ $skema->label() }}</h4>

<form action="{{ route('admin.skema.update', $skema) }}" method="POST">
@csrf
@method('PUT')
<div class="mb-3">
    <label>Kategori</label>
    <select name="kategori" class="form-control @error('kategori') is-invalid @enderror" required>
        @foreach(['Pembinaan', 'Kompetisi', 'Khusus'] as $kategori)
            <option value="{{ $kategori }}" {{ old('kategori', $skema->kategori) === $kategori ? 'selected' : '' }}>{{ $kategori }}</option>
        @endforeach
    </select>
    @error('kategori')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Bidang (kosongkan untuk Khusus)</label>
    <select name="bidang" class="form-control @error('bidang') is-invalid @enderror">
        <option value="">— Tanpa bidang —</option>
        @foreach(['Kesehatan', 'Humaniora'] as $bidang)
            <option value="{{ $bidang }}" {{ old('bidang', $skema->bidang) === $bidang ? 'selected' : '' }}>{{ $bidang }}</option>
        @endforeach
    </select>
    @error('bidang')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Plafon (Rp)</label>
    <input type="number" name="plafon" class="form-control @error('plafon') is-invalid @enderror" value="{{ old('plafon', $skema->plafon) }}" min="0" step="0.01" required>
    @error('plafon')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Kuota (maks keikutsertaan per dosen per kategori)</label>
    <input type="number" name="kuota" class="form-control @error('kuota') is-invalid @enderror" value="{{ old('kuota', $skema->kuota) }}" min="0" required>
    @error('kuota')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Deskripsi</label>
    <textarea name="deskripsi" class="form-control @error('deskripsi') is-invalid @enderror" rows="3">{{ old('deskripsi', $skema->deskripsi) }}</textarea>
    @error('deskripsi')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label>Syarat</label>
    <textarea name="syarat" class="form-control @error('syarat') is-invalid @enderror" rows="3">{{ old('syarat', $skema->syarat) }}</textarea>
    @error('syarat')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<button class="btn btn-success">Simpan Perubahan</button>
<a href="{{ route('admin.skema.index') }}" class="btn btn-secondary">Batal</a>
</form>
@endsection
