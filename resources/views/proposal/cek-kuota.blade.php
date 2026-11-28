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
            {{-- BUG #3 uji ke-4 (28 Nov): layout tetap (fixed) + lebar
                 persen DIHAPUS. Akar masalah: di `table-layout: fixed`
                 kolom dipatok persen dari tabel, tapi isi sel (angka
                 "Rp 15.000.000", badge "Belum ditetapkan") TIDAK
                 mengecil mengikuti kolomnya — begitu lebar efektif
                 menyusut, header "Sebagai Ketua" terpotong per suku
                 kata, angka terbelah di tengah ("Rp 15.000.00/0"),
                 dan badge Sisa terpotong di tepi kanan. Sekarang
                 layout otomatis + lebar minimum per kolom (total
                 anggaran ~735px di konten efektif ~990px): header
                 dipersingkat "Ketua"/"Anggota" (arti lengkapnya sudah
                 dijelaskan di paragraf pengantar di atas), sel Plafon
                 116px supaya "Rp 50.000.000" utuh satu baris, kolom
                 Sisa 132px supaya badge "Belum ditetapkan" muat satu
                 baris, Kuota 92px supaya "ditetapkan" tidak terbelah
                 per suku kata. Teks "Belum ditetapkan" dipertahankan
                 apa adanya (diuji test: kuota 0 bukan "Penuh"). --}}
            <table class="table table-hover align-middle mb-0 table-compact">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 120px;">Skema</th>
                        <th class="text-center text-nowrap" style="min-width: 116px;">Plafon</th>
                        <th class="text-center" style="min-width: 56px;">Ketua</th>
                        <th class="text-center" style="min-width: 64px;">Anggota</th>
                        <th class="text-center" style="min-width: 56px;">Total</th>
                        <th class="text-center" style="min-width: 92px;">Kuota</th>
                        <th class="text-center" style="min-width: 132px;">Sisa</th>
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
