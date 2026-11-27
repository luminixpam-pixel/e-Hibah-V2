@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h4 class="mb-3">Detail Proposal — {{ $proposal->judul }}</h4>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Judul:</strong> {{ $proposal->judul }}</p>
            <p><strong>Nama Ketua:</strong> {{ $proposal->nama_ketua }}</p>
            {{-- B4: label tegas "Total RAB" agar tidak tertukar dengan rekomendasi
                 biaya reviewer (angka berbeda, sumber berbeda). $proposal->biaya
                 adalah total komponen RAB yang diajukan pengusul. --}}
            <p><strong>Total RAB (Biaya Diajukan):</strong> {{ $proposal->biaya !== null ? 'Rp '.number_format($proposal->biaya, 0, ',', '.') : '-' }}</p>
            <p><strong>Status:</strong> {{ $proposal->status ?? '-' }}</p>

            {{-- Rekomendasi biaya dari reviewer ditampilkan terpisah dengan label
                 yang jelas — ini BUKAN total RAB proposal, melainkan usulan
                 biaya dari masing-masing reviewer. --}}
            @if($proposal->reviews->isNotEmpty())
                <p class="mb-1"><strong>Rekomendasi Biaya Reviewer:</strong></p>
                <ul class="mb-0">
                    @foreach($proposal->reviews as $review)
                        <li>
                            {{ $review->reviewer->name ?? 'Reviewer' }}:
                            @if($review->rekomendasi_biaya)
                                Rp {{ number_format($review->rekomendasi_biaya, 0, ',', '.') }}
                            @else
                                <span class="text-muted">tidak ada rekomendasi</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            <p><strong>Anggota:</strong>
                {{--
                   Karena di Model Proposal.php sudah ada protected $casts = ['anggota' => 'array'],
                   kita tidak perlu lagi menggunakan json_decode().
                   Variabel $proposal->anggota sudah otomatis menjadi Array PHP.
                --}}
                @if(!empty($proposal->anggota) && is_array($proposal->anggota))
                    {{ implode(', ', $proposal->anggota) }}
                @else
                    -
                @endif
            </p>

            <hr>

            <div class="d-flex gap-2">
                @if($proposal->file_path)
                    <a href="{{ route('proposal.download', $proposal->id) }}"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-download"></i> Download Proposal
                    </a>
                @else
                    <span class="text-muted">File belum diupload</span>
                @endif

                <a href="{{ route('proposal.index') }}" class="btn btn-outline-secondary btn-sm">
                    Kembali ke Daftar Proposal
                </a>
            </div>
        </div>
    </div>

    {{-- Formulir verifikasi administrasi (khusus verifikator, proposal masih Dikirim) --}}
    @if(auth()->user()->role === 'verifikator' && $proposal->status === 'Dikirim')
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Verifikasi Administrasi</h5>
            {{-- Pesan hasil verifikasi + pesan validasi: sebelumnya tidak dirender
                 di halaman ini, sehingga pengembalian tanpa alasan ditolak
                 diam-diam (select ter-reset tanpa penjelasan). --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            <form action="{{ route('verifikasi.store', $proposal->id) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Hasil pemeriksaan</label>
                    <select name="hasil" class="form-select @error('hasil') is-invalid @enderror" required>
                        <option value="">— Pilih hasil —</option>
                        <option value="Lolos" {{ old('hasil') === 'Lolos' ? 'selected' : '' }}>Lolos Administrasi</option>
                        <option value="Revisi" {{ old('hasil') === 'Revisi' ? 'selected' : '' }}>Kembalikan untuk Revisi Administrasi</option>
                    </select>
                    @error('hasil')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-2">
                    <label class="form-label">Catatan / alasan pengembalian <span class="text-muted">(wajib bila dikembalikan)</span></label>
                    <textarea name="catatan" class="form-control @error('catatan') is-invalid @enderror" rows="3" placeholder="Tuliskan kekurangan berkas atau alasan pengembalian...">{{ old('catatan') }}</textarea>
                    @error('catatan')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-success btn-sm">Simpan Hasil Verifikasi</button>
            </form>
        </div>
    </div>
    @endif

    {{-- Riwayat verifikasi administrasi --}}
    @if($proposal->verifications->isNotEmpty())
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Riwayat Verifikasi</h5>
            @foreach($proposal->verifications->sortByDesc('created_at') as $v)
                <p class="mb-1">
                    <strong>{{ $v->hasil === 'Lolos' ? 'Lolos Administrasi' : 'Revisi Administrasi' }}</strong>
                    <small class="text-muted">— {{ $v->verifikator->name ?? '-' }}, {{ $v->created_at?->format('d M Y H:i') }}</small>
                </p>
                @if($v->catatan)
                    <p class="mb-2">{{ $v->catatan }}</p>
                @endif
            @endforeach
        </div>
    </div>
    @endif

    {{-- Riwayat keputusan pendanaan (dari log aktivitas admin) --}}
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="mb-3">Riwayat Keputusan</h5>
            @forelse($riwayatKeputusan ?? [] as $log)
                <p class="mb-1">
                    {{ $log->activity }}
                    <small class="text-muted">— {{ $log->user->name ?? '-' }}, {{ $log->created_at?->format('d M Y H:i') }}</small>
                </p>
            @empty
                <p class="text-muted mb-0">Belum ada keputusan pendanaan untuk proposal ini.</p>
            @endforelse
        </div>
    </div>

</div>
@endsection
