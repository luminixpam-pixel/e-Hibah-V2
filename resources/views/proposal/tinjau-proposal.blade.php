@extends('layouts.app')

@section('content')
<div class="container mt-4">

    <h4 class="mb-3">Detail Proposal — {{ $proposal->judul }}</h4>

    <div class="card mb-3">
        <div class="card-body">
            <p><strong>Judul:</strong> {{ $proposal->judul }}</p>
            <p><strong>Nama Ketua:</strong> {{ $proposal->nama_ketua }}</p>
            <p><strong>Biaya:</strong> {{ $proposal->biaya ?? '-' }}</p>
            <p><strong>Status:</strong> {{ $proposal->status ?? '-' }}</p>

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
            <form action="{{ route('verifikasi.store', $proposal->id) }}" method="POST">
                @csrf
                <div class="mb-2">
                    <label class="form-label">Hasil pemeriksaan</label>
                    <select name="hasil" class="form-select" required>
                        <option value="">— Pilih hasil —</option>
                        <option value="Lolos">Lolos Administrasi</option>
                        <option value="Revisi">Kembalikan untuk Revisi Administrasi</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label">Catatan / alasan pengembalian <span class="text-muted">(wajib bila dikembalikan)</span></label>
                    <textarea name="catatan" class="form-control" rows="3" placeholder="Tuliskan kekurangan berkas atau alasan pengembalian..."></textarea>
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
