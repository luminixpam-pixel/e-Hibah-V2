@extends('layouts.app')

@section('content')

<style>
    .page-title {
        font-weight: 700;
        font-size: 22px;
        color: #2d2d2d;
    }

    .table thead th {
        background: #f8f9fa !important;
        font-weight: 600;
    }

    .btn-action {
        padding: 5px 8px;
        border-radius: 6px;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: 100%;
    }

    /* TABEL (BUG #1 uji ke-4, 28 Nov; UB5 uji ke-5): TANPA
       table-layout: fixed — kolom menyesuaikan isi (lebar minimum
       di <th>). Perbaikan UB5 bersifat struktural, bukan anggaran
       lebar: semua kolom dipangkas ke lebar isi sebenarnya
       (Tanggal, Pengusul, Reviewer, Status boleh melipat/menyusut;
       tombol Aksi tetap ditumpuk vertikal tapi dengan font/padding
       lebih kecil), tabel memakai .table-padat, dan pembungkus
       .container diganti .container-fluid px-0 supaya padding
       12px kiri-kanan tidak memakan lebar tabel. */
    .table-responsive .table {
        width: 100%;
    }
    .aksi-disetujui {
        display: flex;
        flex-direction: column;
        gap: 5px;
        align-items: stretch;
    }

    .page-subtitle {
        font-size: 15px;
        color: #6c757d;
    }
</style>

@php
    /**
     * ✅ FIX: Kalau halaman "Disetujui" kosong padahal di "Review Selesai" ada yang dipilih Disetujui,
     * biasanya karena yang diubah itu kolom "status pendanaan", bukan kolom "status".
     *
     * Jadi: kalau $proposals kosong, kita fallback query ulang pakai patokan:
     * - status = 'Disetujui'
     * - ATAU status_pendanaan = 'Disetujui' (dan beberapa kemungkinan nama kolom lain)
     */

    $authUser = auth()->user();
    $currentRole = $role ?? ($authUser->role ?? null);

    // pastiin $proposals ada
    $proposals = $proposals ?? collect();

    $isEmpty = false;
    if (is_object($proposals) && method_exists($proposals, 'count')) {
        $isEmpty = ($proposals->count() == 0);
    } else {
        $isEmpty = (count($proposals) == 0);
    }

    if ($isEmpty) {
        $pendanaanCols = [
            'status_pendanaan',
            'status_pendanaan_admin',
            'status_pendanaan_final',
            'keputusan_pendanaan',
            'status_keputusan',
        ];

        $q = \App\Models\Proposal::query()
            ->with(['user', 'reviewers'])
            ->where(function ($qq) use ($pendanaanCols) {
                // patokan lama (kalau memang ada)
                $qq->where('status', 'Disetujui');

                // patokan baru: status pendanaan = Disetujui (cek hanya kalau kolom ada)
                foreach ($pendanaanCols as $col) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('proposals', $col)) {
                        $qq->orWhere($col, 'Disetujui');
                    }
                }
            });

        // filter sesuai role (biar konsisten)
        if ($currentRole === 'reviewer') {
            $q->whereHas('reviewers', function ($r) use ($authUser) {
                $r->where('users.id', $authUser->id);
            });
        } elseif ($currentRole !== 'admin') {
            $q->where(function ($u) use ($authUser) {
                $u->where('user_id', $authUser->id);
                if (!empty($authUser->username)) {
                    $u->orWhere('user_id', $authUser->username);
                }
            });
        }

        $proposals = $q->latest()->get();
    }
@endphp

<div class="container-fluid px-0 mt-4">

    {{-- TITLE --}}
    <h4 class="page-title mb-1">Daftar Proposal Yang Disetujui — Universitas YARSI</h4>
    <p class="page-subtitle mb-4">Berikut daftar proposal yang telah Disetujui (setelah review selesai).</p>

    {{-- 🔍 SEARCH --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="input-group" style="max-width: 360px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="table-search" class="form-control"
                   placeholder="Cari Judul Proposal atau Nama Dosen">
        </div>
    </div>

    {{-- TABLE --}}
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle shadow-sm table-compact table-padat">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th style="min-width: 96px;">No. Pengajuan</th>
                    <th style="min-width: 84px;">Tanggal</th>
                    <th style="min-width: 84px;">Pengusul</th>
                    <th>Judul Proposal</th>
                    <th style="min-width: 80px;">Reviewer</th>
                    <th style="min-width: 76px;">Status</th>
                    <th style="min-width: 92px;">Aksi</th>
                </tr>
            </thead>

            <tbody>
                @forelse($proposals as $index => $proposal)
                    @php
                        $pengusul = $proposal->user->name ?? '-';
                        $judul    = $proposal->judul ?? '-';
                        $tanggal  = $proposal->created_at?->format('d M Y') ?? '-';
                        $reviewer = optional($proposal->reviewers ?? collect())->pluck('name')->implode(', ') ?: '-';

                        // ✅ tampilkan status yang benar:
                        // kalau ada status_pendanaan (atau kolom variasi), pakai itu; kalau tidak, fallback ke status
                        $status = $proposal->status ?? '-';
                        $pendanaanCols = [
                            'status_pendanaan',
                            'status_pendanaan_admin',
                            'status_pendanaan_final',
                            'keputusan_pendanaan',
                            'status_keputusan',
                        ];
                        foreach ($pendanaanCols as $col) {
                            if (\Illuminate\Support\Facades\Schema::hasColumn('proposals', $col) && !empty($proposal->{$col})) {
                                $status = $proposal->{$col};
                                break;
                            }
                        }
                    @endphp

                    <tr>
                        <td>{{ $index + 1 }}</td>

                        {{-- No. Pengajuan (ditambahkan revisi uji ke-3:
                             sebelumnya kolom ini tidak ada sehingga nomor
                             seperti EH-2026-0001 tidak terlihat di sini). --}}
                        <td class="text-center"><span class="fw-semibold">{{ $proposal->no_pengajuan ?? '—' }}</span></td>

                        {{-- Tanggal --}}
                        <td class="text-nowrap">{{ $tanggal }}</td>

                        {{-- Pengusul --}}
                        <td>{{ $pengusul }}</td>

                        {{-- Judul Proposal --}}
                        <td>{{ $judul }}</td>

                        {{-- Reviewer --}}
                        <td>{{ $reviewer }}</td>

                        {{-- Status --}}
                        <td>
                            <span class="badge bg-success">{{ $status }}</span>
                        </td>

                        {{-- Aksi --}}
                        <td>
                            <div class="aksi-disetujui">
                            @if($proposal->file_path)
                                <a href="{{ route('proposal.download', $proposal->id) }}"
                                   class="btn btn-primary btn-action">
                                    <i class="bi bi-download"></i> Download
                                </a>
                                <button type="button" class="btn btn-primary btn-action"
                                        data-preview-url="{{ route('proposal.preview', $proposal->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($proposal->file_path ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}"
                                        data-preview-title="{{ $judul }}"
                                        data-download-url="{{ route('proposal.download', $proposal->id) }}">
                                    <i class="bi bi-eye"></i> Preview
                                </button>
                            @else
                                <button class="btn btn-secondary btn-action" disabled>
                                    <i class="bi bi-x-circle"></i> Tidak ada file
                                </button>
                            @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-3">
                            Belum ada proposal yang disetujui.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🔁 PREV / NEXT --}}
    <div class="d-flex justify-content-between mt-3">
        <a href="{{ route('monitoring.reviewSelesai') }}"
           class="btn btn-outline-success btn-sm">
            &laquo; Review Selesai
        </a>

        <a href="{{ route('monitoring.proposalDitolak') }}"
           class="btn btn-outline-success btn-sm">
            Proposal Ditolak &raquo;
        </a>
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
