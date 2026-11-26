{{-- resources/views/admin/settings/index.blade.php --}}
{{-- Setelan Admin (SettingController) — hanya admin --}}
@extends('layouts.app')

@section('title', 'Setelan Admin')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Setelan Admin</h4>
    <p class="text-muted">Daftar key–value yang mengatur perilaku sistem. Key wajib snake_case (huruf kecil, angka, garis bawah) dan unik.</p>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Form tambah setelan --}}
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Tambah Setelan</h6>
            <form action="{{ route('admin.settings.store') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label fw-bold">Key *</label>
                    <input type="text" name="key" class="form-control" required value="{{ old('key') }}" placeholder="mis. rab_komponen_kustom">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Value</label>
                    <input type="text" name="value" class="form-control" value="{{ old('value') }}" placeholder="Nilai setelan (boleh kosong)">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Tambah</button>
                </div>
            </form>
            <p class="text-muted small mb-0 mt-3">
                Komponen RAB kustom: buat setelan dengan key <code>rab_komponen_kustom</code> dan value berupa JSON array string,
                contoh: <code>["Sewa Lab", "Uji Sampel"]</code>. Nama-nama itu lalu muncul sebagai pilihan di form RAB pengajuan proposal
                dan lolos validasi; bila key belum ada, daftar kustom dianggap kosong.
            </p>
        </div>
    </div>

    {{-- Daftar setelan --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Value</th>
                            <th>Diperbarui</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settings as $setting)
                            <tr>
                                <td>
                                    <input type="text" name="key" form="update-setting-{{ $setting->id }}" class="form-control form-control-sm" required value="{{ $setting->key }}">
                                </td>
                                <td>
                                    <input type="text" name="value" form="update-setting-{{ $setting->id }}" class="form-control form-control-sm" value="{{ $setting->value }}">
                                </td>
                                <td class="text-nowrap small text-muted">{{ $setting->updated_at?->format('d M Y H:i') }}</td>
                                <td class="text-nowrap">
                                    <form id="update-setting-{{ $setting->id }}" action="{{ route('admin.settings.update', $setting->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Simpan</button>
                                    </form>
                                    <form action="{{ route('admin.settings.destroy', $setting->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus setelan {{ $setting->key }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada setelan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
