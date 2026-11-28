{{-- resources/views/proposal/cek-kuota.blade.php --}}
{{-- Halaman cek kuota dosen (Route GET /cek-kuota-dosen) --}}
@extends('layouts.app')

@section('title', 'Cek Kuota Dosen')

@section('content')
<div class="container-fluid px-0 mt-4 mb-5">
    <h4 class="fw-bold mb-1">Cek Kuota Dosen</h4>
    <p class="text-muted">Kuota keikutsertaan dihitung <strong>per kategori skema</strong> (sebagai ketua atau anggota). Hanya proposal yang sudah dikirim yang dihitung; draft tidak menghabiskan kuota.</p>

    <div class="card">
        <div class="table-responsive">
            {{-- BUG #3 uji ke-4 (28 Nov; UB5 uji ke-5): layout tetap
                 (fixed) + lebar persen DIHAPUS. Akar masalah dulu: di
                 `table-layout: fixed` kolom dipatok persen dari tabel,
                 tapi isi sel (angka "Rp 15.000.000", badge "Belum
                 ditetapkan") TIDAK mengecil mengikuti kolomnya.
                 Perbaikan UB5 bersifat struktural, bukan anggaran
                 lebar: tiap kolom dipangkas ke lebar isi sebenarnya
                 (Plafon muat "Rp 50.000.000" satu baris, Sisa muat
                 badge "Belum ditetapkan" satu baris, Ketua/Anggota/
                 Total hanya butuh lebar 1-2 digit), tabel memakai
                 .table-padat, dan pembungkus .container diganti
                 .container-fluid px-0 supaya padding 12px kiri-kanan
                 tidak memakan lebar tabel. Teks "Belum ditetapkan"
                 dipertahankan apa adanya (diuji test: kuota 0 bukan
                 "Penuh"). --}}
            <table class="table table-hover align-middle mb-0 table-compact table-padat">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 100px;">Skema</th>
                        <th class="text-center text-nowrap" style="min-width: 104px;">Plafon</th>
                        <th class="text-center" style="min-width: 44px;">Ketua</th>
                        <th class="text-center" style="min-width: 48px;">Anggota</th>
                        <th class="text-center" style="min-width: 44px;">Total</th>
                        <th class="text-center" style="min-width: 80px;">Kuota</th>
                        <th class="text-center" style="min-width: 104px;">Sisa</th>
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
