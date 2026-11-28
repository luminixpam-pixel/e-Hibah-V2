<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Logbook;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Logbook pasca-hibah (Fase 5, story 9).
 *
 * Hanya untuk proposal yang DIDANAI: status_pendanaan = 'Disetujui'
 * (keputusan pendanaan; kolom status alur saja tidak cukup — pelajaran
 * bug #10: status alur 'Disetujui' tidak sama dengan didanai).
 * Kedua kolom dicek bersamaan; keputusan() menulis keduanya sekaligus,
 * jadi pada alur normal keduanya selalu sama, dan bila datanya
 * menyimpang arah amannya adalah menolak.
 *
 * Kepemilikan / visibilitas:
 *  - pengaju      : hanya proposal milik sendiri (user_id = id user login).
 *  - lppm, kepala_lppm, admin : semua proposal yang didanai.
 *  - dekan        : hanya proposal di fakultasnya (fakultas_prodi =
 *    fakultas_id user); bila fakultas_id belum diisi, semua proposal
 *    (meniru RoleDashboardController::dekan()).
 *  - reviewer, verifikator : tidak berhak melihat apa pun (403).
 *  - Tulis / ubah / hapus : hanya pengaju pemilik proposal.
 *
 * Catatan Auth::id(): mengembalikan USERNAME, bukan users.id — di sini
 * selalu dipakai auth()->user()->id untuk membandingkan ID angka.
 */
class LogbookController extends Controller
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
     * Daftar proposal Disetujui yang boleh dilihat user login.
     */
    public function index()
    {
        $user = auth()->user();
        $proposals = $this->queryDisetujui($user)->with('user')->latest()->get();

        $jumlah = Logbook::selectRaw('proposal_id, COUNT(*) as jumlah')
            ->whereIn('proposal_id', $proposals->pluck('id'))
            ->groupBy('proposal_id')
            ->pluck('jumlah', 'proposal_id');

        return view('pasca-hibah.logbook-index', compact('proposals', 'jumlah'));
    }

    /**
     * Daftar entri logbook satu proposal + form tambah.
     */
    public function show(Proposal $proposal)
    {
        if (! $this->canView(auth()->user(), $proposal)) {
            abort(403, 'Anda tidak berhak melihat logbook proposal ini.');
        }

        $logbooks = Logbook::where('proposal_id', $proposal->id)->latest()->get();
        $isOwner = $this->isOwner(auth()->user(), $proposal);

        return view('pasca-hibah.logbook-show', compact('proposal', 'logbooks', 'isOwner'));
    }

    /**
     * Simpan entri logbook baru. Hanya pengaju pemilik proposal yang didanai.
     */
    public function store(Request $request, Proposal $proposal)
    {
        if (! $this->isOwner(auth()->user(), $proposal) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya pengaju pemilik proposal yang didanai yang bisa menambah logbook.');
        }

        $validated = $request->validate([
            // Tanggal kegiatan tidak boleh di masa depan.
            'tanggal' => 'required|date|before_or_equal:today',
            'uraian_kegiatan' => 'required|string',
            'file_bukti' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('file_bukti')) {
            $path = $request->file('file_bukti')->store('logbook_bukti', 'public');
        }

        Logbook::create([
            'proposal_id' => $proposal->id,
            'tanggal' => $validated['tanggal'],
            'uraian_kegiatan' => $validated['uraian_kegiatan'],
            'file_bukti' => $path,
        ]);

        $this->logActivity("Menambah entri logbook proposal ID {$proposal->id}");

        return redirect()->route('logbook.show', $proposal)->with('success', 'Entri logbook berhasil ditambahkan.');
    }

    /**
     * Form ubah entri logbook. Hanya pengaju pemilik proposal yang didanai.
     */
    public function edit(Logbook $logbook)
    {
        $proposal = $logbook->proposal;

        if (! $this->isOwner(auth()->user(), $proposal) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya pengaju pemilik proposal yang didanai yang bisa mengubah logbook.');
        }

        return view('pasca-hibah.logbook-edit', compact('logbook', 'proposal'));
    }

    /**
     * Simpan perubahan entri logbook. File lama dihapus bila diganti.
     */
    public function update(Request $request, Logbook $logbook)
    {
        $proposal = $logbook->proposal;

        if (! $this->isOwner(auth()->user(), $proposal) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya pengaju pemilik proposal yang didanai yang bisa mengubah logbook.');
        }

        $validated = $request->validate([
            // Tanggal kegiatan tidak boleh di masa depan.
            'tanggal' => 'required|date|before_or_equal:today',
            'uraian_kegiatan' => 'required|string',
            'file_bukti' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        $data = [
            'tanggal' => $validated['tanggal'],
            'uraian_kegiatan' => $validated['uraian_kegiatan'],
        ];

        if ($request->hasFile('file_bukti')) {
            if ($logbook->file_bukti && Storage::disk('public')->exists($logbook->file_bukti)) {
                Storage::disk('public')->delete($logbook->file_bukti);
            }
            $data['file_bukti'] = $request->file('file_bukti')->store('logbook_bukti', 'public');
        }

        $logbook->update($data);

        $this->logActivity("Mengubah entri logbook ID {$logbook->id} proposal ID {$proposal->id}");

        return redirect()->route('logbook.show', $proposal)->with('success', 'Entri logbook berhasil diperbarui.');
    }

    /**
     * Hapus entri logbook beserta file buktinya.
     */
    public function destroy(Logbook $logbook)
    {
        $proposal = $logbook->proposal;

        if (! $this->isOwner(auth()->user(), $proposal) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya pengaju pemilik proposal yang didanai yang bisa menghapus logbook.');
        }

        if ($logbook->file_bukti && Storage::disk('public')->exists($logbook->file_bukti)) {
            Storage::disk('public')->delete($logbook->file_bukti);
        }

        $logbookId = $logbook->id;
        $proposalId = $proposal->id;
        $logbook->delete();

        $this->logActivity("Menghapus entri logbook ID {$logbookId} proposal ID {$proposalId}");

        return redirect()->route('logbook.show', $proposal)->with('success', 'Entri logbook berhasil dihapus.');
    }

    /**
     * Unduh file bukti entri logbook.
     */
    public function download(Logbook $logbook)
    {
        if (! $this->canView(auth()->user(), $logbook->proposal)) {
            abort(403, 'Anda tidak berhak mengunduh file bukti ini.');
        }

        if (! $logbook->file_bukti || ! Storage::disk('public')->exists($logbook->file_bukti)) {
            return redirect()->back()->with('error', 'File bukti tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($logbook->file_bukti);
    }

    /**
     * Preview file bukti entri logbook.
     * Otorisasi sama seperti download(): cek canView dulu (403).
     */
    public function preview(Logbook $logbook)
    {
        if (! $this->canView(auth()->user(), $logbook->proposal)) {
            abort(403, 'Anda tidak berhak mengunduh file bukti ini.');
        }

        if (! $logbook->file_bukti || ! Storage::disk('public')->exists($logbook->file_bukti)) {
            return redirect()->back()->with('error', 'File bukti tidak ditemukan di server.');
        }

        return $this->previewStoredFile(
            $logbook->file_bukti,
            basename($logbook->file_bukti),
            'logbook.download',
            ['logbook' => $logbook->id]
        );
    }

    /**
     * Query proposal yang didanai yang boleh dilihat user login.
     * Reviewer / verifikator / role lain: 403.
     */
    protected function queryDisetujui($user)
    {
        $query = Proposal::where('status', 'Disetujui')
            ->where('status_pendanaan', 'Disetujui');

        if ($user->role === 'pengaju') {
            $query->where('user_id', $user->id);
        } elseif ($user->role === 'dekan') {
            if ($user->fakultas_id) {
                $query->where('fakultas_prodi', $user->fakultas_id);
            }
        } elseif (! in_array($user->role, ['lppm', 'kepala_lppm', 'admin'], true)) {
            abort(403, 'Anda tidak berhak melihat logbook.');
        }

        return $query;
    }

    /**
     * Apakah proposal ini didanai (keputusan pendanaan Disetujui)?
     * Kedua kolom dicek: status alur saja tidak cukup (bug #10).
     */
    protected function isDidanai(Proposal $proposal): bool
    {
        return $proposal->status === 'Disetujui'
            && $proposal->status_pendanaan === 'Disetujui';
    }

    /**
     * Apakah user boleh melihat logbook proposal ini (baca + unduh)?
     */
    protected function canView($user, Proposal $proposal): bool
    {
        if (! $this->isDidanai($proposal)) {
            return false;
        }

        if ($user->role === 'pengaju') {
            return (int) $proposal->user_id === (int) $user->id;
        }

        if ($user->role === 'dekan') {
            return ! $user->fakultas_id || (int) $proposal->fakultas_prodi === (int) $user->fakultas_id;
        }

        return in_array($user->role, ['lppm', 'kepala_lppm', 'admin'], true);
    }

    /**
     * Apakah user adalah pengaju pemilik proposal ini?
     */
    protected function isOwner($user, Proposal $proposal): bool
    {
        return $user->role === 'pengaju'
            && (int) $proposal->user_id === (int) $user->id;
    }
}
