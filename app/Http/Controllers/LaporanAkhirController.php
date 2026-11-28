<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanVersiBerkas;
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
     *  - anggota tim proposal berstatus Diterima (butir 6);
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
            return (int) $proposal->user_id === (int) $user->id
                || $proposal->isAnggotaDiterima((int) $user->id);
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
     * Kepemilikan ditentukan oleh user_id proposal, bukan role saat ini.
     */
    private function isOwner(User $user, Proposal $proposal): bool
    {
        return (int) $proposal->user_id === (int) $user->id;
    }

    /**
     * Apakah user boleh mengunggah laporan akhir proposal ini?
     * (butir 5 + 6): ketua pemilik, admin (atas nama pengaju), atau
     * anggota tim berstatus Diterima. Anggota Menunggu/Ditolak dan
     * pengaju lain tetap tidak boleh.
     */
    private function canUpload(User $user, Proposal $proposal): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $this->isOwner($user, $proposal)
            || $proposal->isAnggotaDiterima((int) $user->id);
    }

    /**
     * Arsipkan berkas aktif lama sebagai riwayat versi sebelum
     * digantikan berkas baru (butir 3): berkas lama TIDAK dihapus.
     * Dipakai juga oleh LaporanKemajuanController dengan pola sama.
     */
    private function arsipkanVersiLama(Proposal $proposal, string $jenis, ?string $filePathLama, int $diunggahOleh): void
    {
        if (! $filePathLama || ! Storage::disk('public')->exists($filePathLama)) {
            return;
        }

        $nomorBerikutnya = (int) LaporanVersiBerkas::where('proposal_id', $proposal->id)
            ->where('jenis', $jenis)
            ->max('nomor_versi') + 1;

        LaporanVersiBerkas::create([
            'proposal_id' => $proposal->id,
            'jenis' => $jenis,
            'file_path' => $filePathLama,
            'nomor_versi' => $nomorBerikutnya,
            'diunggah_oleh' => $diunggahOleh,
        ]);
    }

    /**
     * Menampilkan daftar proposal untuk laporan akhir.
     * Pengaju melihat proposal miliknya + proposal yang ia ikuti
     * sebagai anggota Diterima (butir 6) agar bisa mengunggah;
     * reviewer hanya proposal yang di-plot kepadanya; staf pemantau
     * (admin, lppm, kepala_lppm, verifikator, dekan) melihat semua
     * proposal — selaras dengan LaporanKemajuanController@index.
     * Sebelumnya hanya admin yang diistimewakan dan SEMUA peran lain
     * (termasuk LPPM) diperlakukan seperti pengaju, sehingga LPPM
     * selalu mendapat daftar kosong ("Belum ada data laporan
     * akhir.") dan tidak pernah melihat tombol Verifikasi/Revisi
     * maupun "Riwayat versi" (uji manual ke-5, butir 7).
     */
    public function index()
    {
        $user = Auth::user();
        $query = Proposal::query();

        if ($user->role === 'pengaju') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id)->where('status_konfirmasi', 'Diterima');
                    });
            });
        } elseif ($user->role === 'reviewer') {
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }
        // admin, lppm, kepala_lppm, verifikator, dekan: tanpa filter,
        // memantau semua laporan. Akses unduh/preview per proposal
        // tetap dijaga canAccess() (mis. dekan hanya fakultasnya).

        $proposals = $query->with(['user', 'reviewers'])->latest()->get();

        if ($user->role === 'admin') {
            // Dropdown admin: semua proposal yang sudah Disetujui
            // pendanaannya. file_laporan ikut diambil agar form bisa
            // menandai proposal yang belum punya laporan kemajuan
            // (butir 1: laporan akhir wajib ada kemajuan dulu).
            $myProposals = Proposal::where('status_pendanaan', 'Disetujui')->latest()->get(['id', 'judul', 'file_laporan']);
        } else {
            // Mengambil proposal untuk dropdown select — milik sendiri
            // atau sebagai anggota Diterima, yang sudah Disetujui
            // pendanaannya (status_pendanaan), selaras dengan laporan
            // kemajuan.
            $myProposals = Proposal::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id)->where('status_konfirmasi', 'Diterima');
                    });
            })
                ->where('status_pendanaan', 'Disetujui')
                ->latest()
                ->get(['id', 'judul', 'file_laporan']);
        }

        // Riwayat versi berkas lama per proposal (butir 3), dipakai
        // tabel untuk tombol unduh per versi.
        $versiPerProposal = LaporanVersiBerkas::whereIn('proposal_id', $proposals->pluck('id'))
            ->where('jenis', 'akhir')
            ->orderBy('nomor_versi')
            ->get()
            ->groupBy('proposal_id');

        return view('reviewer.laporan-akhir', compact('proposals', 'myProposals', 'versiPerProposal'));
    }

    /**
     * Proses simpan berkas laporan akhir.
     *
     * Yang boleh mengunggah (butir 5 + 6): ketua pemilik proposal,
     * admin (atas nama pengaju), atau anggota tim berstatus Diterima.
     * Syarat: pendanaan proposal sudah Disetujui, DAN proposal sudah
     * punya laporan kemajuan (butir 1 — berlaku untuk semua, termasuk
     * admin). Unggah ulang mengarsipkan berkas lama sebagai riwayat
     * versi, bukan menghapusnya (butir 3 — PERUBAHAN PERILAKU dari
     * 4fb1ddef yang menghapus berkas lama). Laporan yang baru
     * diunggah berstatus 'Menunggu Verifikasi' (butir 4).
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

        // Hanya ketua pemilik, admin, atau anggota Diterima yang boleh
        // mengunggah laporan akhir. Cek ini HARUS di luar blok try di
        // bawah: abort(403) melempar HttpException yang tertangkap
        // catch (\Exception) bila diletakkan di dalam try, sehingga
        // 403 berubah jadi redirect.
        if (! $this->canUpload(Auth::user(), $proposal)) {
            abort(403, 'Hanya ketua pengusul, anggota tim yang sudah Diterima, atau admin yang boleh mengunggah laporan akhir.');
        }

        // Laporan akhir hanya untuk proposal yang sudah disetujui
        // pendanaannya (status_pendanaan), selaras dengan dropdown
        // di halaman ini dan dengan laporan kemajuan.
        if ($proposal->status_pendanaan !== 'Disetujui') {
            return back()->with('error', 'Laporan akhir hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
        }

        // Butir 1: laporan akhir wajib ada laporan kemajuan dulu —
        // cukup keberadaannya, tidak peduli status verifikasinya.
        if (empty($proposal->file_laporan)) {
            return back()->with('error', 'Laporan akhir belum bisa diunggah karena proposal ini belum memiliki laporan kemajuan. Unggah laporan kemajuan terlebih dahulu.');
        }

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');

                // Arsipkan berkas lama sebagai riwayat versi sebelum
                // digantikan (butir 3) — berkas lama TIDAK dihapus.
                $this->arsipkanVersiLama($proposal, 'akhir', $proposal->file_laporan_akhir, (int) auth()->user()->id);

                // Simpan file
                $path = $file->store('laporan_akhir', 'public');

                // 2. Update dengan pengecekan sukses
                $isSuccess = $proposal->update([
                    'file_laporan_akhir' => $path,
                    'keterangan_akhir' => $request->keterangan,
                    'status_laporan_akhir' => 'Menunggu Verifikasi',
                ]);

                if ($isSuccess) {
                    $pelaku = Auth::user()->role === 'admin' ? 'Admin mengunggah laporan akhir atas nama pengaju untuk proposal' : 'Mengunggah laporan akhir proposal';
                    $this->logActivity("{$pelaku} ID {$proposal->id}");

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
     * Verifikasi laporan akhir oleh LPPM, admin, atau Kepala LPPM
     * (keputusan 28 Nov 2026; sebelumnya hanya LPPM).
     *
     * Pola mengikuti verifikasi Monev (route memakai middleware
     * role:lppm,admin,kepala_lppm; dicek ulang di sini).
     * Keputusan: 'terverifikasi' atau 'revisi'. Hanya laporan
     * berstatus 'Menunggu Verifikasi' yang bisa diverifikasi.
     */
    public function verifikasi(Request $request, $id)
    {
        if (! in_array(auth()->user()->role, ['lppm', 'admin', 'kepala_lppm'], true)) {
            abort(403, 'Hanya LPPM, admin, atau Kepala LPPM yang boleh memverifikasi laporan akhir.');
        }

        $request->validate([
            'keputusan' => 'required|in:terverifikasi,revisi',
        ]);

        $proposal = Proposal::findOrFail($id);

        if (! $proposal->file_laporan_akhir) {
            return back()->with('error', 'Belum ada berkas laporan akhir yang bisa diverifikasi.');
        }

        if ($proposal->status_laporan_akhir !== 'Menunggu Verifikasi') {
            return back()->with('error', 'Hanya laporan berstatus Menunggu Verifikasi yang bisa diverifikasi (status saat ini: '.($proposal->status_laporan_akhir ?? '-').').');
        }

        $statusBaru = $request->keputusan === 'terverifikasi' ? 'Terverifikasi' : 'Revisi';
        $proposal->update(['status_laporan_akhir' => $statusBaru]);

        $this->logActivity(auth()->user()->role_label." memverifikasi laporan akhir proposal ID {$proposal->id} (status: {$statusBaru})");

        return back()->with('success', 'Laporan akhir dinyatakan '.$statusBaru.'.');
    }

    /**
     * Hapus laporan akhir (butir 4).
     *
     * Yang boleh: ketua pemilik proposal atau admin. Hanya bila
     * laporan belum 'Terverifikasi' — laporan yang sudah terverifikasi
     * tidak bisa dihapus. Berkas aktif + seluruh riwayat versinya
     * ikut dihapus; kolom laporan dikosongkan.
     *
     * Dampak tanggungan: hasTanggunganLaporan() (ProposalController)
     * menganggap proposal bertanggungan bila file_laporan_akhir
     * kosong — jadi begitu laporan akhir dihapus, tanggungan
     * pengaju muncul kembali secara otomatis dan ia tidak bisa
     * mengajukan proposal baru sampai mengunggah ulang.
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $user = Auth::user();

        if (! $this->isOwner($user, $proposal) && $user->role !== 'admin') {
            abort(403, 'Hanya ketua pengusul atau admin yang boleh menghapus laporan akhir.');
        }

        if ($proposal->status_laporan_akhir === 'Terverifikasi') {
            return back()->with('error', 'Laporan akhir yang sudah Terverifikasi tidak bisa dihapus.');
        }

        if (! $proposal->file_laporan_akhir) {
            return back()->with('error', 'Belum ada berkas laporan akhir yang bisa dihapus.');
        }

        if (Storage::disk('public')->exists($proposal->file_laporan_akhir)) {
            Storage::disk('public')->delete($proposal->file_laporan_akhir);
        }

        // Riwayat versi laporan ini ikut terhapus beserta berkasnya.
        $versi = LaporanVersiBerkas::where('proposal_id', $proposal->id)->where('jenis', 'akhir')->get();
        foreach ($versi as $v) {
            if (Storage::disk('public')->exists($v->file_path)) {
                Storage::disk('public')->delete($v->file_path);
            }
            $v->delete();
        }

        $proposal->update([
            'file_laporan_akhir' => null,
            'keterangan_akhir' => null,
            'status_laporan_akhir' => null,
        ]);

        $this->logActivity("Menghapus laporan akhir proposal ID {$proposal->id}");

        return back()->with('success', 'Laporan akhir berhasil dihapus. Tanggungan laporan pengaju kembali berlaku sampai laporan akhir diunggah ulang.');
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
