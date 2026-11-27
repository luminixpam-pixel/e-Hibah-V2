{{-- resources/views/proposal/plotting.blade.php --}}
{{-- Antrean plotting LPPM (Fase 3, story 16 + 19): proposal Lolos Administrasi
     beserta kandidat reviewer terurut skor kecocokan. --}}
@extends('layouts.app')

@section('title', 'Plotting Reviewer')

@section('content')
<div class="container mt-4">

    <h4 class="mb-1">Plotting Reviewer</h4>
    <p class="text-muted mb-4">Proposal yang telah lolos administrasi dan siap ditugaskan reviewer.</p>

    {{-- Pesan hasil penugasan (sukses/gagal) — sebelumnya tidak ada di halaman
         ini, sehingga penugasan yang ditolak server terlihat gagal diam-diam. --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
        </div>
    @endif

    @forelse ($proposals ?? [] as $p)
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $p->judul }}</h6>
                <p class="text-muted small mb-3">
                    {{ $p->user->name ?? '-' }}
                    @if ($p->no_pengajuan)
                        · {{ $p->no_pengajuan }}
                    @endif
                    @if ($p->skema)
                        · {{ $p->skema->label() }}
                    @endif
                </p>

                @if ($p->reviewers->isNotEmpty())
                    <p class="small mb-3">
                        <i class="bi bi-person-check me-1"></i>
                        Reviewer saat ini: {{ $p->reviewers->pluck('name')->implode(', ') }}
                    </p>
                @endif

                {{-- Penugasan butuh 2 reviewer berbeda; bila kandidat kurang dari 2,
                     penugasan pasti ditolak server — beri tahu LPPM sebabnya. --}}
                @if (count($kandidat[$p->id] ?? []) < 2)
                    <div class="alert alert-warning small mb-3" role="alert">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Kandidat reviewer hanya {{ count($kandidat[$p->id] ?? []) }} orang — penugasan butuh 2 reviewer berbeda.
                        Tambahkan akun reviewer lewat menu Manajemen Reviewer (admin) sebelum menugaskan proposal ini.
                    </div>
                @endif

                <form action="{{ route('proposal.assignReviewer', $p->id) }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small">Reviewer 1</label>
                            {{-- old() didahulukan agar isian tidak hilang bila penugasan
                                 ditolak server (mis. reviewer 1 = reviewer 2); fallback
                                 ke reviewer yang sudah ditugaskan. --}}
                            @php $oldR1 = old('reviewer_1', $p->reviewers->get(0)?->id); @endphp
                            <select name="reviewer_1" class="form-select form-select-sm @error('reviewer_1') is-invalid @enderror" required>
                                <option value="">— Pilih reviewer —</option>
                                @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                    <option value="{{ $k['user']->id }}" {{ (string) $oldR1 === (string) $k['user']->id ? 'selected' : '' }}>
                                        {{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('reviewer_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small">Reviewer 2</label>
                            @php $oldR2 = old('reviewer_2', $p->reviewers->get(1)?->id); @endphp
                            <select name="reviewer_2" class="form-select form-select-sm @error('reviewer_2') is-invalid @enderror" required>
                                <option value="">— Pilih reviewer —</option>
                                @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                    <option value="{{ $k['user']->id }}" {{ (string) $oldR2 === (string) $k['user']->id ? 'selected' : '' }}>
                                        {{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('reviewer_2')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        {{-- L3: kolom tenggat dilebarkan (2 → 3) dan label tidak
                             melipat, supaya input datetime-local tidak
                             terpotong nilainya. --}}
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small text-nowrap">Tenggat Penilaian</label>
                            <input type="datetime-local" name="review_deadline" class="form-control form-control-sm @error('review_deadline') is-invalid @enderror"
                                   value="{{ old('review_deadline', $p->review_deadline?->format('Y-m-d\\TH:i')) }}" required>
                            @error('review_deadline')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-success btn-sm w-100 text-nowrap">Tugaskan Reviewer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center py-5">
                <h6 class="fw-bold">Belum ada proposal untuk plotting</h6>
                <p class="text-muted small mb-0">Proposal yang lolos administrasi akan ditampilkan di halaman ini.</p>
            </div>
        </div>
    @endforelse

</div>
@endsection
