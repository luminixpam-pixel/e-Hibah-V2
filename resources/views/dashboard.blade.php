{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')

<div class="container mt-4 mb-5">

    {{-- HEADER DASHBOARD --}}
<div class="dashboard-welcome mb-4">
    <div>
        <h4 class="fw-bold mb-1">
            Selamat datang, {{ $user->name ?? 'Pengguna' }} 👋
        </h4>

        <p class="text-muted mb-0">
            Kelola dan pantau aktivitas hibah Anda dengan mudah melalui E-Hibah Universitas YARSI.
        </p>
    </div>

    <div class="welcome-icon">
        <i class="bi bi-journal-check"></i>
    </div>
</div>

    {{-- ✅ FIX: fallback hitung total kartu kalau variabel dari controller belum keisi / salah --}}
    @php
        // =========================
        // 0) PASTIIN SEMUA VARIABEL ADA (BIAR GAK UNDEFINED)
        // =========================
        $daftarProposalCount   = $daftarProposalCount   ?? 0;

        $reviewSelesaiCount    = $reviewSelesaiCount    ?? 0;
        $perluDireviewCount    = $perluDireviewCount    ?? 0;
        $sedangDireviewCount   = $sedangDireviewCount   ?? 0;

        $direvisiFileCount     = $direvisiFileCount     ?? null;
        $direvisiCount         = $direvisiCount         ?? 0;

        $disetujuiCount        = $disetujuiCount        ?? 0;
        $ditolakCount          = $ditolakCount          ?? 0;
        $hasilRevisiCount      = $hasilRevisiCount      ?? 0;

        $authUser   = auth()->user();
        $currentRole = $role ?? ($authUser->role ?? null);

        // =========================
        // ✅ DETEKSI NAMA TABEL & KOLOM (biar gak ngira tabelnya 'proposals')
        // =========================
        $proposalTable = (new \App\Models\Proposal)->getTable();

        $hasStatusPendanaan = false;
        try {
            $hasStatusPendanaan = \Illuminate\Support\Facades\Schema::hasColumn($proposalTable, 'status_pendanaan');
        } catch (\Throwable $e) {
            $hasStatusPendanaan = false;
        }

        // =========================
        // Helper filter role (biar query card sama seperti halaman sesuai role)
        // =========================
        $applyRoleFilter = function ($query) use ($currentRole, $authUser) {
            if ($currentRole === 'reviewer') {
                $query->whereHas('reviewers', function ($q) use ($authUser) {
                    $q->where('users.id', $authUser->id);
                });
            } elseif ($currentRole !== 'admin') {
                $query->where(function ($q) use ($authUser) {
                    $q->where('user_id', $authUser->id);
                    if (!empty($authUser->username)) {
                        $q->orWhere('user_id', $authUser->username);
                    }
                });
            }
            return $query;
        };

        // =========================
        // Helper keputusan: count "Direvisi/Disetujui/Ditolak" harus sama dengan isi halaman
        // ✅ cek status + (status_pendanaan kalau ada)
        // ✅ TRIM + LOWER biar aman kalau ada spasi / kapital beda
        // ✅ LIKE biar aman kalau ada variasi value (contoh: "Direvisi " / "Direvisi (Perbaikan)")
        // =========================
        $countByDecision = function (string $decision) use ($applyRoleFilter, $hasStatusPendanaan) {
            $base = \App\Models\Proposal::query();
            $base = $applyRoleFilter($base);

            $needle = strtolower(trim((string) $decision));
            $like   = '%' . $needle . '%';

            $base->where(function ($qq) use ($needle, $like, $hasStatusPendanaan) {
                // status
                $qq->whereRaw('LOWER(TRIM(status)) = ?', [$needle])
                   ->orWhereRaw('LOWER(status) LIKE ?', [$like]);

                // status_pendanaan (kalau ada kolomnya)
                if ($hasStatusPendanaan) {
                    $qq->orWhereRaw('LOWER(TRIM(status_pendanaan)) = ?', [$needle])
                       ->orWhereRaw('LOWER(status_pendanaan) LIKE ?', [$like]);
                }
            });

            return (clone $base)->count();
        };

        // ==========================================================
        // ✅ 1) DAFTAR MONITORING PROPOSAL (sesuai halaman monitoring.proposalDikirim)
        // Prinsip: total proposal yg bisa dilihat user sesuai role (TANPA BATAS)
        // ==========================================================
        try {
            $qMonitoring = \App\Models\Proposal::query();
            $qMonitoring = $applyRoleFilter($qMonitoring);

            $dbMonitoringCount = (clone $qMonitoring)->count();

            if ((int)$daftarProposalCount !== (int)$dbMonitoringCount) {
                $daftarProposalCount = $dbMonitoringCount;
            }
        } catch (\Throwable $e) {
            // fallback: biarin value yg ada
        }

        // ==========================================================
        // ✅ 2) REVIEW SELESAI (sesuai halaman monitoring.reviewSelesai)
        // ✅ PERMINTAAN: yang dihitung di CARD hanya proposal yang SUDAH DISETUJUI
        // Prinsip: selesai kalau reviews_count >= reviewers_count DAN keputusan = Disetujui
        // ==========================================================
        try {
            $needleApprove = 'disetujui';
            $likeApprove   = '%'.$needleApprove.'%';

            $qReviewSelesai = \App\Models\Proposal::query()
                ->withCount(['reviews', 'reviewers'])
                ->whereHas('reviewers')
                ->havingRaw('reviews_count >= reviewers_count')
                // ✅ hanya yang disetujui (status OR status_pendanaan)
                ->where(function ($qq) use ($needleApprove, $likeApprove, $hasStatusPendanaan) {
                    $qq->whereRaw('LOWER(TRIM(status)) = ?', [$needleApprove])
                       ->orWhereRaw('LOWER(status) LIKE ?', [$likeApprove]);

                    if ($hasStatusPendanaan) {
                        $qq->orWhereRaw('LOWER(TRIM(status_pendanaan)) = ?', [$needleApprove])
                           ->orWhereRaw('LOWER(status_pendanaan) LIKE ?', [$likeApprove]);
                    }
                });

            $qReviewSelesai = $applyRoleFilter($qReviewSelesai);

            $dbReviewSelesaiCount = (clone $qReviewSelesai)->count();

            if ((int)$reviewSelesaiCount !== (int)$dbReviewSelesaiCount) {
                $reviewSelesaiCount = $dbReviewSelesaiCount;
            }
        } catch (\Throwable $e) {
            // fallback yang tetap sesuai konsep + tetap hanya disetujui
            try {
                $needleApprove = 'disetujui';
                $likeApprove   = '%'.$needleApprove.'%';

                $rows = \App\Models\Proposal::query()
                    ->withCount(['reviews', 'reviewers'])
                    ->whereHas('reviewers')
                    ->where(function ($qq) use ($needleApprove, $likeApprove, $hasStatusPendanaan) {
                        $qq->whereRaw('LOWER(TRIM(status)) = ?', [$needleApprove])
                           ->orWhereRaw('LOWER(status) LIKE ?', [$likeApprove]);

                        if ($hasStatusPendanaan) {
                            $qq->orWhereRaw('LOWER(TRIM(status_pendanaan)) = ?', [$needleApprove])
                               ->orWhereRaw('LOWER(status_pendanaan) LIKE ?', [$likeApprove]);
                        }
                    });

                $rows = $applyRoleFilter($rows)->get(['id']);

                $reviewSelesaiCount = $rows->filter(function ($p) {
                    return (int)$p->reviews_count >= (int)$p->reviewers_count;
                })->count();

            } catch (\Throwable $e2) {
                $reviewSelesaiCount = $reviewSelesaiCount ?? 0;
            }
        }

        // ==========================================================
        // ✅ 3) DAFTAR REVIEW PROPOSAL (sesuai halaman monitoring.proposalPerluDireview)
        // Admin: pipeline yg BELUM selesai (reviewers_count=0 atau reviews_count < reviewers_count)
        // Reviewer: tugas yg belum dia review
        // ==========================================================
        try {
            if ($currentRole === 'admin') {
                $qPerlu = \App\Models\Proposal::query()
                    ->whereIn('status', [
                        'Dikirim',
                        'Menunggu Pemilihan',
                        'Perlu Direview',
                        'Sedang Direview',
                    ])
                    ->withCount(['reviews', 'reviewers'])
                    ->havingRaw('(reviewers_count = 0) OR (reviews_count < reviewers_count)');

                $dbPerlu = (clone $qPerlu)->count();

                if ((int)$perluDireviewCount !== (int)$dbPerlu) {
                    $perluDireviewCount = $dbPerlu;
                }

            } elseif ($currentRole === 'reviewer') {
                $qPerlu = \App\Models\Proposal::query()
                    ->whereIn('status', ['Dikirim', 'Perlu Direview', 'Sedang Direview'])
                    ->whereHas('reviewers', function ($q) use ($authUser) {
                        $q->where('users.id', $authUser->id);
                    })
                    ->whereDoesntHave('reviews', function ($q) use ($authUser) {
                        $q->where('reviewer_id', $authUser->id);
                    });

                $dbPerlu = (clone $qPerlu)->count();

                if ((int)$perluDireviewCount !== (int)$dbPerlu) {
                    $perluDireviewCount = $dbPerlu;
                }
            }
        } catch (\Throwable $e) {
            if ($currentRole === 'admin') {
                try {
                    $rows = \App\Models\Proposal::query()
                        ->whereIn('status', [
                            'Dikirim',
                            'Menunggu Pemilihan',
                            'Perlu Direview',
                            'Sedang Direview',
                        ])
                        ->withCount(['reviews', 'reviewers'])
                        ->get(['id']);

                    $perluDireviewCount = $rows->filter(function ($p) {
                        return ((int)$p->reviewers_count === 0) || ((int)$p->reviews_count < (int)$p->reviewers_count);
                    })->count();
                } catch (\Throwable $e2) {
                    $perluDireviewCount = $perluDireviewCount ?? 0;
                }
            }
        }

        // ==========================================================
        // ✅ 4) SEDANG DIREVIEW (sesuai route monitoring.proposalSedangDireview)
        // ==========================================================
        try {
            $qSedang = \App\Models\Proposal::query()->where('status', 'Sedang Direview');
            $qSedang = $applyRoleFilter($qSedang);

            $dbSedang = (clone $qSedang)->count();

            if ((int)$sedangDireviewCount !== (int)$dbSedang) {
                $sedangDireviewCount = $dbSedang;
            }
        } catch (\Throwable $e) {
            // fallback: biarin
        }

        // ==========================================================
        // ✅ 5) DIREVISI / DISETUJUI / DITOLAK (sesuai masing-masing halaman)
        // IMPORTANT: pakai status + status_pendanaan (trim/lower) biar gak beda
        // ==========================================================
        try {
            // DIREVISI
            $dbDirevisi = $countByDecision('Direvisi');
            $direvisiFileCount = $dbDirevisi;

            // DISETUJUI
            $dbDisetujui = $countByDecision('Disetujui');
            if ((int)$disetujuiCount !== (int)$dbDisetujui) {
                $disetujuiCount = $dbDisetujui;
            }

            // DITOLAK
            $dbDitolak = $countByDecision('Ditolak');
            if ((int)$ditolakCount !== (int)$dbDitolak) {
                $ditolakCount = $dbDitolak;
            }
        } catch (\Throwable $e) {
            // fallback: biarin
        }

        // ✅ pastiin variabel yang dipakai card Direvisi SELALU ada
        $direvisiDisplay = $direvisiFileCount ?? ($direvisiCount ?? 0);
    @endphp

    {{-- ===================== HEADER & FILTER (Hanya Admin) ==================== --}}
    @if($role === 'admin')
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm border">
            <div>
                <h4 class="fw-bold mb-1 text-primary">Dashboard Laporan</h4>
                <p class="text-muted mb-0 small">Monitoring hibah tahunan dan rekapitulasi data fakultas.</p>
            </div>
            <form action="{{ route('dashboard') }}" method="GET" class="d-flex align-items-center gap-2">
                <label class="small fw-bold text-muted text-uppercase" style="font-size: 10px;">Periode Tahun:</label>
                <select name="tahun" class="form-select form-select-sm border shadow-sm" onchange="this.form.submit()" style="width: 110px; font-weight: 600;">
                    @for ($y = date('Y'); $y >= 2023; $y--)
                        <option value="{{ $y }}" {{ $tahun == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </form>
        </div>

        {{-- RINGKASAN ANGKA KUNCI ADMIN --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 bg-primary text-white h-100 position-relative overflow-hidden">
                    <div class="position-relative" style="z-index: 2;">
                        <div class="small fw-bold opacity-75 text-uppercase mb-1">Total Dana Disetujui ({{ $tahun }})</div>
                        <h3 class="fw-bold mb-0">Rp {{ number_format($ringkasanLaporan['total_dana'] ?? 0, 0, ',', '.') }}</h3>
                    </div>
                    <i class="bi bi-cash-stack position-absolute end-0 bottom-0 opacity-25 m-3" style="font-size: 3rem;"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 bg-success text-white h-100 position-relative overflow-hidden">
                    <div class="position-relative" style="z-index: 2;">
                        <div class="small fw-bold opacity-75 text-uppercase mb-1">Total Penerima Hibah</div>
                        <h3 class="fw-bold mb-0">{{ number_format($ringkasanLaporan['total_penerima'] ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Proposal</span></h3>
                    </div>
                    <i class="bi bi-people position-absolute end-0 bottom-0 opacity-25 m-3" style="font-size: 3rem;"></i>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm p-4 bg-warning text-dark h-100 position-relative overflow-hidden">
                    <div class="position-relative" style="z-index: 2;">
                        <div class="small fw-bold opacity-75 text-uppercase mb-1">Total Pengajuan Masuk</div>
                        <h3 class="fw-bold mb-0">{{ number_format($ringkasanLaporan['total_pengajuan'] ?? 0, 0, ',', '.') }} <span class="fs-6 fw-normal">Berkas</span></h3>
                    </div>
                    <i class="bi bi-file-earmark-text position-absolute end-0 bottom-0 opacity-25 m-3" style="font-size: 3rem;"></i>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== PROGRESS STEPPER (Pengaju & Reviewer) ==================== --}}
   @if($role === 'pengaju' || $role === 'reviewer')
    @php
        $direvisiDisplay = $direvisiFileCount ?? ($direvisiCount ?? 0);
        $currentStep = 0;

        // Logika penentuan step (tetap ada untuk warna garis, tapi angka disembunyikan)
        if (($disetujuiCount ?? 0) > 0 || ($direvisiDisplay ?? 0) > 0 || ($hasilRevisiCount ?? 0) > 0 || ($ditolakCount ?? 0) > 0) {
            $currentStep = 4;
        } elseif (($reviewSelesaiCount ?? 0) > 0) {
            $currentStep = 3;
        } elseif (($perluDireviewCount ?? 0) > 0 || ($sedangDireviewCount ?? 0) > 0) {
            $currentStep = 2;
        } elseif (($daftarProposalCount ?? 0) > 0) {
            $currentStep = 1;
        }

        $labelHeader = $role === 'reviewer' ? 'Status Monitoring Penilaian' : 'Status Progress Pengajuan';

        $steps = ($role === 'reviewer')
            ? [1 => 'Masuk', 2 => 'Proses', 3 => 'Selesai', 4 => 'Final']
            : [1 => 'Dikirim', 2 => 'Direview', 3 => 'Keputusan', 4 => 'Selesai'];
    @endphp

   <div class="progress-card mb-4">

    <div class="progress-header">
        <div>
            <h6 class="fw-bold mb-1 text-dark">
                {{ $labelHeader }}
            </h6>
            <p class="text-muted small mb-0">
                Pantau perkembangan proposal Anda pada setiap tahapan.
            </p>
        </div>

        <div class="progress-status-badge">
            @if($currentStep === 4)
                <i class="bi bi-check-circle-fill"></i> Tahap akhir
            @elseif($currentStep === 3)
                <i class="bi bi-hourglass-split"></i> Menunggu keputusan
            @elseif($currentStep === 2)
                <i class="bi bi-search"></i> Sedang diproses
            @elseif($currentStep === 1)
                <i class="bi bi-send-check"></i> Proposal dikirim
            @else
                <i class="bi bi-info-circle"></i> Belum ada pengajuan
            @endif
        </div>
    </div>

    <div class="stepper-modern">

        @foreach($steps as $stepNum => $stepText)

            @php
                $isDone = ($stepNum === 4)
                    ? ($currentStep >= 4)
                    : ($currentStep > $stepNum);

                $isActive = $currentStep === $stepNum;

                $dotClass = $isDone
                    ? 'done'
                    : ($isActive ? 'active' : 'todo');
            @endphp

            <div class="step-modern">

                <div class="step-circle {{ $dotClass }}">
                    @if($isDone)
                        <i class="bi bi-check-lg"></i>
                    @elseif($isActive)
                        <i class="bi bi-dot"></i>
                    @else
                        {{ $stepNum }}
                    @endif
                </div>

                <div class="step-label {{ $isActive ? 'active-label' : '' }}">
                    {{ $stepText }}
                </div>

            </div>

            @if($stepNum < 4)
                <div class="step-connector {{ $currentStep > $stepNum ? 'filled' : '' }}"></div>
            @endif

        @endforeach

    </div>

</div>
@endif

{{-- ===================== YANG PERLU DILAKUKAN ===================== --}}
@if($role === 'pengaju' || $role === 'reviewer')

    @php
        $actionItems = [];

        if ($role === 'pengaju') {

            if (($direvisiFileCount ?? 0) > 0 || ($direvisiCount ?? 0) > 0) {
                $actionItems[] = [
                    'title' => 'Proposal Perlu Direvisi',
                    'description' => 'Silakan periksa catatan revisi dan lengkapi proposal Anda.',
                    'count' => $direvisiFileCount ?? $direvisiCount ?? 0,
                    'route' => 'monitoring.proposalDirevisi',
                    'icon' => 'bi-pencil-square',
                    'class' => 'action-warning'
                ];
            }

            if (($hasilRevisiCount ?? 0) > 0) {
                $actionItems[] = [
                    'title' => 'Hasil Revisi Proposal',
                    'description' => 'Periksa kembali hasil revisi proposal Anda.',
                    'count' => $hasilRevisiCount,
                    'route' => 'monitoring.hasilRevisi',
                    'icon' => 'bi-send-check',
                    'class' => 'action-info'
                ];
            }

        } elseif ($role === 'reviewer') {

            if (($perluDireviewCount ?? 0) > 0) {
                $actionItems[] = [
                    'title' => 'Proposal Menunggu Review',
                    'description' => 'Terdapat proposal yang perlu Anda review.',
                    'count' => $perluDireviewCount,
                    'route' => 'monitoring.proposalPerluDireview',
                    'icon' => 'bi-envelope-paper',
                    'class' => 'action-primary'
                ];
            }

            if (($sedangDireviewCount ?? 0) > 0) {
                $actionItems[] = [
                    'title' => 'Review Sedang Berlangsung',
                    'description' => 'Lanjutkan proses review proposal yang sedang Anda kerjakan.',
                    'count' => $sedangDireviewCount,
                    'route' => 'monitoring.proposalSedangDireview',
                    'icon' => 'bi-arrow-repeat',
                    'class' => 'action-info'
                ];
            }
        }
    @endphp

    @if(count($actionItems) > 0)

        <div class="action-card mb-4">

            <div class="action-header">
                <div>
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-lightning-charge-fill me-1"></i>
                        Yang Perlu Dilakukan
                    </h6>
                    <p class="text-muted small mb-0">
                        Aktivitas yang membutuhkan perhatian Anda.
                    </p>
                </div>

                <span class="action-total">
                    {{ count($actionItems) }} aktivitas
                </span>
            </div>

            <div class="action-list">

                @foreach($actionItems as $action)

                    <a href="{{ route($action['route']) }}"
                       class="action-item text-decoration-none">

                        <div class="action-icon {{ $action['class'] }}">
                            <i class="bi {{ $action['icon'] }}"></i>
                        </div>

                        <div class="action-content">
                            <div class="action-title">
                                {{ $action['title'] }}
                            </div>

                            <div class="action-description">
                                {{ $action['description'] }}
                            </div>
                        </div>

                        <div class="action-count">
                            {{ $action['count'] }}
                        </div>

                        <i class="bi bi-chevron-right action-arrow"></i>

                    </a>

                @endforeach

            </div>

        </div>

    @else

        <div class="action-card action-empty mb-4">

            <div class="empty-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <h6 class="fw-bold mb-1">
                    Tidak ada tindakan yang diperlukan
                </h6>

                <p class="text-muted small mb-0">
                    Semua aktivitas Anda saat ini sudah tertangani.
                </p>
            </div>

        </div>

    @endif

@endif

    {{-- ===================== MONITORING CARDS ==================== --}}
@php
    $allMenus = [
        'monitoring'     => ['title'=>'Daftar Monitoring Proposal','count'=>$daftarProposalCount ?? 0,'route'=>'monitoring.proposalDikirim','icon'=>'bi-file-earmark-text'],
        'perlu_review'   => ['title'=>'Daftar Review Proposal','count'=>$perluDireviewCount ?? 0,'route'=>'monitoring.proposalPerluDireview','icon'=>'bi-envelope-paper'],
        'sedang_review'  => ['title'=>'Daftar Proposal Sedang Direview','count'=>$sedangDireviewCount ?? 0,'route'=>'monitoring.proposalSedangDireview','icon'=>'bi-arrow-repeat'],
        'review_selesai' => ['title'=>'Daftar Review Selesai','count'=>$reviewSelesaiCount ?? 0,'route'=>'monitoring.reviewSelesai','icon'=>'bi-clipboard-check'],
        'disetujui'      => ['title'=>'Daftar Proposal Disetujui','count'=>$disetujuiCount ?? 0,'route'=>'monitoring.proposalDisetujui','icon'=>'bi-check-circle'],
        'ditolak'        => ['title'=>'Daftar Proposal Ditolak','count'=>$ditolakCount ?? 0,'route'=>'monitoring.proposalDitolak','icon'=>'bi-x-circle'],
        'direvisi'       => ['title'=>'Daftar Proposal Direvisi','count'=>$direvisiDisplay ?? 0,'route'=>'monitoring.proposalDirevisi','icon'=>'bi-pencil-square'],
        'hasil_revisi'   => ['title'=>'Hasil Revisi Proposal','count'=>$hasilRevisiCount ?? 0,'route'=>'monitoring.hasilRevisi','icon'=>'bi-send-check'],
    ];

    $dashboardItems = [];

    if($role === 'admin' || $role === 'reviewer') {
        $dashboardItems = $allMenus;
    } elseif($role === 'pengaju') {
        $dashboardItems = [
            $allMenus['monitoring'],
            $allMenus['review_selesai'],
            $allMenus['disetujui'],
            $allMenus['ditolak'],
            $allMenus['direvisi'],
            $allMenus['hasil_revisi']
        ];
    }
@endphp

@php
    $rowAlignment = (count($dashboardItems) < 4) ? 'justify-content-center' : '';
@endphp

<div class="row g-3 mb-4 {{ $rowAlignment }}">
    @foreach($dashboardItems as $item)
        <div class="col-6 col-md-3">
            <a href="{{ route($item['route']) }}" class="text-decoration-none dashboard-link">
                <div class="card border-0 shadow-sm p-3 h-100 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="icon-box bg-primary-subtle p-2 rounded">
                            <i class="bi {{ $item['icon'] }} text-primary fs-5"></i>
                        </div>
                        {{--
                            Hanya tampilkan angka jika role adalah admin.
                            Untuk Pengaju & Reviewer, angka tidak akan muncul.
                        --}}
                        @if($role === 'admin')
                            <h4 class="fw-bold mb-0 text-dark">{{ $item['count'] }}</h4>
                        @endif
                    </div>
                    <h6 class="mb-0 text-muted small fw-bold text-uppercase" style="font-size: 10px;">{{ $item['title'] }}</h6>
                </div>
            </a>
        </div>
    @endforeach
</div>

    {{-- ===================== REKAP FAKULTAS (Hanya Admin) ==================== --}}
    @if($role === 'admin' && isset($rekapFakultas))
    <div class="card border-0 shadow-sm mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>Distribusi Hibah per Fakultas</h6>
            <span class="badge bg-light text-muted border fw-normal">Data Tahun {{ $tahun }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr class="small text-muted text-uppercase" style="font-size: 10px;">
                        <th class="ps-4 py-3">Fakultas</th>
                        <th class="text-center">Pengajuan</th>
                        <th class="text-center">Disetujui</th>
                        <th class="text-end pe-4">Total Dana (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rekapFakultas as $rekap)
                    <tr>
                       <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $rekap->fakultas ?? 'N/A' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold">{{ $rekap->total_pengajuan }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-3">{{ $rekap->total_disetujui }}</span>
                        </td>
                        <td class="text-end pe-4 fw-bold text-primary">
                            Rp {{ number_format($rekap->total_biaya ?? 0, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                            Belum ada rekapan data untuk tahun {{ $tahun }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ===================== PROFIL & INFORMASI ==================== --}}
<div class="row g-4">
    {{-- Sisi Kiri: Profil Pengguna --}}
    <div class="col-md-7">
        <div class="card p-4 border-0 shadow-sm h-100">
            <h6 class="fw-bold mb-3 border-bottom pb-2">
                <i class="bi bi-person-badge me-2 text-primary"></i>Profil Pengguna
            </h6>
            <div class="row g-3">
                {{-- Nama Lengkap --}}
                <div class="col-sm-6">
                    <label class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Nama Lengkap</label>
                    <span class="text-dark fw-medium">{{ $user->name ?? '-' }}</span>
                </div>

                {{-- Hak Akses --}}
                <div class="col-sm-6">
                    <label class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Hak Akses</label>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                        {{ strtoupper($user->role ?? $role) }}
                    </span>
                </div>

                {{-- Logika Fakultas: Sembunyikan input jika Admin --}}
                @if($user->role !== 'admin')
                    <div class="col-12">
                        <label class="form-label small fw-bold text-secondary">Fakultas / Unit</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                            <input type="text"
                                class="form-control bg-light"
                                value="{{ $user->fakultas ?? 'Belum diatur di profil' }}"
                                readonly>
                        </div>
                        <input type="hidden" name="fakultas_prodi" value="{{ $user->fakultas }}">
                        <small class="text-muted" style="font-size: 0.7rem;">*Data diambil otomatis dari profil Anda</small>
                    </div>
                @else
                    <div class="col-sm-6">
                        <label class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Unit Kerja</label>
                        <span class="text-dark fw-medium">Pusat Teknologi Informasi (Puskom)</span>
                    </div>
                @endif

                {{-- Email --}}
                <div class="col-sm-6">
                    <label class="text-muted small fw-bold text-uppercase d-block" style="font-size: 10px;">Email Terdaftar</label>
                    <span class="text-dark fw-medium">{{ $user->email ?? '-' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sisi Kanan: Pusat Bantuan --}}
    <div class="col-md-5">
        <div class="card p-4 border-0 shadow-sm h-100 bg-light border">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2 text-primary"></i>Pusat Bantuan</h6>
            <p class="small text-muted mb-4">Butuh panduan pengisian? Unduh dokumen petunjuk teknis di bawah ini atau hubungi admin fakultas.</p>
            <div class="d-grid mt-auto">
               <a href="{{ route('dokumen.index') }}" class="btn btn-primary btn-sm rounded-pill shadow-sm">
                    <i class="bi bi-download me-2"></i>Unduh Panduan Layanan
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Global Card styling */
    .card { border-radius: 12px; }

    /* Interactive Dashboard Cards */
    .dashboard-link .card { transition: all 0.2s ease-in-out; border: 1px solid transparent; }
    .dashboard-link:hover .card { transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,0.05) !important; border-color: #0d6efd; }
    .icon-box { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; }

    /* Stepper Logic */
    .stepper { display: flex; align-items: center; justify-content: space-between; padding: 0 10px; }
    .step { display: flex; flex-direction: column; align-items: center; z-index: 2; position: relative; width: 60px; }
    .dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; background: #fff; border: 2px solid #e9ecef; color: #adb5bd; transition: 0.4s; }
    .dot.done { background: #198754; border-color: #198754; color: #fff; }
    .dot.active { border-color: #0d6efd; color: #0d6efd; background: #eef6ff; box-shadow: 0 0 0 4px rgba(13,110,253,0.1); }
    .label { margin-top: 8px; font-weight: 700; font-size: 11px; color: #6c757d; text-transform: uppercase; }
    .line { flex: 1; height: 3px; background: #e9ecef; margin-top: -22px; z-index: 1; border-radius: 10px; }
    .line.filled { background: #198754; }

    /* Table & Badges */
    .bg-success-subtle { background-color: #e8f5e9; color: #2e7d32; }
    .bg-primary-subtle { background-color: #eef6ff; }

    .dashboard-welcome {
    background: linear-gradient(135deg, #ffffff, #f1fff4);
    border: 1px solid #e8f0ea;
    border-radius: 16px;
    padding: 22px 25px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.dashboard-welcome h4 {
    color: #1f2937;
}

.welcome-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #1fbd4c;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 23px;
    flex-shrink: 0;
}

@media (max-width: 576px) {
    .dashboard-welcome {
        padding: 18px;
    }

    .dashboard-welcome h4 {
        font-size: 18px;
    }

    .welcome-icon {
        width: 44px;
        height: 44px;
        font-size: 19px;
    }
}

/* =================================
   MODERN PROGRESS STEPPER
================================= */

.progress-card {
    background: #fff;
    border: 1px solid #edf0f2;
    border-radius: 16px;
    padding: 22px 24px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
    margin-bottom: 28px;
}

.progress-status-badge {
    padding: 7px 12px;
    border-radius: 20px;
    background: #f0fdf4;
    color: #15803d;
    font-size: 11px;
    font-weight: 600;
    white-space: nowrap;
}

.progress-status-badge i {
    margin-right: 4px;
}

/* Stepper */

.stepper-modern {
    display: flex;
    align-items: center;
    width: 100%;
}

.step-modern {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 85px;
}

.step-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: 700;
    border: 3px solid #e5e7eb;
    background: #fff;
    color: #9ca3af;
    position: relative;
    z-index: 2;
    transition: all .25s ease;
}

.step-circle.done {
    background: #16a34a;
    border-color: #16a34a;
    color: #fff;
}

.step-circle.active {
    background: #fff;
    border-color: #2563eb;
    color: #2563eb;
    box-shadow: 0 0 0 5px rgba(37,99,235,.10);
}

.step-circle.todo {
    background: #fff;
    border-color: #e5e7eb;
    color: #9ca3af;
}

.step-label {
    margin-top: 9px;
    font-size: 11px;
    font-weight: 600;
    color: #9ca3af;
    text-align: center;
    white-space: nowrap;
}

.step-label.active-label {
    color: #2563eb;
    font-weight: 700;
}

.step-connector {
    height: 3px;
    background: #e5e7eb;
    flex: 1;
    margin: 0 -2px;
    margin-bottom: 27px;
    border-radius: 5px;
    transition: background .25s ease;
}

.step-connector.filled {
    background: #16a34a;
}

/* Mobile */

@media (max-width: 576px) {

    .progress-card {
        padding: 18px 14px;
    }

    .progress-header {
        align-items: flex-start;
        flex-direction: column;
        margin-bottom: 24px;
    }

    .progress-status-badge {
        font-size: 10px;
    }

    .step-modern {
        min-width: 55px;
    }

    .step-circle {
        width: 34px;
        height: 34px;
        font-size: 12px;
    }

    .step-label {
        font-size: 9px;
        white-space: normal;
        line-height: 1.3;
    }

    .step-connector {
        margin-bottom: 27px;
    }
}

/* =================================
   YANG PERLU DILAKUKAN
================================= */

.action-card {
    background: #fff;
    border: 1px solid #edf0f2;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,.05);
}

.action-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.action-header h6 {
    color: #1f2937;
}

.action-total {
    padding: 5px 10px;
    border-radius: 20px;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 10px;
    font-weight: 600;
}

.action-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.action-item {
    display: flex;
    align-items: center;
    gap: 13px;
    padding: 13px;
    border-radius: 12px;
    border: 1px solid #f0f1f3;
    transition: all .2s ease;
}

.action-item:hover {
    background: #f9fafb;
    transform: translateX(3px);
}

.action-icon {
    width: 40px;
    height: 40px;
    min-width: 40px;
    border-radius: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
}

.action-primary {
    background: #eff6ff;
    color: #2563eb;
}

.action-info {
    background: #ecfeff;
    color: #0891b2;
}

.action-warning {
    background: #fffbeb;
    color: #d97706;
}

.action-content {
    flex: 1;
    min-width: 0;
}

.action-title {
    color: #374151;
    font-size: 13px;
    font-weight: 700;
    margin-bottom: 2px;
}

.action-description {
    color: #9ca3af;
    font-size: 11px;
    line-height: 1.4;
}

.action-count {
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    border-radius: 20px;
    background: #f3f4f6;
    color: #374151;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
}

.action-arrow {
    color: #9ca3af;
    font-size: 13px;
}

.action-empty {
    display: flex;
    align-items: center;
    gap: 14px;
}

.empty-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #f0fdf4;
    color: #16a34a;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
}

@media (max-width: 576px) {

    .action-card {
        padding: 16px;
    }

    .action-header {
        align-items: flex-start;
        gap: 10px;
    }

    .action-description {
        font-size: 10px;
    }

    .action-icon {
        width: 36px;
        min-width: 36px;
        height: 36px;
        font-size: 15px;
    }
}
</style>
@endpush
