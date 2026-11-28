<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LaporanKemajuanController extends Controller
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

    public function index(Request $request)
    {
        $search = $request->input('search');
        $user = Auth::user();

        // 1. Query Dasar untuk Tabel
        $query = Proposal::with(['user', 'reviewers']);

        // 2. Filter Role (Security Filter)
        if ($user->role === 'pengaju') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'reviewer') {
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // 3. Search Logic
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $proposals = $query->latest()->paginate(10);

        // 4. Data untuk Dropdown Pilih Proposal (Khusus Pengaju).
        // Hanya proposal yang sudah Disetujui pendanaannya (pasca-hibah)
        // yang boleh diunggah laporan kemajuannya. Definisi "disetujui"
        // di sini adalah kolom status_pendanaan (keputusan pendanaan),
        // BUKAN kolom status (status alur: 'Review Selesai' hanya
        // berarti review selesai, belum tentu didanai). Query lama
        // memakai status sehingga proposal yang baru lolos review tapi
        // belum diputuskan pendanaannya tidak bisa dibedakan — dan
        // sebaliknya tabel (tanpa filter status) menampilkan proposal
        // itu sehingga form terlihat kosong padahal tabel menampilkan
        // proposal yang "sudah Disetujui" (uji manual ke-4, bug #10).
        $myProposals = collect();
        if (in_array($user->role, ['pengaju', 'reviewer'], true)) {
            $myProposals = Proposal::where('user_id', $user->id)
                ->where('status_pendanaan', 'Disetujui')
                ->latest()
                ->get(['id', 'judul']);
        }

        return view('reviewer.laporan-kemajuan', [
            'proposals' => $proposals,
            'myProposals' => $myProposals,
            'user' => $user, // Mengirim data user login untuk Profile
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'proposal_id' => 'required|exists:proposals,id', // ✅ pilih proposal
            'file' => 'required|mimes:pdf,doc,docx|max:10240',
            'keterangan' => 'nullable|string',
        ]);

        if ($request->hasFile('file')) {

            // ✅ ambil proposal yang dipilih + pastikan milik user login
            $proposal = Proposal::where('id', $request->proposal_id)
                ->whereHas('user', function ($q) {
                    $q->where('username', Auth::user()->username);
                })->first();

            if (! $proposal) {
                return redirect()->back()->with('error', 'Proposal tidak valid / bukan milik Anda.');
            }

            // Laporan kemajuan hanya untuk proposal yang sudah disetujui
            // pendanaannya (sama seperti dropdown di halaman ini):
            // cek status_pendanaan, bukan status alur.
            if ($proposal->status_pendanaan !== 'Disetujui') {
                return redirect()->back()->with('error', 'Laporan kemajuan hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
            }

            // Hapus file lama jika ada
            if ($proposal->file_laporan && Storage::disk('public')->exists($proposal->file_laporan)) {
                Storage::disk('public')->delete($proposal->file_laporan);
            }

            $path = $request->file('file')->store('laporan_kemajuan', 'public');

            $proposal->update([
                'file_laporan' => $path,
                'keterangan' => $request->keterangan,
            ]);

            $this->logActivity("Mengunggah laporan kemajuan proposal ID {$proposal->id}");

            return redirect()->back()->with('success', 'Laporan berhasil diunggah!');
        }

        return redirect()->back()->withErrors(['file' => 'Gagal mengunggah file.']);
    }

    /**
     * ✅ DOWNLOAD LAPORAN KEMAJUAN (dipakai oleh route laporan.kemajuan.download)
     */
    public function downloadLaporan($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $proposal->file_laporan) {
            return redirect()->back()->with('error', 'File laporan belum tersedia.');
        }

        if (! Storage::disk('public')->exists($proposal->file_laporan)) {
            return redirect()->back()->with('error', 'File laporan tidak ditemukan di storage.');
        }

        return Storage::disk('public')->download($proposal->file_laporan);
    }

    /**
     * ✅ PREVIEW LAPORAN KEMAJUAN (dipakai oleh route laporan.kemajuan.preview)
     * Pesan error bila tidak ada file sama seperti downloadLaporan().
     */
    public function preview($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $proposal->file_laporan) {
            return redirect()->back()->with('error', 'File laporan belum tersedia.');
        }

        if (! Storage::disk('public')->exists($proposal->file_laporan)) {
            return redirect()->back()->with('error', 'File laporan tidak ditemukan di storage.');
        }

        return $this->previewStoredFile(
            $proposal->file_laporan,
            basename($proposal->file_laporan),
            'laporan.kemajuan.download',
            ['id' => $id]
        );
    }
}
