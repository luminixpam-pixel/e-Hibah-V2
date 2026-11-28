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

                <form action="{{ route('proposal.assignReviewer', $p->id) }}" method="POST" class="plotting-assign-form">
                    @csrf
                    <div class="row g-2">
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small">Reviewer 1</label>
                            {{-- Dropdown Bootstrap (bukan <select> native): opsi adalah
                                 elemen DOM biasa sehingga klik mouse pada opsi selalu
                                 bekerja — popup <select> native tidak bisa dipilih
                                 lewat klik di lingkungan uji (klik opsi gagal, hanya
                                 keyboard/type-ahead yang jalan), sementara tidak ada
                                 overlay atau JS di halaman ini yang menghalangi
                                 <select> tersebut. Nilai yang dikirim tetap ID
                                 reviewer lewat input hidden bernama sama, jadi
                                 validasi server tidak berubah. old() didahulukan agar
                                 isian tidak hilang bila penugasan ditolak server
                                 (mis. reviewer 1 = reviewer 2); fallback ke reviewer
                                 yang sudah ditugaskan. --}}
                            @php
                                $oldR1 = old('reviewer_1', $p->reviewers->get(0)?->id);
                                $labelR1 = '— Pilih reviewer —';
                                foreach (($kandidat[$p->id] ?? collect()) as $k) {
                                    if ((string) $oldR1 === (string) $k['user']->id) {
                                        $labelR1 = $k['user']->name . ($k['skor'] > 0 ? ' — Skor ' . $k['skor'] . ' (' . implode(', ', $k['alasan']) . ')' : '');
                                    }
                                }
                            @endphp
                            <div class="dropdown plotting-reviewer-dropdown">
                                <input type="hidden" name="reviewer_1" value="{{ $oldR1 }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center justify-content-between gap-2 @error('reviewer_1') border-danger @enderror" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="plotting-reviewer-label text-truncate" style="min-width: 0;">{{ $labelR1 }}</span>
                                </button>
                                <ul class="dropdown-menu w-100" style="max-height: 320px; overflow-y: auto;">
                                    <li><button type="button" class="dropdown-item plotting-reviewer-item {{ empty($oldR1) ? 'active' : '' }}" data-value="">— Pilih reviewer —</button></li>
                                    @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                        <li><button type="button" class="dropdown-item plotting-reviewer-item {{ (string) $oldR1 === (string) $k['user']->id ? 'active' : '' }}" data-value="{{ $k['user']->id }}">{{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif</button></li>
                                    @endforeach
                                </ul>
                            </div>
                            @error('reviewer_1')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <label class="form-label small">Reviewer 2</label>
                            {{-- Dropdown Bootstrap, alasan sama seperti Reviewer 1. --}}
                            @php
                                $oldR2 = old('reviewer_2', $p->reviewers->get(1)?->id);
                                $labelR2 = '— Pilih reviewer —';
                                foreach (($kandidat[$p->id] ?? collect()) as $k) {
                                    if ((string) $oldR2 === (string) $k['user']->id) {
                                        $labelR2 = $k['user']->name . ($k['skor'] > 0 ? ' — Skor ' . $k['skor'] . ' (' . implode(', ', $k['alasan']) . ')' : '');
                                    }
                                }
                            @endphp
                            <div class="dropdown plotting-reviewer-dropdown">
                                <input type="hidden" name="reviewer_2" value="{{ $oldR2 }}">
                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle w-100 d-flex align-items-center justify-content-between gap-2 @error('reviewer_2') border-danger @enderror" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="plotting-reviewer-label text-truncate" style="min-width: 0;">{{ $labelR2 }}</span>
                                </button>
                                <ul class="dropdown-menu w-100" style="max-height: 320px; overflow-y: auto;">
                                    <li><button type="button" class="dropdown-item plotting-reviewer-item {{ empty($oldR2) ? 'active' : '' }}" data-value="">— Pilih reviewer —</button></li>
                                    @foreach (($kandidat[$p->id] ?? collect()) as $k)
                                        <li><button type="button" class="dropdown-item plotting-reviewer-item {{ (string) $oldR2 === (string) $k['user']->id ? 'active' : '' }}" data-value="{{ $k['user']->id }}">{{ $k['user']->name }}@if ($k['skor'] > 0) — Skor {{ $k['skor'] }} ({{ implode(', ', $k['alasan']) }})@endif</button></li>
                                    @endforeach
                                </ul>
                            </div>
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

<style>
/* Opsi reviewer yang panjang (nama + skor + alasan) boleh melipat di
   dalam menu dropdown supaya tidak meluber melewati kartu. */
.plotting-reviewer-dropdown .dropdown-item {
    white-space: normal;
}
</style>
@endsection

@push('scripts')
<script>
(function () {
    // Pilihan reviewer di plotting: klik opsi dropdown mengisi input
    // hidden + label tombol (delegasi: satu handler untuk semua kartu
    // proposal di halaman ini).
    document.addEventListener('click', function (e) {
        var item = e.target.closest('.plotting-reviewer-item');
        if (!item) return;
        var wrap = item.closest('.plotting-reviewer-dropdown');
        if (!wrap) return;
        var input = wrap.querySelector('input[type="hidden"]');
        var label = wrap.querySelector('.plotting-reviewer-label');
        var btn = wrap.querySelector('[data-bs-toggle="dropdown"]');
        input.value = item.getAttribute('data-value') || '';
        label.textContent = item.textContent.trim();
        wrap.querySelectorAll('.plotting-reviewer-item').forEach(function (el) {
            el.classList.toggle('active', el === item);
        });
        if (btn) btn.classList.remove('border-danger');
    });

    // Bug #9 (defensif): bila JS Bootstrap gagal dimuat (CDN tidak
    // terjangkau), data-API dropdown tidak aktif dan klik pada tombol
    // Reviewer tidak membuka menu sama sekali. Fallback ini HANYA
    // jalan bila window.bootstrap tidak ada, jadi tidak pernah
    // bentrok/double-toggle dengan data-API Bootstrap yang normalnya
    // menangani buka-tutup menu (bundle dimuat sinkron di <head>).
    // Penyebab pasti klik-pertama-gagal di uji manual ke-4 tidak bisa
    // dibuktikan dari kode statis (bundle hanya dimuat sekali, tidak
    // ada overlay yang menutupi tombol) — kemungkinan besar klik
    // pertama termakan fokus jendela/tab atau balapan saat muat —
    // jadi pembuktian akhir tetap lewat uji web.
    if (!window.bootstrap || !window.bootstrap.Dropdown) {
        document.addEventListener('click', function (e) {
            var toggleBtn = e.target.closest('.plotting-reviewer-dropdown [data-bs-toggle="dropdown"]');
            document.querySelectorAll('.plotting-reviewer-dropdown .dropdown-menu.show').forEach(function (menu) {
                var wrap = menu.closest('.plotting-reviewer-dropdown');
                if (!toggleBtn || !wrap.contains(toggleBtn)) {
                    menu.classList.remove('show');
                    var otherBtn = wrap.querySelector('[data-bs-toggle="dropdown"]');
                    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
                }
            });
            if (toggleBtn) {
                var wrap = toggleBtn.closest('.plotting-reviewer-dropdown');
                var menu = wrap.querySelector('.dropdown-menu');
                var willShow = !menu.classList.contains('show');
                menu.classList.toggle('show', willShow);
                toggleBtn.setAttribute('aria-expanded', willShow ? 'true' : 'false');
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            document.querySelectorAll('.plotting-reviewer-dropdown .dropdown-menu.show').forEach(function (menu) {
                menu.classList.remove('show');
                var btn = menu.closest('.plotting-reviewer-dropdown').querySelector('[data-bs-toggle="dropdown"]');
                if (btn) btn.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Pengganti atribut required pada <select> yang dihapus: cegah
    // submit bila salah satu reviewer belum dipilih. Validasi server
    // (required|exists:users,id di assignReviewer) tetap menjadi
    // penahan terakhir.
    document.querySelectorAll('form.plotting-assign-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var missing = false;
            form.querySelectorAll('.plotting-reviewer-dropdown').forEach(function (wrap) {
                var input = wrap.querySelector('input[type="hidden"]');
                var btn = wrap.querySelector('[data-bs-toggle="dropdown"]');
                if (!input.value) {
                    missing = true;
                    if (btn) btn.classList.add('border-danger');
                }
            });
            if (missing) {
                e.preventDefault();
                if (window.ehibahToast) {
                    window.ehibahToast('Pilih Reviewer 1 dan Reviewer 2 terlebih dahulu.', 'warning');
                }
            }
        });
    });
})();
</script>
@endpush
