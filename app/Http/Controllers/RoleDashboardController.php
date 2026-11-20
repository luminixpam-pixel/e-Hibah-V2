<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Notification;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard per role (satu dashboard untuk tiap role).
 *
 * Alur login:
 *  - AuthController::login() mengarahkan user ke dashboard rolenya
 *    lewat dashboardUrlFor() (lihat AuthController).
 *  - Route GET /dashboard memanggil home() di sini, yang juga
 *    mengarahkan ke dashboard sesuai role. Jadi link lama ke
 *    /dashboard tetap berfungsi.
 *
 * Pemetaan role -> dashboard (sesuai enum role di tabel users,
 * setelah migration penambah role):
 *  admin       -> Administrator TI (IT Admin)
 *  pengaju     -> Dosen / Ketua Pengusul & Anggota Tim
 *  reviewer    -> Reviewer
 *  verifikator -> Verifikator Administrasi
 *  lppm        -> Pengelola LPPM
 *  dekan       -> Pimpinan Fakultas
 *  kepala_lppm -> Kepala LPPM
 *
 * Catatan: DashboardController@index dan view dashboard.blade.php
 * yang lama TIDAK dihapus — tetap ada kalau mau kembali ke
 * satu dashboard bersama.
 */
class RoleDashboardController extends Controller
{
    /**
     * GET /dashboard
     * Arahkan ke dashboard sesuai role user yang login.
     */
    public function home()
    {
        return redirect()->route($this->routeNameFor(Auth::user()->role));
    }

    /**
     * GET /dashboard/admin — Administrator TI (role: admin)
     */
    public function admin()
    {
        $user = Auth::user();

        $stats = [
            'totalUsers'     => User::count(),
            'totalProposals' => Proposal::count(),
            'disetujui'      => Proposal::where('status_pendanaan', 'Disetujui')->count(),
            'ditolak'        => Proposal::where('status_pendanaan', 'Ditolak')->count(),
            'direvisi'       => Proposal::where('status_pendanaan', 'Direvisi')->count(),
        ];

        $recentLogs  = ActivityLog::with('user')->latest()->take(5)->get();
        $recentUsers = User::latest()->take(5)->get();

        return view('dashboard.admin', array_merge(
            compact('user', 'recentLogs', 'recentUsers'),
            $stats
        ));
    }

    /**
     * GET /dashboard/pengaju — Dosen / Ketua Pengusul & Anggota Tim (role: pengaju)
     */
    public function pengaju()
    {
        $user = Auth::user();

        $myProposals = Proposal::where('user_id', $user->id)->latest()->get();

        $stats = [
            'total'     => $myProposals->count(),
            'draft'     => $myProposals->where('status', 'Draft')->count(),
            'dikirim'   => $myProposals->where('status', 'Dikirim')->count(),
            'disetujui' => $myProposals->where('status_pendanaan', 'Disetujui')->count(),
            'ditolak'   => $myProposals->where('status_pendanaan', 'Ditolak')->count(),
        ];

        $notifications = Notification::where('user_id', $user->id)->latest()->take(5)->get();

        return view('dashboard.pengaju', array_merge(
            compact('user', 'myProposals', 'notifications'),
            $stats
        ));
    }

    /**
     * GET /dashboard/reviewer — Reviewer (role: reviewer)
     */
    public function reviewer()
    {
        $user = Auth::user();

        $assignedIds = DB::table('proposal_reviewers')
            ->where('reviewer_id', $user->id)
            ->pluck('proposal_id');

        $tugasReview = Proposal::whereIn('id', $assignedIds->isEmpty() ? [0] : $assignedIds)
            ->with('user')
            ->latest()
            ->get();

        $reviewedIds = Review::where('reviewer_id', $user->id)->pluck('proposal_id');

        $stats = [
            'totalTugas' => $tugasReview->count(),
            'menunggu'   => $tugasReview->whereNotIn('id', $reviewedIds)->count(),
            'selesai'    => $reviewedIds->count(),
        ];

        return view('dashboard.reviewer', array_merge(
            compact('user', 'tugasReview'),
            $stats
        ));
    }

    /**
     * GET /dashboard/verifikator — Verifikator Administrasi (role: verifikator)
     */
    public function verifikator()
    {
        $user = Auth::user();

        $antrean = Proposal::where('status', 'Dikirim')->with('user')->latest()->get();

        $stats = [
            'antrean_count' => $antrean->count(),
            'lolos'         => Proposal::where('status', 'Lolos Administrasi')->count(),
            'revisi'        => Proposal::where('status', 'Revisi Administrasi')->count(),
        ];

        return view('dashboard.verifikator', array_merge(
            compact('user', 'antrean'),
            $stats
        ));
    }

    /**
     * GET /dashboard/lppm — Pengelola LPPM (role: lppm)
     */
    public function lppm()
    {
        $user = Auth::user();

        $stats = [
            'lolosAdmin'     => Proposal::where('status', 'Lolos Administrasi')->count(),
            'sedangDireview' => Proposal::where('status', 'Sedang Direview')->count(),
            'reviewSelesai'  => Proposal::where('status', 'Review Selesai')->count(),
            'disetujui'      => Proposal::where('status_pendanaan', 'Disetujui')->count(),
        ];

        $siapPlotting = Proposal::where('status', 'Lolos Administrasi')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard.lppm', array_merge(
            compact('user', 'siapPlotting'),
            $stats
        ));
    }

    /**
     * GET /dashboard/dekan — Pimpinan Fakultas (role: dekan)
     * Proposal difilter berdasarkan fakultas user (fakultas_id).
     * Kalau fakultas user belum diisi, tampilkan semua proposal.
     */
    public function dekan()
    {
        $user = Auth::user();

        $base = Proposal::query();
        if ($user->fakultas_id) {
            $base->where('fakultas_prodi', $user->fakultas_id);
        }

        $stats = [
            'total'     => (clone $base)->count(),
            'disetujui' => (clone $base)->where('status_pendanaan', 'Disetujui')->count(),
            'ditolak'   => (clone $base)->where('status_pendanaan', 'Ditolak')->count(),
        ];

        $proposals = (clone $base)->with('user')->latest()->take(10)->get();

        return view('dashboard.dekan', array_merge(
            compact('user', 'proposals'),
            $stats
        ));
    }

    /**
     * GET /dashboard/kepala-lppm — Kepala LPPM (role: kepala_lppm)
     */
    public function kepalaLppm()
    {
        $user = Auth::user();

        $stats = [
            'menungguKeputusan' => Proposal::where('status', 'Review Selesai')->count(),
            'disetujui'         => Proposal::where('status_pendanaan', 'Disetujui')->count(),
            'ditolak'           => Proposal::where('status_pendanaan', 'Ditolak')->count(),
            'direvisi'          => Proposal::where('status_pendanaan', 'Direvisi')->count(),
        ];

        $siapDiputuskan = Proposal::where('status', 'Review Selesai')
            ->with('user')
            ->latest()
            ->take(10)
            ->get();

        $riwayatKeputusan = Proposal::whereIn('status_pendanaan', ['Disetujui', 'Ditolak', 'Direvisi'])
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard.kepala-lppm', array_merge(
            compact('user', 'siapDiputuskan', 'riwayatKeputusan'),
            $stats
        ));
    }

    /**
     * Nama route dashboard sesuai role.
     * Role yang tidak dikenal diarahkan ke dashboard pengaju
     * (paling umum) supaya tidak terjadi redirect loop ke /dashboard.
     */
    protected function routeNameFor(?string $role): string
    {
        return match ($role) {
            'admin'       => 'dashboard.admin',
            'pengaju'     => 'dashboard.pengaju',
            'reviewer'    => 'dashboard.reviewer',
            'verifikator' => 'dashboard.verifikator',
            'lppm'        => 'dashboard.lppm',
            'dekan'       => 'dashboard.dekan',
            'kepala_lppm' => 'dashboard.kepala-lppm',
            default       => 'dashboard.pengaju',
        };
    }
}
