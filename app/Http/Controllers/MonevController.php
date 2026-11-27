<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\ActivityLog;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Temuan Monev + RTL (Fase 5, story 10).
 *
 * Alur status temuan: Open -> In_Review -> Closed
 *  - LPPM membuat temuan (status awal 'Open').
 *  - Pengaju pemilik proposal mengirim RTL + bukti perbaikan
 *    (status jadi 'In_Review').
 *  - LPPM memverifikasi: 'tutup' -> 'Closed', 'kembalikan' -> 'Open'.
 *
 * Semua fitur di sini HANYA untuk proposal berstatus 'Disetujui'
 * (kolom status; keputusan admin menulis 'Disetujui' ke kolom
 * status dan status_pendanaan — lihat ProposalController::keputusan
 * dan ProposalController::proposalDisetujui).
 *
 * Catatan: jangan pakai Auth::id() / auth()->id() untuk membandingkan
 * ID angka (itu mengembalikan USERNAME string) — selalu pakai
 * auth()->user()->id.
 */
class MonevController extends Controller
{
    /**
     * Helper internal untuk mencatat log aktivitas pengguna
     * (pola yang sama dengan ProposalController::logActivity).
     */
    private function logActivity($activity)
    {
        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Apakah user boleh melihat proposal ini (dan temuannya)?
     *  - admin / lppm / kepala_lppm: semua proposal.
     *  - pengaju: hanya proposal miliknya.
     *  - dekan: hanya proposal di fakultasnya (meniru filter dekan di
     *    RoleDashboardController::dekan(); bila fakultas user belum
     *    diisi, semua proposal terlihat).
     *  - reviewer / verifikator: tidak boleh.
     */
    private function canViewProposal(User $user, Proposal $proposal): bool
    {
        if (in_array($user->role, ['admin', 'lppm', 'kepala_lppm'], true)) {
            return true;
        }

        if ($user->role === 'pengaju') {
            return (int) $proposal->user_id === (int) $user->id;
        }

        if ($user->role === 'dekan') {
            if (! $user->fakultas_id) {
                return true;
            }

            return (int) $proposal->fakultas_prodi === (int) $user->fakultas_id;
        }

        return false;
    }

    /**
     * GET /monev — daftar temuan.
     * Reviewer dan verifikator ditolak 403.
     */
    public function index()
    {
        $user = auth()->user();

        if (in_array($user->role, ['reviewer', 'verifikator'], true)) {
            abort(403);
        }

        $query = MonevFinding::with(['proposal.user'])
            ->whereHas('proposal', function ($q) {
                $q->where('status', 'Disetujui');
            });

        if ($user->role === 'pengaju') {
            $query->whereHas('proposal', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        } elseif ($user->role === 'dekan' && $user->fakultas_id) {
            $query->whereHas('proposal', function ($q) use ($user) {
                $q->where('fakultas_prodi', $user->fakultas_id);
            });
        }

        $findings = $query->latest()->get();

        return view('pasca-hibah.monev-index', [
            'findings' => $findings,
            'user' => $user,
        ]);
    }

    /**
     * GET /monev/{proposal} — daftar temuan satu proposal Disetujui
     * + form buat temuan (LPPM) + form kirim RTL (pengaju pemilik).
     */
    public function show(Proposal $proposal)
    {
        if (! $this->canViewProposal(auth()->user(), $proposal)) {
            abort(403);
        }

        if ($proposal->status !== 'Disetujui') {
            return redirect()->back()->with('error', 'Temuan monev hanya tersedia untuk proposal yang sudah Disetujui (status saat ini: '.$proposal->status.').');
        }

        $findings = MonevFinding::where('proposal_id', $proposal->id)
            ->latest()
            ->get();

        return view('pasca-hibah.monev-show', [
            'proposal' => $proposal,
            'findings' => $findings,
            'user' => auth()->user(),
        ]);
    }

    /**
     * POST /monev/{proposal} — LPPM membuat temuan baru (status awal 'Open').
     * Route akan diberi middleware role:lppm oleh koordinator;
     * controller tetap memeriksa role dan abort 403 bila bukan lppm.
     */
    public function store(Request $request, Proposal $proposal)
    {
        if (auth()->user()->role !== 'lppm') {
            abort(403);
        }

        if ($proposal->status !== 'Disetujui') {
            return redirect()->back()->with('error', 'Temuan monev hanya bisa dibuat untuk proposal yang sudah Disetujui (status saat ini: '.$proposal->status.').');
        }

        $request->validate([
            'uraian_temuan' => 'required|string',
            'rekomendasi' => 'required|string',
        ]);

        $finding = MonevFinding::create([
            'proposal_id' => $proposal->id,
            'uraian_temuan' => $request->uraian_temuan,
            'rekomendasi' => $request->rekomendasi,
            'status_temuan' => 'Open',
        ]);

        NotificationController::notifyMonevDibuat($proposal, $finding);

        $this->logActivity("LPPM mencatat temuan Monev untuk proposal ID {$proposal->id} (status temuan: Open)");

        return redirect()->route('monev.show', $proposal)
            ->with('success', 'Temuan monev berhasil dicatat (status: Open).');
    }

    /**
     * POST /monev/{finding}/rtl — pengaju pemilik proposal mengirim
     * RTL + bukti perbaikan (PDF, maks 2 MB). Status jadi 'In_Review'.
     * Hanya bila status temuan Open atau In_Review; Closed tidak bisa.
     */
    public function kirimRtl(Request $request, MonevFinding $finding)
    {
        $proposal = $finding->proposal;

        if (! $proposal || $proposal->status !== 'Disetujui') {
            return redirect()->back()->with('error', 'RTL hanya bisa dikirim untuk temuan pada proposal yang sudah Disetujui.');
        }

        // Hanya pengaju pemilik proposal terkait.
        if (auth()->user()->role !== 'pengaju' || (int) $proposal->user_id !== (int) auth()->user()->id) {
            abort(403);
        }

        if (! in_array($finding->status_temuan, ['Open', 'In_Review'], true)) {
            return redirect()->back()->with('error', 'RTL tidak bisa dikirim karena temuan sudah Closed.');
        }

        $request->validate([
            'file_rtl' => 'required|file|mimes:pdf|max:2048',
            'bukti_perbaikan' => 'required|file|mimes:pdf|max:2048',
        ]);

        // Hapus file lama saat diganti (kirim ulang saat In_Review).
        if ($finding->file_rtl && Storage::disk('public')->exists($finding->file_rtl)) {
            Storage::disk('public')->delete($finding->file_rtl);
        }

        if ($finding->bukti_perbaikan && Storage::disk('public')->exists($finding->bukti_perbaikan)) {
            Storage::disk('public')->delete($finding->bukti_perbaikan);
        }

        $finding->update([
            'file_rtl' => $request->file('file_rtl')->store('monev_rtl', 'public'),
            'bukti_perbaikan' => $request->file('bukti_perbaikan')->store('monev_bukti', 'public'),
            'status_temuan' => 'In_Review',
        ]);

        NotificationController::notifyRtlDikirim($proposal, $finding);

        $this->logActivity("Pengaju mengirim RTL untuk temuan Monev ID {$finding->id} proposal ID {$proposal->id} (status temuan: In_Review)");

        return redirect()->route('monev.show', $proposal)
            ->with('success', 'RTL dan bukti perbaikan berhasil dikirim (status: In_Review).');
    }

    /**
     * POST /monev/{finding}/verifikasi — LPPM memverifikasi RTL.
     *  - 'tutup' -> 'Closed' (hanya bila status saat ini In_Review).
     *  - 'kembalikan' -> 'Open' (hanya bila In_Review; bila masih
     *    Open ditolak dengan error).
     */
    public function verifikasi(Request $request, MonevFinding $finding)
    {
        if (auth()->user()->role !== 'lppm') {
            abort(403);
        }

        $request->validate([
            'keputusan' => 'required|in:tutup,kembalikan',
        ]);

        if ($finding->status_temuan !== 'In_Review') {
            return redirect()->back()->with('error', 'Hanya temuan berstatus In_Review yang bisa diverifikasi (status saat ini: '.$finding->status_temuan.').');
        }

        $proposal = $finding->proposal;

        if ($request->keputusan === 'tutup') {
            $finding->update(['status_temuan' => 'Closed']);

            $this->logActivity("LPPM memverifikasi RTL temuan Monev ID {$finding->id} proposal ID {$proposal->id} (status temuan: Closed)");

            NotificationHelper::send(
                (int) $proposal->user_id,
                'Temuan Monev Ditutup',
                'RTL untuk proposal "'.$proposal->judul.'" telah diverifikasi LPPM dan temuan dinyatakan selesai (Closed).',
                'success',
                $proposal->id
            );

            return redirect()->route('monev.show', $proposal)
                ->with('success', 'Temuan ditutup (status: Closed).');
        }

        $finding->update(['status_temuan' => 'Open']);

        $this->logActivity("LPPM memverifikasi RTL temuan Monev ID {$finding->id} proposal ID {$proposal->id} (status temuan: Open)");

        NotificationHelper::send(
            (int) $proposal->user_id,
            'RTL Dikembalikan',
            'RTL untuk proposal "'.$proposal->judul.'" dikembalikan oleh LPPM. Silakan perbaiki dan kirim ulang RTL beserta bukti perbaikan.',
            'warning',
            $proposal->id
        );

        return redirect()->route('monev.show', $proposal)
            ->with('success', 'RTL dikembalikan, temuan kembali Open.');
    }

    /**
     * GET /monev/{finding}/download/{jenis} — unduh file_rtl ('rtl')
     * atau bukti_perbaikan ('bukti'). 403 bila tidak berhak melihat.
     */
    public function download(MonevFinding $finding, $jenis)
    {
        $proposal = $finding->proposal;

        if (! $proposal || ! $this->canViewProposal(auth()->user(), $proposal)) {
            abort(403);
        }

        if ($jenis === 'rtl') {
            $path = $finding->file_rtl;
            $label = 'RTL';
        } elseif ($jenis === 'bukti') {
            $path = $finding->bukti_perbaikan;
            $label = 'Bukti_Perbaikan';
        } else {
            abort(404);
        }

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $cleanTitle = str_replace([' ', '/', '\\'], '_', $proposal->judul);
        $downloadName = $label.'_'.$cleanTitle.'.'.$extension;

        return Storage::disk('public')->download($path, $downloadName);
    }

    /**
     * GET /monev/{finding}/preview/{jenis} — preview file_rtl ('rtl')
     * atau bukti_perbaikan ('bukti'). Otorisasi sama seperti download():
     * cek canViewProposal dulu (403), jenis selain itu 404.
     */
    public function preview(MonevFinding $finding, $jenis)
    {
        $proposal = $finding->proposal;

        if (! $proposal || ! $this->canViewProposal(auth()->user(), $proposal)) {
            abort(403);
        }

        if ($jenis === 'rtl') {
            $path = $finding->file_rtl;
            $label = 'RTL';
        } elseif ($jenis === 'bukti') {
            $path = $finding->bukti_perbaikan;
            $label = 'Bukti_Perbaikan';
        } else {
            abort(404);
        }

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $cleanTitle = str_replace([' ', '/', '\\'], '_', $proposal->judul);
        $displayName = $label.'_'.$cleanTitle.'.'.$extension;

        return $this->previewStoredFile(
            $path,
            $displayName,
            'monev.download',
            ['finding' => $finding->id, 'jenis' => $jenis]
        );
    }
}
