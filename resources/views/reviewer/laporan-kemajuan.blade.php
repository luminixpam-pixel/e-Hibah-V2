@extends('layouts.app')

@section('title', Auth::user()->role === 'admin' ? 'Laporan Kemajuan' : 'Unggah Laporan Kemajuan Final')

@section('content')
<style>
    body { background-color: #f0fdf4; } /* Tema Hijau YARSI */

    .main-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: none;
    }

    .form-section {
        background: #fcfdfd;
        border-radius: 15px;
        border: 1px solid #eef2f3;
        padding: 25px;
    }

    /* Card khusus Admin untuk Upload Template */
    .admin-setting-card {
        background-color: #fff9db;
        border: 1px solid #ffe066;
        border-radius: 15px;
    }

    .table thead th {
        background-color: #28a745;
        color: white;
        border: none;
        font-size: 13px;
        font-weight: 600;
        padding: 15px;
        text-align: center;
    }

    /* TABEL (revisi uji ke-3): TANPA table-layout: fixed — kolom
       menyesuaikan isi (lebar minimum di <th>). Lebar konten
       efektif ~930px (viewport 1341 - sidebar 220 - padding
       konten & .main-card). Tombol UNDUH & PREVIEW di kolom Berkas
       ditumpuk vertikal memenuhi lebar kolom supaya tidak meluber
       menumpuk tombol Aksi di sebelahnya. */
    .table-responsive .table {
        width: 100%;
    }
    .table td .berkas-laporan {
        display: flex;
        flex-direction: column;
        gap: 6px;
        align-items: stretch;
    }
    .table td .berkas-laporan .btn-laporan {
        width: 100%;
        justify-content: center;
        white-space: normal;
        text-align: center;
        line-height: 1.35;
    }

    .table tbody td {
        padding: 15px;
        vertical-align: middle;
        font-size: 13px;
    }

    .btn-laporan {
        background-color: #e3f2fd;
        color: #1565c0;
        font-weight: 700;
        font-size: 11px;
        border-radius: 8px;
        padding: 8px 10px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        border: none;
        text-decoration: none;
    }

    .btn-laporan:hover {
        background-color: #bbdefb;
        color: #0d47a1;
        transform: translateY(-2px);
    }

    .badge-empty {
        background-color: #f8f9fa;
        color: #adb5bd;
        border: 1px dashed #dee2e6;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
    }

    .action-btn {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        transition: 0.3s;
    }
</style>

<div class="container py-4">
    <div class="main-card">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-extrabold text-dark mb-1">
                    {{ Auth::user()->role === 'admin' ? 'Monitoring Laporan Kemajuan' : 'Unggah Laporan Kemajuan' }}
                </h4>
                <p class="text-muted small">Kelola dan tinjau progres pelaksanaan penelitian/pengabdian.</p>
            </div>
            <div class="text-end">
                <div class="text-success small fw-bold">
                    <i class="bi bi-calendar-check me-1"></i> {{ now()->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>

        {{-- ALERT MESSAGES --}}
        @if (session('success'))
            <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            </div>
        @endif

        {{-- ✅ BAGIAN ADMIN: UPLOAD TEMPLATE --}}
        @if(Auth::user()->role === 'admin')
        <div class="admin-setting-card p-4 mb-5 shadow-sm">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-gear-fill me-2"></i>Pengaturan Template Laporan (Admin)</h6>
            <form action="{{ route('admin.template.upload') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="jenis" value="laporan_kemajuan">
                <div class="row align-items-end">
                    <div class="col-md-6 mb-2">
                        <label class="small fw-bold text-muted">File Template Baru (.docx / .pdf)</label>
                        <input type="file" name="file_template" class="form-control form-control-sm" required style="border-radius: 8px;">
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-sm btn-warning w-100 fw-bold shadow-sm" style="border-radius: 8px; height: 38px;">
                            <i class="bi bi-cloud-upload me-1"></i> Perbarui Template
                        </button>
                    </div>
                </div>
            </form>
        </div>
        @endif

        {{-- FORM UNGGAH (KHUSUS PENGAJU/USER) --}}
        @if(Auth::user()->role !== 'admin')
        <div class="form-section mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold text-dark mb-0">Submit Laporan kemajuan</h5>

                {{-- ✅ LINK DOWNLOAD DINAMIS DARI DATABASE --}}
                @php
                    $template = \App\Models\Template::where('jenis', 'laporan_kemajuan')->first();
                @endphp

               @if($template && $template->file_path)
                {{-- Gunakan asset() jika file ada di folder public, atau Storage::url() jika di storage/app/public --}}
                <a href="{{ route('template.download', $template->id) }}"
                class="btn btn-sm btn-outline-success border-2 fw-bold px-3"
                style="border-radius: 8px;"
                download="{{ $template->nama_template }}">
                    <i class="bi bi-file-earmark-arrow-down-fill me-1"></i>
                    Unduh Template laporan kemajuan
                    </a>
                <button type="button"
                class="btn btn-sm btn-outline-success border-2 fw-bold px-3"
                style="border-radius: 8px;"
                data-preview-url="{{ route('template.preview', $template->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($template->file_path ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}"
                data-preview-title="{{ $template->nama_template }}"
                data-download-url="{{ route('template.download', $template->id) }}">
                    <i class="bi bi-eye me-1"></i>
                    Preview Template
                    </button>
            @else
                <span class="text-muted small fst-italic">
                    <i class="bi bi-info-circle me-1"></i> Template belum tersedia.
                </span>
            @endif
            </div>

            <form action="{{ route('laporan.kemajuan.store') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                @csrf
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold small text-muted">Pilih Judul Proposal</label>
                    <select name="proposal_id" class="form-select @error('proposal_id') is-invalid @enderror" required style="border-radius: 10px; padding: 10px;">
                        <option value="">-- Pilih Proposal --</option>

                        {{-- Hanya proposal yang sudah Disetujui pendanaannya
                             (status_pendanaan, dari $myProposals); sebelumnya
                             memakai $proposals sehingga Draft dan Lolos
                             Administrasi ikut tercantum. --}}
                        @foreach (($myProposals ?? collect()) as $proposal)
                            <option value="{{ $proposal->id }}" {{ old('proposal_id') == $proposal->id ? 'selected' : '' }}>
                                {{ $proposal->judul }}
                            </option>
                        @endforeach
                    </select>
                    @if (($myProposals ?? collect())->isEmpty())
                        <small class="text-muted">Belum ada proposal yang disetujui pendanaannya.</small>
                    @endif

                    @error('proposal_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                     <label class="form-label fw-bold small text-muted">Berkas Laporan (PDF/DOCX)</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".pdf,.doc,.docx" required style="border-radius: 10px; padding: 10px;">
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label fw-bold small text-muted">Catatan Kemajuan (Opsional)</label>
                        <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Progres sudah 50%..." style="border-radius: 10px;">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <button type="submit" class="btn btn-success px-5 fw-bold shadow-sm" style="border-radius: 10px; height: 45px;" id="submitBtn">
                    <span class="spinner-border spinner-border-sm d-none" id="btnSpinner"></span>
                    <span id="btnText"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Unggah Laporan</span>
                </button>
            </form>
        </div>
        @endif

        {{-- TABEL RIWAYAT --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0">Data Laporan Kemajuan</h5>
            <div class="search-wrapper">
                <form action="{{ route('laporan.kemajuan.index') }}" method="GET">
                    <input type="text" name="search" class="form-control form-control-sm shadow-sm" placeholder="Cari judul..." value="{{ request('search') }}" style="border-radius: 8px; width: 250px;">
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 36px; border-top-left-radius: 15px;">No</th>
                        <th class="text-start" style="min-width: 180px;">Judul & Pengusul</th>
                        <th style="min-width: 100px;">Reviewer</th>
                        <th style="min-width: 110px;">Status Pendanaan</th>
                        <th style="min-width: 170px;">Berkas Laporan</th>
                        <th style="min-width: 120px;">Status Verifikasi</th>
                        <th style="min-width: 100px; border-top-right-radius: 15px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $index => $proposal)
                        <tr>
                            <td class="text-center fw-bold text-muted">{{ $index + 1 }}</td>
                            <td>
                                <div class="fw-bold text-dark mb-1">{{ Str::limit($proposal->judul, 70) }}</div>
                                <div class="text-primary small">
                                    <i class="bi bi-person-circle me-1"></i> {{ $proposal->user->name ?? '-' }}
                                </div>
                            </td>
                            <td class="text-center small">
                                {{-- Relasi di model adalah reviewers() (many-to-many);
                                     $proposal->reviewer (tunggal) tidak ada sehingga
                                     kolom ini selalu "Belum Ditentukan" (bug #10),
                                     padahal reviewer sudah di-plotting. --}}
                                {{ $proposal->reviewers->isNotEmpty() ? $proposal->reviewers->pluck('name')->join(', ') : 'Belum Ditentukan' }}
                            </td>
                            <td class="text-center small">
                                {{-- Status keputusan pendanaan (bukan status alur):
                                     supaya terlihat di tabel proposal mana yang
                                     sudah Disetujui pendanaannya — dan kenapa form
                                     unggah di atas kosong bila belum ada (bug #10).
                                     Proposal yang baru lolos review masih 'Proses'
                                     sampai ada keputusan pendanaan. --}}
                                @php($sp = $proposal->status_pendanaan ?? 'Proses')
                                <span class="badge {{ $sp === 'Disetujui' ? 'bg-success' : ($sp === 'Ditolak' ? 'bg-danger' : ($sp === 'Direvisi' ? 'bg-warning text-dark' : 'bg-light text-dark border')) }}">{{ $sp }}</span>
                            </td>
                            <td class="text-center">
                                @if($proposal->file_laporan)
                                    <div class="berkas-laporan">
                                        <a href="{{ route('laporan.kemajuan.download', $proposal->id) }}" class="btn-laporan shadow-sm">
                                            <i class="bi bi-file-earmark-check-fill fs-6"></i>
                                            <span>UNDUH LAPORAN</span>
                                        </a>
                                        <button type="button" class="btn-laporan shadow-sm" data-preview-url="{{ route('laporan.kemajuan.preview', $proposal->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($proposal->file_laporan ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}" data-preview-title="Laporan Kemajuan - {{ $proposal->judul }}" data-download-url="{{ route('laporan.kemajuan.download', $proposal->id) }}">
                                            <i class="bi bi-eye fs-6"></i>
                                            <span>PREVIEW LAPORAN</span>
                                        </button>
                                    </div>
                                    <div class="mt-1 text-muted" style="font-size: 10px;">
                                        Diperbarui: {{ \Carbon\Carbon::parse($proposal->updated_at)->format('d/m/Y') }}
                                    </div>
                                    {{-- Riwayat versi berkas lama (butir 3): tombol
                                         unduh per versi, tunduk pada aturan akses
                                         yang sama dengan berkas aktif. --}}
                                    @if(($versiPerProposal[$proposal->id] ?? collect())->isNotEmpty())
                                        <div class="mt-2 text-start" style="font-size: 11px;">
                                            <div class="fw-bold text-muted mb-1">Riwayat versi:</div>
                                            @foreach($versiPerProposal[$proposal->id] as $v)
                                                <div>
                                                    <a href="{{ route('laporan.versi.download', $v) }}">Versi {{ $v->nomor_versi }}</a>
                                                    <span class="text-muted">({{ \Carbon\Carbon::parse($v->created_at)->format('d/m/Y') }})</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @else
                                    <span class="badge-empty">
                                        <i class="bi bi-info-circle me-1"></i> Belum Ada Berkas
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                {{-- Status verifikasi laporan kemajuan (butir 4):
                                     Menunggu Verifikasi / Terverifikasi / Revisi. --}}
                                @if($proposal->file_laporan)
                                    @php($stKemajuan = $proposal->status_laporan_kemajuan ?? 'Menunggu Verifikasi')
                                    <span class="badge {{ $stKemajuan === 'Terverifikasi' ? 'bg-success' : ($stKemajuan === 'Revisi' ? 'bg-warning text-dark' : 'bg-light text-dark border') }}">{{ $stKemajuan }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <a href="{{ route('proposal.tinjau', $proposal->id) }}"
                                       class="action-btn btn btn-light-success"
                                       style="background: #e8f5e9; color: #2e7d32;" title="Tinjau Detail">
                                        <i class="bi bi-eye-fill"></i>
                                    </a>
                                    @if(Auth::user()->role === 'admin' && $proposal->file_laporan)
                                        <a href="{{ route('laporan.kemajuan.download', $proposal->id) }}"
                                           class="action-btn btn btn-light-primary"
                                           style="background: #e3f2fd; color: #1565c0;" title="Download Laporan">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <button type="button"
                                           class="action-btn btn btn-light-primary"
                                           style="background: #e3f2fd; color: #1565c0;" title="Preview Laporan"
                                           data-preview-url="{{ route('laporan.kemajuan.preview', $proposal->id) }}" data-previewable="{{ in_array(strtolower(pathinfo($proposal->file_laporan ?? '', PATHINFO_EXTENSION)), ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? '1' : '0' }}"
                                           data-preview-title="Laporan Kemajuan - {{ $proposal->judul }}"
                                           data-download-url="{{ route('laporan.kemajuan.download', $proposal->id) }}">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                    @endif
                                </div>
                                {{-- Verifikasi oleh LPPM (butir 4, pola Monev):
                                     hanya bila status Menunggu Verifikasi. --}}
                                @if(Auth::user()->role === 'lppm' && $proposal->file_laporan && ($proposal->status_laporan_kemajuan ?? null) === 'Menunggu Verifikasi')
                                    <div class="d-flex justify-content-center gap-2 mt-2">
                                        <form action="{{ route('laporan.kemajuan.verifikasi', $proposal->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="keputusan" value="terverifikasi">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold px-2" title="Tandai Terverifikasi">
                                                <i class="bi bi-check-lg me-1"></i>Verifikasi
                                            </button>
                                        </form>
                                        <form action="{{ route('laporan.kemajuan.verifikasi', $proposal->id) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="keputusan" value="revisi">
                                            <button type="submit" class="btn btn-sm btn-warning fw-bold px-2" title="Minta revisi">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Revisi
                                            </button>
                                        </form>
                                    </div>
                                @endif
                                {{-- Hapus laporan kemajuan (butir 4): ketua atau
                                     admin, hanya bila belum Terverifikasi. --}}
                                @if($proposal->file_laporan && ($proposal->user_id === Auth::user()->id || Auth::user()->role === 'admin') && ($proposal->status_laporan_kemajuan ?? null) !== 'Terverifikasi')
                                    <form action="{{ route('laporan.kemajuan.destroy', $proposal->id) }}" method="POST" class="mt-2" data-ehibah-confirm="Hapus laporan kemajuan ini beserta seluruh riwayat versinya?" data-ehibah-confirm-title="Hapus Laporan Kemajuan" data-ehibah-confirm-label="Hapus" data-ehibah-confirm-btn="btn-danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-2" title="Hapus laporan kemajuan">
                                            <i class="bi bi-trash me-1"></i>Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada data laporan kemajuan yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(Auth::user()->role !== 'admin')
        <div class="d-flex justify-content-between mt-5">
            <a href="{{ url('/status/review') }}" class="btn btn-sm btn-outline-secondary px-3" style="border-radius: 8px;">
                <i class="bi bi-arrow-left me-1"></i> Tahap Review
            </a>
            <a href="{{ url('/status/selesai') }}" class="btn btn-sm btn-outline-success px-3" style="border-radius: 8px;">
                Proposal Selesai <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    const uploadForm = document.getElementById('uploadForm');
    if(uploadForm) {
        uploadForm.onsubmit = function() {
            document.getElementById('submitBtn').classList.add('disabled');
            document.getElementById('btnSpinner').classList.remove('d-none');
            document.getElementById('btnText').innerText = 'Sedang Mengunggah...';
        };
    }
</script>
@endpush
@endsection
