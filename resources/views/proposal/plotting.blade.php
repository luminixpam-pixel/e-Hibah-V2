{{-- resources/views/proposal/plotting.blade.php --}}
{{-- Antrean plotting LPPM (Fase 3, story 16 + 19): proposal Lolos Administrasi
     beserta kandidat reviewer terurut skor kecocokan. --}}
@extends('layouts.app')

@section('title', 'Plotting Reviewer')

@section('content')
<div class="container mt-4">

    <h4 class="mb-1">Plotting Reviewer</h4>
    <p class="text-muted mb-4">Proposal yang telah lolos administrasi dan siap ditugaskan reviewer.</p>

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
                        · {{ $p->skema->kategori }}
                    @endif
                </p>

                @if ($p->reviewers->isNotEmpty())
                    <p class="small mb-3">
                        <i class="bi bi-person-check me-1"></i>
                        Reviewer saat ini: {{ $p->reviewers->pluck('name')->implode(', ') }}
                    </p>
                @endif

                <form action="{{ route('proposal.assignReviewer', $p->id) }}" method="POST">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Reviewer 1</label>
                            <select name="reviewer_1" class="form-select form-select-sm" required>
                                <option value="">— Pilih reviewer —</option>
                                @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                    <option value="{{ $k['user']->id }}" {{ $p->reviewers->get(0)?->id === $k['user']->id ? 'selected' : '' }}>
                                        {{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Reviewer 2</label>
                            <select name="reviewer_2" class="form-select form-select-sm" required>
                                <option value="">— Pilih reviewer —</option>
                                @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                    <option value="{{ $k['user']->id }}" {{ $p->reviewers->get(1)?->id === $k['user']->id ? 'selected' : '' }}>
                                        {{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small">Tenggat Penilaian</label>
                            <input type="datetime-local" name="review_deadline" class="form-control form-control-sm"
                                   value="{{ $p->review_deadline?->format('Y-m-d\\TH:i') }}" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success btn-sm w-100">Tugaskan Reviewer</button>
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
