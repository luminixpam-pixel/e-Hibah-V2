{{-- resources/views/proposal/cek-kuota.blade.php --}}
{{-- Halaman cek kuota dosen (Route GET /cek-kuota-dosen) --}}
@extends('layouts.app')

@section('title', 'Cek Kuota Dosen')

@section('content')
<div class="container mt-4 mb-5">
    <h4 class="fw-bold mb-1">Cek Kuota Dosen</h4>
    <p class="text-muted">Kuota keikutsertaan dihitung <strong>per kategori skema</strong> (sebagai ketua atau anggota). Hanya proposal yang sudah dikirim yang dihitung; draft tidak menghabiskan kuota.</p>

    <div class="card">
        <div class="table-responsive">
            {{-- L1: layout tetap (fixed) dengan lebar kolom eksplisit agar
                 tabel muat dalam area konten; sel plafon/kuota/sisa tidak
                 melipat. --}}
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed; width: 100%;">
                <thead class="table-light">
                    <tr>
                        <th width="24%">Skema</th>
                        <th class="text-center text-nowrap" width="16%">Plafon</th>
                        <th class="text-center" width="11%">Sebagai Ketua</th>
                        <th class="text-center" width="12%">Sebagai Anggota</th>
                        <th class="text-center" width="9%">Total</th>
                        <th class="text-center text-nowrap" width="14%">Kuota</th>
                        <th class="text-center text-nowrap" width="14%">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($kuotaPerSkema as $row)
                        <tr>
                            <td class="fw-bold">{{ $row['skema']->label() }}</td>
                            {{-- Plafon/kuota 0 berarti belum ditetapkan LPPM (bukan 0 rupiah
                                 atau kuota habis) — jangan tampilkan "Rp 0" / "Penuh". --}}
                            <td class="text-center">{{ $row['skema']->plafon > 0 ? 'Rp '.number_format($row['skema']->plafon, 0, ',', '.') : 'Belum ditetapkan' }}</td>
                            <td class="text-center">{{ $row['sebagaiKetua'] }}</td>
                            <td class="text-center">{{ $row['sebagaiAnggota'] }}</td>
                            <td class="text-center fw-bold">{{ $row['total'] }}</td>
                            <td class="text-center">{{ $row['kuota'] > 0 ? $row['kuota'] : 'Belum ditetapkan' }}</td>
                            <td class="text-center">
                                @if ($row['kuota'] <= 0)
                                    <span class="badge bg-secondary">Belum ditetapkan</span>
                                @elseif ($row['sisa'] > 0)
                                    <span class="badge bg-success">{{ $row['sisa'] }} lagi</span>
                                @else
                                    <span class="badge bg-danger">Penuh</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data skema. Hubungi admin LPPM.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <p class="text-muted small mt-3 mb-4">
        Proposal yang dibuat sebelum sistem skema diberlakukan (tanpa skema) tidak masuk hitungan per skema di atas.
    </p>

    <a href="{{ route('proposal.create') }}" class="btn btn-success">Ajukan proposal baru</a>
    <a href="{{ route('proposal.index') }}" class="btn btn-outline-primary">Lihat Daftar Proposal</a>
</div>
@endsection
