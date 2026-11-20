{{-- resources/views/admin/users/index.blade.php --}}
{{-- Kelola Pengguna (AdminController@indexUsers) --}}
{{-- Dibuat saat perapian: method ini dulu memanggil view 'admin.users.index' yang tidak ada di disk (500). --}}
{{-- Form edit di sini memakai route admin.users.update (AdminController@updateUser) --}}
{{-- dengan field name, username, role, dan password — sesuai validasi di controller itu. --}}
@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Kelola Pengguna</h4>
    <p class="text-muted">Ubah nama, username, role, dan password pengguna. Role yang tersedia: Admin, Pengaju, Reviewer, Verifikator, LPPM, Dekan, Kepala LPPM.</p>

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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $index => $u)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $u->name }}</strong></td>
                                <td>{{ $u->username }}</td>
                                <td>{{ $u->email }}</td>
                                <td><span class="badge bg-primary">{{ $u->role_label ?? ucfirst($u->role) }}</span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal-{{ $u->id }}">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">Tidak ada pengguna.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal edit per pengguna --}}
@foreach ($users as $u)
<div class="modal fade" id="editUserModal-{{ $u->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Edit Pengguna — {{ $u->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.users.update', $u->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama *</label>
                        <input type="text" name="name" class="form-control" required value="{{ $u->name }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username *</label>
                        <input type="text" name="username" class="form-control" required value="{{ $u->username }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role *</label>
                        <select name="role" class="form-select" required>
                            @foreach (['admin' => 'Administrator TI', 'pengaju' => 'Pengaju Penelitian', 'reviewer' => 'Reviewer Proposal', 'verifikator' => 'Verifikator Administrasi', 'lppm' => 'Pengelola LPPM', 'dekan' => 'Pimpinan Fakultas', 'kepala_lppm' => 'Kepala LPPM'] as $val => $label)
                                <option value="{{ $val }}" {{ $u->role === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password Baru (kosongkan bila tidak diubah)</label>
                        <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Konfirmasi Password Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" minlength="8" autocomplete="new-password">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection
