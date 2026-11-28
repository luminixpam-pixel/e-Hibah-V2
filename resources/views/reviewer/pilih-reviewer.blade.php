@extends('layouts.app')

@php
    $role = Auth::user()->role ?? null;
@endphp

@section('content')

<style>
    .page-title {
        font-weight: 700;
        font-size: 22px;
        color: #2d2d2d;
    }
    .page-subtitle {
        font-size: 15px;
        color: #6c757d;
    }
    .table thead th {
        background: #f8f9fa !important;
        font-weight: 600;
    }
    .btn-action {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 13px;
    }
    /* TABEL (revisi uji ke-3): TANPA table-layout: fixed — kolom
       menyesuaikan isi (lebar minimum di <th>). Lebar konten
       efektif ~990px (viewport 1341 - sidebar 220 - padding). */
    .col-aksi {
        min-width: 150px;
    }
    .table-responsive .table {
        width: 100%;
    }
</style>

<div class="container mt-4">

    <h4 class="page-title mb-1">Manajemen Reviewer — Universitas YARSI</h4>
    <p class="page-subtitle mb-4">
        Admin dapat menentukan user menjadi reviewer. Gunakan kolom pencarian untuk mempermudah.
    </p>

    {{-- 🔍 SEARCH --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="table-search" class="form-control" placeholder="Cari nama / email / NIDN / NIP">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle shadow-sm">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    <th style="min-width: 130px;">Nama</th>
                    <th style="min-width: 160px;">Email</th>
                    <th style="min-width: 100px;">NIDN / NIP</th>
                    <th style="min-width: 110px;">Fakultas</th>
                    <th style="min-width: 120px;">Program Studi</th>
                    <th style="min-width: 80px;">Role</th>
                    <th class="col-aksi">Aksi</th>
                </tr>
            </thead>

            <tbody>
            @forelse($users as $index => $u)
                @php
                    $roleUser = $u->role ?? '-';
                    $roleLabel = $roleUser === 'reviewer' ? 'Reviewer' : ($roleUser === 'pengaju' ? 'Pengaju' : ucfirst($roleUser));
                @endphp

                <tr>
                    <td>{{ ($users->firstItem() ?? 0) + $index }}</td>
                    <td>{{ $u->name ?? '-' }}</td>
                    <td class="text-break">{{ $u->email ?? '-' }}</td>
                    <td>{{ $u->nidn ?? '-' }}</td>
                    <td>{{ $u->fakultas ?? '-' }}</td>
                    <td>{{ $u->program_studi ?? '-' }}</td>
                    <td>
                        @if($roleUser === 'reviewer')
                            <span class="badge bg-success">{{ $roleLabel }}</span>
                        @elseif($roleUser === 'pengaju')
                            <span class="badge bg-primary">{{ $roleLabel }}</span>
                        @else
                            <span class="badge bg-secondary">{{ $roleLabel }}</span>
                        @endif
                    </td>

                    <td class="col-aksi">
                        @if($roleUser === 'reviewer')
                            {{-- ✅ reviewer -> pengaju --}}
                            <form action="{{ route('admin.reviewer.remove', $u->id) }}"
                                  method="POST"
                                  data-ehibah-confirm="Yakin hentikan {{ $u->name }} sebagai reviewer? Role akan dikembalikan jadi Pengaju."
                                  data-ehibah-confirm-title="Hentikan Reviewer"
                                  data-ehibah-confirm-label="Hentikan"
                                  data-ehibah-confirm-btn="btn-danger"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm btn-action">
                                    Hapus Reviewer
                                </button>
                            </form>
                        @else
                            {{-- ✅ pengaju -> reviewer --}}
                            <form action="{{ route('admin.reviewer.set', $u->id) }}"
                                  method="POST"
                                  data-ehibah-confirm="Yakin jadikan {{ $u->name }} sebagai reviewer?"
                                  data-ehibah-confirm-title="Jadikan Reviewer"
                                  data-ehibah-confirm-label="Jadikan Reviewer"
                                  data-ehibah-confirm-btn="btn-success"
                                  class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm btn-action">
                                    Jadikan Reviewer
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">
                        Tidak ada data user.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination 20/halaman (uji ke-5 butir 10), pola yang sama
         dengan /admin/users. --}}
    <div class="d-flex justify-content-center mt-3">
        {{ $users->links() }}
    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const searchInput = document.getElementById('table-search');
    if (!searchInput) return;

    const rows = document.querySelectorAll('table tbody tr');

    searchInput.addEventListener('keyup', function () {
        const term = this.value.toLowerCase();

        rows.forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
});
</script>
@endpush
