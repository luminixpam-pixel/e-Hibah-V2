<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\LaporanVersiBerkas;
use App\Models\Proposal;
use App\Models\User;
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

    /**
     * Apakah user boleh mengakses (baca/unduh/preview) laporan kemajuan
     * proposal ini? Sama seperti laporan akhir: pemilik, anggota tim
     * Diterima (butir 6), reviewer yang di-plot, dekan fakultasnya,
     * atau peran pengelola.
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
     * Apakah user boleh mengunggah laporan kemajuan proposal ini?
     * (butir 6): ketua pemilik atau anggota tim berstatus Diterima.
     * Admin tidak mengunggah laporan kemajuan (hanya laporan akhir,
     * butir 5).
     */
    private function canUpload(User $user, Proposal $proposal): bool
    {
        return (int) $proposal->user_id === (int) $user->id
            || $proposal->isAnggotaDiterima((int) $user->id);
    }

    /**
     * Arsipkan berkas aktif lama sebagai riwayat versi sebelum
     * digantikan berkas baru (butir 3): berkas lama TIDAK dihapus.
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

    public function index(Request $request)
    {
        $search = $request->input('search');
        $user = Auth::user();

        // 1. Query Dasar untuk Tabel
        $query = Proposal::with(['user', 'reviewers']);

        // 2. Filter Role (Security Filter). Pengaju melihat proposal
        // miliknya + proposal yang ia ikuti sebagai anggota Diterima
        // (butir 6) agar bisa mengunggah laporan kemajuan.
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
            $myProposals = Proposal::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id)->where('status_konfirmasi', 'Diterima');
                    });
            })
                ->where('status_pendanaan', 'Disetujui')
                ->latest()
                ->get(['id', 'judul']);
        }

        // Riwayat versi berkas lama per proposal (butir 3), dipakai
        // tabel untuk tombol unduh per versi.
        $versiPerProposal = LaporanVersiBerkas::whereIn('proposal_id', $proposals->getCollection()->pluck('id'))
            ->where('jenis', 'kemajuan')
            ->orderBy('nomor_versi')
            ->get()
            ->groupBy('proposal_id');

        return view('reviewer.laporan-kemajuan', [
            'proposals' => $proposals,
            'myProposals' => $myProposals,
            'versiPerProposal' => $versiPerProposal,
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
            // atau user adalah anggota tim Diterima (butir 6).
            // Bandingkan user_id angka dengan auth()->user()->id —
            // JANGAN pakai Auth::id() / username (Auth::id() di aplikasi
            // ini mengembalikan username, bukan users.id).
            $proposal = Proposal::where('id', $request->proposal_id)->first();

            if (! $proposal || ! $this->canUpload(Auth::user(), $proposal)) {
                return redirect()->back()->with('error', 'Proposal tidak valid / bukan milik Anda.');
            }

            // Laporan kemajuan hanya untuk proposal yang sudah disetujui
            // pendanaannya (sama seperti dropdown di halaman ini):
            // cek status_pendanaan, bukan status alur.
            if ($proposal->status_pendanaan !== 'Disetujui') {
                return redirect()->back()->with('error', 'Laporan kemajuan hanya dapat diunggah untuk proposal yang sudah disetujui pendanaannya.');
            }

            // Arsipkan berkas lama sebagai riwayat versi sebelum
            // digantikan (butir 3) — berkas lama TIDAK dihapus.
            $this->arsipkanVersiLama($proposal, 'kemajuan', $proposal->file_laporan, (int) Auth::user()->id);

            $path = $request->file('file')->store('laporan_kemajuan', 'public');

            $proposal->update([
                'file_laporan' => $path,
                'keterangan' => $request->keterangan,
                'status_laporan_kemajuan' => 'Menunggu Verifikasi',
            ]);

            $this->logActivity("Mengunggah laporan kemajuan proposal ID {$proposal->id}");

            return redirect()->back()->with('success', 'Laporan berhasil diunggah!');
        }

        return redirect()->back()->withErrors(['file' => 'Gagal mengunggah file.']);
    }

    /**
     * Verifikasi laporan kemajuan oleh LPPM (butir 4).
     * Pola mengikuti verifikasi Monev: LPPM yang memverifikasi
     * (route memakai middleware role:lppm; dicek ulang di sini).
     * Keputusan: 'terverifikasi' atau 'revisi'. Hanya laporan
     * berstatus 'Menunggu Verifikasi' yang bisa diverifikasi.
     */
    public function verifikasi(Request $request, $id)
    {
        if (auth()->user()->role !== 'lppm') {
            abort(403, 'Hanya LPPM yang boleh memverifikasi laporan kemajuan.');
        }

        $request->validate([
            'keputusan' => 'required|in:terverifikasi,revisi',
        ]);

        $proposal = Proposal::findOrFail($id);

        if (! $proposal->file_laporan) {
            return back()->with('error', 'Belum ada berkas laporan kemajuan yang bisa diverifikasi.');
        }

        if ($proposal->status_laporan_kemajuan !== 'Menunggu Verifikasi') {
            return back()->with('error', 'Hanya laporan berstatus Menunggu Verifikasi yang bisa diverifikasi (status saat ini: '.($proposal->status_laporan_kemajuan ?? '-').').');
        }

        $statusBaru = $request->keputusan === 'terverifikasi' ? 'Terverifikasi' : 'Revisi';
        $proposal->update(['status_laporan_kemajuan' => $statusBaru]);

        $this->logActivity("LPPM memverifikasi laporan kemajuan proposal ID {$proposal->id} (status: {$statusBaru})");

        return back()->with('success', 'Laporan kemajuan dinyatakan '.$statusBaru.'.');
    }

    /**
     * Hapus laporan kemajuan (butir 4).
     *
     * Yang boleh: ketua pemilik proposal atau admin. Hanya bila
     * laporan belum 'Terverifikasi'. Berkas aktif + seluruh riwayat
     * versinya ikut dihapus; kolom laporan dikosongkan.
     *
     * Pengaman konsistensi butir 1: laporan kemajuan tidak bisa
     * dihapus bila laporan akhir sudah diunggah — kalau tidak,
     * proposal akan punya laporan akhir tanpa laporan kemajuan.
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $user = Auth::user();

        if ((int) $proposal->user_id !== (int) $user->id && $user->role !== 'admin') {
            abort(403, 'Hanya ketua pengusul atau admin yang boleh menghapus laporan kemajuan.');
        }

        if ($proposal->status_laporan_kemajuan === 'Terverifikasi') {
            return back()->with('error', 'Laporan kemajuan yang sudah Terverifikasi tidak bisa dihapus.');
        }

        if ($proposal->file_laporan_akhir) {
            return back()->with('error', 'Laporan kemajuan tidak bisa dihapus karena laporan akhir sudah diunggah. Hapus laporan akhir terlebih dahulu.');
        }

        if (! $proposal->file_laporan) {
            return back()->with('error', 'Belum ada berkas laporan kemajuan yang bisa dihapus.');
        }

        if (Storage::disk('public')->exists($proposal->file_laporan)) {
            Storage::disk('public')->delete($proposal->file_laporan);
        }

        // Riwayat versi laporan ini ikut terhapus beserta berkasnya.
        $versi = LaporanVersiBerkas::where('proposal_id', $proposal->id)->where('jenis', 'kemajuan')->get();
        foreach ($versi as $v) {
            if (Storage::disk('public')->exists($v->file_path)) {
                Storage::disk('public')->delete($v->file_path);
            }
            $v->delete();
        }

        $proposal->update([
            'file_laporan' => null,
            'keterangan' => null,
            'status_laporan_kemajuan' => null,
        ]);

        $this->logActivity("Menghapus laporan kemajuan proposal ID {$proposal->id}");

        return back()->with('success', 'Laporan kemajuan berhasil dihapus.');
    }

    /**
     * Unduh satu berkas versi lama laporan (kemajuan/akhir, butir 3).
     * Aturan aksesnya sama dengan berkas aktif jenis tersebut
     * (canAccess): hanya yang berhak atas proposal ini.
     */
    public function downloadVersi(LaporanVersiBerkas $versi)
    {
        $proposal = $versi->proposal;

        if (! $proposal || ! $this->canAccess(Auth::user(), $proposal)) {
            abort(403, 'Anda tidak berhak mengunduh berkas versi ini.');
        }

        if (! Storage::disk('public')->exists($versi->file_path)) {
            return redirect()->back()->with('error', 'File versi tidak ditemukan di server.');
        }

        $extension = pathinfo($versi->file_path, PATHINFO_EXTENSION);
        $cleanTitle = str_replace([' ', '/', '\\'], '_', $proposal->judul);
        $label = $versi->jenis === 'akhir' ? 'Laporan_Akhir' : 'Laporan_Kemajuan';
        $downloadName = $label.'_v'.$versi->nomor_versi.'_'.$cleanTitle.'.'.$extension;

        return Storage::disk('public')->download($versi->file_path, $downloadName);
    }

    /**
     * ✅ DOWNLOAD LAPORAN KEMAJUAN (dipakai oleh route laporan.kemajuan.download)
     * Hanya yang berhak atas proposal ini — sebelumnya tanpa cek akses
     * sama sekali (siapa pun yang login bisa mengunduh laporan siapa pun).
     */
    public function downloadLaporan($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $this->canAccess(Auth::user(), $proposal)) {
            abort(403, 'Anda tidak berhak mengunduh laporan kemajuan proposal ini.');
        }

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
     * Otorisasi sama seperti downloadLaporan() — sebelumnya tanpa cek.
     */
    public function preview($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (! $this->canAccess(Auth::user(), $proposal)) {
            abort(403, 'Anda tidak berhak melihat laporan kemajuan proposal ini.');
        }

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
