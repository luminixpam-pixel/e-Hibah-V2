@extends('layouts.app')

@php
    $role = Auth::user()->role ?? null;
@endphp

{{-- CSS Tambahan untuk memperbaiki masalah layar redup & tampilan PDF.
     Catatan: JANGAN taruh aturan z-index modal/backdrop di sini —
     satu-satunya sumber kebenaran adalah layouts/app.blade.php
     (backdrop 3200, modal 3210, di atas sidebar 3100). Aturan lama
     yang bertabrakan (.modal 1060 lalu 1050, .modal-backdrop 1050
     lalu 1040) sudah dihapus; semuanya masih di bawah sidebar
     sehingga modal tampak terpotong. Blok <style> bersarang yang
     tidak valid sebelumnya juga sudah digabung menjadi satu blok. --}}
<style>
    .pdf-container {
        background-color: #525659;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 600px;
        border-radius: 0 0 12px 0;
    }
    /* Efek Hover pada Tabel */
    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
        transition: 0.3s;
        transform: scale(1.002);
    }

    /* Mempercantik shadow box tabel */
    .table-responsive {
        border: none !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    /* TABEL (revisi uji ke-3): TANPA table-layout: fixed — kolom
       menyesuaikan isi (lebar minimum di <th>), bukan dipatok persen
       dari tabel yang ternyata lebih sempit dari perkiraan. Lebar
       konten efektif ~990px (viewport 1341 - sidebar 220 - padding).
       Kolom Aksi min. 124px menampung 3 tombol ikon 32px + gap per
       baris, jadi 6 tombol terburuk (Detail, Download, Preview,
       Edit, Kirim, Hapus) tersusun maks. 2 baris (3+3); kolom Status
       min. 170px cukup untuk badge "SEDANG DIREVIEW" satu baris
       tanpa meluber ke kolom Aksi. */
    .table-responsive table {
        width: 100%;
    }
    .table-responsive table .d-flex.gap-2 {
        flex-wrap: wrap;
    }

    /* Styling tombol soft-UI */
    .btn-sm {
        padding: 0.4rem 0.6rem;
        border-radius: 8px;
    }
</style>

@section('content')
<div class="container mt-4">
    {{-- Header & Filter Tahun --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        @if($role === 'admin')
            <h4 class="fw-bold text-dark">Daftar Proposal Masuk Tahun {{ $tahun }}</h4>
        @else
            <h4 class="fw-bold text-dark">Daftar Proposal Dikirim Tahun {{ $tahun }}</h4>
        @endif

        <form action="{{ route('proposal.index') }}" method="GET" class="d-flex gap-2">
            <select name="tahun" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()">
                @foreach(range(date('Y'), 2023) as $year)
                    <option value="{{ $year }}" {{ $tahun == $year ? 'selected' : '' }}>Tahun {{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- SEARCH --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="input-group shadow-sm" style="max-width: 350px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
            <input type="text" id="table-search" class="form-control border-start-0" placeholder="Cari data proposal...">
        </div>
    </div>

    <script>
document.getElementById('table-search').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('table tbody tr');

    rows.forEach(row => {
        // Mengambil teks dari seluruh baris tabel
        let text = row.textContent.toLowerCase();

        // Jika teks pencarian ditemukan dalam baris tersebut, tampilkan barisnya
        if (text.includes(filter)) {
            row.style.display = '';
        } else {
            // Sembunyikan baris jika tidak cocok, kecuali baris "Belum ada data"
            if (!row.classList.contains('empty-row')) {
                row.style.display = 'none';
            }
        }
    });
});
</script>

    {{-- TABEL --}}
    <div class="table-responsive shadow-sm" style="border-radius: 12px;">
        <table class="table table-bordered table-hover align-middle bg-white mb-0">
            <thead class="table-light text-center">
                <tr>
                    <th style="width: 36px;">No</th>
                    <th style="min-width: 120px;">No. Pengajuan</th>
                    <th>Judul Proposal</th>
                    <th style="min-width: 110px;">Pengusul</th>
                    <th style="min-width: 170px;">Status Alur</th>
                    <th style="min-width: 124px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($proposals as $index => $proposal)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">
                            @if ($proposal->no_pengajuan)
                                <span class="fw-semibold">{{ $proposal->no_pengajuan }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $proposal->judul }}</div>
                            @if ($proposal->skema)
                                <small class="text-muted d-block">{{ $proposal->skema->label() }}</small>
                            @endif
                        </td>
                        <td class="text-center">{{ $proposal->nama_ketua }}</td>

                       <td class="text-center">
                            @php
                                $statusMap = [
                                    'Draft'           => ['class' => 'bg-warning text-dark', 'icon' => 'bi-pencil-square'],
                                    'Dikirim'         => ['class' => 'bg-secondary', 'icon' => 'bi-send'],
                                    'Perlu Direview'  => ['class' => 'bg-warning text-dark', 'icon' => 'bi-hourglass-split'],
                                    'Sedang Direview' => ['class' => 'bg-info text-white', 'icon' => 'bi-search'],
                                    'Review Selesai'  => ['class' => 'bg-primary', 'icon' => 'bi-check-all'],
                                    'Direvisi'        => ['class' => 'bg-danger', 'icon' => 'bi-exclamation-triangle'],
                                ];
                                $currentStatus = $statusMap[$proposal->status] ?? ['class' => 'bg-dark', 'icon' => 'bi-info-circle'];
                            @endphp

                            <span class="badge {{ $currentStatus['class'] }} px-2 py-2 rounded-pill shadow-sm text-nowrap" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <i class="{{ $currentStatus['icon'] }} me-1"></i>
                                {{ $role === 'admin' && $proposal->status === 'Dikirim' ? 'PROPOSAL BARU' : strtoupper($proposal->status) }}
                            </span>
                        </td>

                      <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                {{-- Detail Modal Trigger - Soft Green --}}
                                <button type="button" class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                        data-bs-toggle="modal" data-bs-target="#tinjauModal{{ $proposal->id }}"
                                        title="Lihat Detail"
                                        style="background-color: #e8f5e9; color: #2e7d32; width: 32px; height: 32px; border-radius: 10px;">
                                    <i class="bi bi-eye-fill fs-5"></i>
                                </button>

                                {{-- Download - Soft Blue --}}
                                @if ($proposal->file_path)
                                    <a href="{{ route('proposal.download', $proposal->id) }}"
                                    class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                    title="Download"
                                    style="background-color: #e3f2fd; color: #1565c0; width: 32px; height: 32px; border-radius: 10px;">
                                        <i class="bi bi-cloud-arrow-down-fill fs-5"></i>
                                    </a>

                                    {{-- Preview - Soft Blue --}}
                                    <button type="button"
                                    class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                    title="Preview"
                                    style="background-color: #e3f2fd; color: #1565c0; width: 32px; height: 32px; border-radius: 10px;"
                                    data-preview-url="{{ route('proposal.preview', $proposal->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($proposal->file_path ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}"
                                    data-preview-title="{{ $proposal->judul }}"
                                    data-download-url="{{ route('proposal.download', $proposal->id) }}">
                                        <i class="bi bi-eye fs-5"></i>
                                    </button>
                                @endif

                                {{-- FITUR EDIT --}}
                                {{-- Logika: Jika dia pemilik proposal, dia BOLEH aksi, apa pun role-nya (pengaju/reviewer) --}}
                                @if($proposal->user_id === Auth::user()->id)
                                    @php
                                        $isStatusEditable = in_array($proposal->status, ['Draft', 'Dikirim', 'Direvisi']);
                                    @endphp

                                    @if($isMasaHibah && $isStatusEditable)
                                        {{-- Tombol Aktif --}}
                                        <a href="{{ route('proposal.edit', $proposal->id) }}"
                                        class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                        title="Edit Proposal"
                                        style="background-color: #fff9c4; color: #fbc02d; width: 32px; height: 32px; border-radius: 10px;">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </a>
                                    @else
                                        {{-- Tombol Terkunci --}}
                                        <button type="button"
                                                onclick="ehibahAlert('Terkunci', 'Anda tidak dapat mengedit proposal pada status ini atau masa hibah telah berakhir.')"
                                                class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                                style="background-color: #f5f5f5; color: #bdbdbd; width: 32px; height: 32px; border-radius: 10px;">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </button>
                                    @endif

                                    {{-- KIRIM PENGAJUAN langsung dari daftar (hanya untuk
                                         Draft / Revisi Administrasi milik sendiri dan
                                         masih dalam masa hibah — jalurnya sama dengan
                                         Edit -> Kirim Pengajuan). Sengaja TANPA dialog
                                         confirm() bawaan browser (B1): dialog sinkron
                                         itu memblokir tab dan membuat tombol tampak
                                         membeku; hasil kirim dikonfirmasi lewat pesan
                                         flash sukses/error dari server, sama seperti
                                         tombol Kirim di form edit. --}}
                                    @if($isMasaHibah && in_array($proposal->status, ['Draft', 'Revisi Administrasi'], true))
                                        <form action="{{ route('proposal.kirim', $proposal->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-md border-0 shadow-sm d-flex align-items-center justify-content-center"
                                                    title="Kirim Pengajuan"
                                                    style="background-color: #e8f5e9; color: #2e7d32; width: 32px; height: 32px; border-radius: 10px;">
                                                <i class="bi bi-send-fill fs-5"></i>
                                            </button>
                                        </form>
                                    @endif

                                    {{-- FITUR HAPUS --}}
                                    @php
                                        $allowedDelete = ['Draft', 'Dikirim', 'Ditolak', 'Direvisi', 'Sedang Direview', 'Hasil Revisi'];
                                    @endphp
                                    @if(in_array($proposal->status, $allowedDelete))
                                        <form action="{{ route('proposal.destroy', $proposal->id) }}" method="POST" class="d-inline" data-ehibah-confirm="Yakin ingin menghapus proposal Anda sendiri?" data-ehibah-confirm-title="Hapus Proposal" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn border-0 shadow-sm d-flex align-items-center justify-content-center"
                                                    title="Hapus"
                                                    style="background-color: #ffebee; color: #c62828; width: 32px; height: 32px; border-radius: 10px;">
                                                <i class="bi bi-trash3-fill fs-5"></i>
                                            </button>
                                        </form>
                                    @endif
                                @endif
                                                        </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Tidak ada data proposal untuk tahun ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- --- MODAL SECTION (DI LUAR CONTAINER) --- --}}
@foreach ($proposals as $proposal)
<div class="modal fade" id="tinjauModal{{ $proposal->id }}" tabindex="-1" aria-labelledby="tinjauModalLabel{{ $proposal->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">

            {{-- Header: Nuansa Kuning Soft (Senada dengan Tombol Edit) --}}
            <div class="modal-header border-0 px-4 py-3" style="background-color: #fff9c4;">
                <h5 class="modal-title d-flex align-items-center" id="tinjauModalLabel{{ $proposal->id }}">
                    <div class="rounded-circle bg-white d-flex align-items-center justify-content-center shadow-sm me-2" style="width: 35px; height: 35px;">
                        <i class="bi bi-file-earmark-text text-warning"></i>
                    </div>
                    <span class="fw-bold" style="color: #827717; font-size: 1.1rem;">Detail & Pratinjau Proposal</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="container-fluid p-0">
                    <div class="row g-0">
                        {{-- Panel Kiri: Informasi --}}
                        <div class="col-lg-4 bg-white p-4 border-end">
                            <div class="mb-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 1px;">Judul</label>
                                <p class="fw-bold text-dark lh-sm" style="font-size: 1.05rem;">{{ $proposal->judul }}</p>
                            </div>

                            <div class="mb-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 1px;">Ketua Pengaju</label>
                                <div class="d-flex align-items-center p-2 rounded-3 bg-light">
                                    <i class="bi bi-person-circle fs-4 text-primary me-2"></i>
                                    <span class="fw-semibold text-secondary">{{ $proposal->nama_ketua }}</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-2" style="letter-spacing: 1px;">Anggota Tim</label>
                                <div class="p-3 border-0 rounded-4 shadow-sm bg-light small">
                                    @if(!empty($proposal->anggota) && is_array($proposal->anggota))
                                        <ul class="list-unstyled mb-0">
                                            @foreach($proposal->anggota as $nama)
                                                <li class="mb-2 d-flex align-items-start">
                                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1"></i>
                                                    <span class="text-secondary fw-medium">{{ $nama }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <div class="text-center py-2 text-muted fst-italic">
                                            <i class="bi bi-people me-1"></i> Tidak ada anggota
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-auto">
                                @if($proposal->file_path)
                                    <a href="{{ route('proposal.download', $proposal->id) }}" class="btn btn-primary border-0 py-2 shadow-sm" style="border-radius: 12px; background-color: #0d6efd;">
                                        <i class="bi bi-cloud-arrow-down-fill me-2"></i>Download File Asli
                                    </a>
                                    <button type="button" class="btn btn-outline-primary py-2 shadow-sm" style="border-radius: 12px;" data-preview-url="{{ route('proposal.preview', $proposal->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($proposal->file_path ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="{{ $proposal->judul }}" data-download-url="{{ route('proposal.download', $proposal->id) }}">
                                        <i class="bi bi-eye me-2"></i>Preview File
                                    </button>
                                @endif
                                <button type="button" class="btn btn-outline-secondary py-2" data-bs-dismiss="modal" style="border-radius: 12px;">Tutup</button>
                            </div>
                        </div>

                        {{-- Panel Kanan: Pratinjau PDF --}}
                        <div class="col-lg-8 p-0" style="background-color: #f1f3f4; min-height: 650px;">
                            @if($proposal->file_path)
                                @php $extension = pathinfo($proposal->file_path, PATHINFO_EXTENSION); @endphp

                                @if(strtolower($extension) === 'pdf')
                                    <iframe src="{{ route('proposal.preview', $proposal->id) }}#toolbar=0" width="100%" height="700px" style="border: none;"></iframe>
                                @else
                                    <div class="d-flex flex-column align-items-center justify-content-center h-100 p-5 text-center">
                                        <div class="bg-white p-4 rounded-circle shadow-sm mb-4">
                                            <i class="bi bi-file-earmark-word text-primary" style="font-size: 4rem;"></i>
                                        </div>
                                        <h5 class="fw-bold text-dark">Pratinjau Tidak Tersedia</h5>
                                        <p class="text-muted px-md-5">File Word tidak dapat ditampilkan langsung di browser. Silakan unduh untuk meninjau dokumen secara lengkap.</p>
                                        <a href="{{ route('proposal.download', $proposal->id) }}" class="btn btn-outline-primary rounded-pill px-4">
                                            <i class="bi bi-download me-2"></i>Unduh Sekarang
                                        </a>
                                    </div>
                                @endif
                            @else
                                <div class="d-flex flex-column align-items-center justify-content-center h-100 opacity-50">
                                    <i class="bi bi-file-earmark-x display-1"></i>
                                    <p class="mt-2 fw-bold">Berkas tidak ditemukan</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endforeach

{{-- Skrip pemindah modal ke <body> tidak lagi ditulis di sini: skrip
     global yang sama sudah ada di layouts/app.blade.php (B3, B10)
     dan berjalan untuk semua modal di semua halaman. --}}

@endsection
