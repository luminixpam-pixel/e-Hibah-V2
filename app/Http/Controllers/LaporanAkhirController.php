<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LaporanAkhirController extends Controller
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
     * Apakah user boleh mengakses (baca/unduh/preview) laporan akhir
     * proposal ini?
     *  - pengaju pemilik proposal (user_id = id user login);
     *  - reviewer yang di-plot ke proposal ini;
     *  - dekan di fakultas proposal;
     *  - peran pengelola: verifikator, lppm, kepala_lppm, admin.
     */
    private function canAccess(User $user, Proposal $proposal): bool
    {
        if (in_array($user->role, ['verifikator', 'lppm', 'kepala_lppm', 'admin'], true)) {
            return true;
        }

        if ($user->role === 'pengaju') {
            return (int) $proposal->user_id === (int) $user->id;
        }

        if ($user->role === 'reviewer') {
            return $proposal->reviewers()->where('users.id', $user->id)->exists();
        }

        if ($user->role === 'dekan') {
            return ! $user->fakultas_id || (int) $proposal->fakultas_prodi === (int) $user->fakultas_id;
        }

        return false;
    }

    /**
     * Apakah user adalah ketua pengusul (pemilik) proposal ini?
     * Hanya pemilik yang boleh mengunggah laporan akhir. Kepemilikan
     * ditentukan oleh user_id proposal, bukan role saat ini.
     */
    private function isOwner(User $user, Proposal $proposal): bool
    {
        return (int) $proposal->user_id === (int) $user->id;
    }

    /**
     * Menampilkan daftar proposal untuk laporan akhir
     */
    public function index()
    {
        $user = Auth::user();
        $query = Proposal::query();

        if ($user->role === 'admin') {
            $proposals = $query->with(['user', 'reviewers'])->latest()->get();
            // Dropdown admin: semua proposal yang sudah Disetujui
            // pendanaannya (sebelumnya kosong, sehingga form unggah
            // tidak bisa dipakai admin). Definisi "disetujui" adalah
            // status_pendanaan (keputusan pendanaan), selaras dengan
            // laporan kemajuan — bukan status alur.
            $myProposals = Proposal::where('status_pendanaan', 'Disetujui')->latest()->get(['id', 'judul']);
        } else {
            $proposals = $query->where('user_id', $user->id)
                ->with(['reviewers'])
                ->latest()
                ->get();

            // Mengambil proposal milik user untuk dropdown select — hanya
            // yang sudah Disetujui pendanaannya (status_pendanaan),
            // selaras dengan laporan kemajuan.
            $myProposals = Proposal::where('user_id', $user->id)
                ->where('status_pendanaan', 'Disetujui')
                ->latest()
                ->get(['id', 'judul']);
        }

        return view('reviewer.laporan-akhir', compact('proposals', 'myProposals'));
    }

    /**
     * Proses simpan berkas laporan akhir.
     *
     * Hanya ketua pengusul (pemilik proposal) yang boleh mengunggah,
     * dan hanya bila pendanaan proposal sudah Disetujui. Sebelumnya
     * tidak ada cek kepemilikan sama sekali: user login mana pun bisa
     * mengunggah laporan akhir untuk proposal_id mana pun.
     */
    public function store(Request $request)
    {
        // 1. Validasi dengan pesan error kustom agar kita tahu apa yang salah
        $request->validate([
            'proposal_id' => 'required|exists:proposals,id',
            'file' => 'required|mimes:pdf|max:51200',
            'keterangan' => 'nullable|string|max:2000',
        ], [
            'file.mimes' => 'File harus berupa PDF.',
            'file.max' => 'File terlalu besar (Maks 50MB).',
        ]);

        $proposal = Proposal::find($request->proposal_id);

        if (! $proposal) {
            return back()->with('error', 'ID Proposal tidak ditemukan di database.');
        }

        // Hanya pemilik proposal yang boleh mengunggah laporan akhir.
        // Cek ini HARUS di luar blok try di bawah: abort(403) melempar
        // HttpException yang tertangkap catch (\Exception) bila
        // diletakkan di dalam try, sehingga 403 berubah jadi redirect.
        if (! $this->isOwner(Auth::user(), $proposal)) {
            abort(403, 'Hanya ketua pengusul pemilik proposal yang boleh mengunggah laporan akhir.');
        }

        // Laporan akhir hanya untuk proposal yang sudah disetujui
        // pendanaannya (status_pendanaan), selaras dengan dropdown
        // di halaman ini dan dengan laporan kemajuan.
        if ($proposal->status_pendanaan !== 'Disetujui') {
            return back()->with('error', 'Laporan akhir hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
        }

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // Hapus file lama bila ada (unggah ulang menggantikan
                // berkas sebelumnya, selaras dengan laporan kemajuan).
                if ($proposal->file_laporan_akhir && Storage::disk('public')->exists($proposal->file_laporan_akhir)) {
                    Storage::disk('public')->delete($proposal->file_laporan_akhir);
                }

                // Simpan file
                $path = $file->store('laporan_akhir', 'public');

                // 2. Update dengan pengecekan sukses
                $isSuccess = $proposal->update([
                    'file_laporan_akhir' => $path,
                    'keterangan_akhir' => $request->keterangan,
                ]);

                if ($isSuccess) {
                    $this->logActivity("Mengunggah laporan akhir proposal ID {$proposal->id}");

                    return back()->with('success', 'Laporan akhir berhasil diunggah!');
                } else {
                    return back()->with('error', 'Database menolak update. Cek $fillable di Model Proposal.');
                }
            }
        } catch (\Exception $e) {
            // Jangan bocorkan pesan exception ke user (dulu
            // $e->getMessage() ditampilkan mentah di halaman).
            Log::error('Gagal mengunggah laporan akhir: '.$e->getMessage(), [
                'proposal_id' => $request->proposal_id,
                'user_id' => auth()->user()->id,
            ]);

            return back()->with('error', 'Terjadi kesalahan sistem saat mengunggah laporan akhir.');
        }

        return back()->with('error', 'Gagal: File tidak terbaca oleh server.');
    }

    /**
     * Fungsi Download Berkas Laporan Akhir.
     * Hanya yang berhak atas proposal ini (pemilik, reviewer terkait,
     * dekan fakultasnya, atau peran pengelola) — sebelumnya tanpa
     * cek akses sama sekali.
     */
    public function download($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $this->canAccess(Auth::user(), $proposal)) {
            abort(403, 'Anda tidak berhak mengunduh laporan akhir proposal ini.');
        }

        // Cek keberadaan file di kolom file_laporan_akhir
        if (! $proposal->file_laporan_akhir || ! Storage::disk('public')->exists($proposal->file_laporan_akhir)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $extension = pathinfo($proposal->file_laporan_akhir, PATHINFO_EXTENSION);
        $cleanTitle = str_replace([' ', '/', '\\'], '_', $proposal->judul);
        $downloadName = 'Laporan_Akhir_'.$cleanTitle.'.'.$extension;

        return Storage::disk('public')->download($proposal->file_laporan_akhir, $downloadName);
    }

    /**
     * Preview Berkas Laporan Akhir (inline bila PDF/gambar, unduh bila bukan).
     * Nama tampil sama seperti download(). Otorisasi sama seperti
     * download() — sebelumnya tanpa cek akses sama sekali.
     */
    public function preview($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $this->canAccess(Auth::user(), $proposal)) {
            abort(403, 'Anda tidak berhak melihat laporan akhir proposal ini.');
        }

        // Cek keberadaan file di kolom file_laporan_akhir
        if (! $proposal->file_laporan_akhir || ! Storage::disk('public')->exists($proposal->file_laporan_akhir)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $extension = pathinfo($proposal->file_laporan_akhir, PATHINFO_EXTENSION);
        $cleanTitle = str_replace([' ', '/', '\\'], '_', $proposal->judul);
        $displayName = 'Laporan_Akhir_'.$cleanTitle.'.'.$extension;

        return $this->previewStoredFile(
            $proposal->file_laporan_akhir,
            $displayName,
            'laporan.akhir.download',
            ['id' => $id]
        );
    }
}
