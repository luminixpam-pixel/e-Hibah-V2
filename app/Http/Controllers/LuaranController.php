<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Luaran;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Luaran pasca-hibah (Fase 5, story 9).
 *
 * Hanya untuk proposal yang DIDANAI: status_pendanaan = 'Disetujui'
 * (keputusan pendanaan; kolom status alur saja tidak cukup — pelajaran
 * bug #10). Kedua kolom dicek bersamaan; keputusan() menulis keduanya
 * sekaligus, jadi pada alur normal keduanya selalu sama, dan bila
 * datanya menyimpang arah amannya adalah menolak.
 *
 * Kepemilikan / visibilitas (sama seperti LogbookController):
 *  - pengaju      : hanya proposal milik sendiri (user_id = id user login)
 *    atau yang ia ikuti sebagai anggota Diterima (butir 6).
 *  - lppm, kepala_lppm, admin : semua proposal yang didanai.
 *  - dekan        : hanya proposal di fakultasnya (fakultas_prodi =
 *    fakultas_id user); bila fakultas_id belum diisi, semua proposal
 *    (meniru RoleDashboardController::dekan()).
 *  - reviewer, verifikator : tidak berhak melihat apa pun (403).
 *  - Tulis : ketua pemilik atau anggota Diterima (butir 6).
 *  - Ubah / hapus : ketua boleh mengelola SEMUA luaran proposalnya;
 *    anggota Diterima hanya luaran yang dibuatnya sendiri (kolom
 *    created_by; luaran lama tanpa created_by hanya bisa dikelola
 *    ketua).
 *
 * Luaran berstatus Published / Granted wajib melampirkan file bukti —
 * klaim terbit/terdaftar tanpa berkas tidak bisa disimpan.
 *
 * Catatan Auth::id(): mengembalikan USERNAME, bukan users.id — di sini
 * selalu dipakai auth()->user()->id untuk membandingkan ID angka.
 */
class LuaranController extends Controller
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

        $jumlah = Luaran::selectRaw('proposal_id, COUNT(*) as jumlah')
            ->whereIn('proposal_id', $proposals->pluck('id'))
            ->groupBy('proposal_id')
            ->pluck('jumlah', 'proposal_id');

        return view('pasca-hibah.luaran-index', compact('proposals', 'jumlah'));
    }

    /**
     * Daftar luaran satu proposal + form tambah.
     */
    public function show(Proposal $proposal)
    {
        if (! $this->canView(auth()->user(), $proposal)) {
            abort(403, 'Anda tidak berhak melihat luaran proposal ini.');
        }

        $luarans = Luaran::where('proposal_id', $proposal->id)->with('pembuat')->latest()->get();
        $isOwner = $this->isOwner(auth()->user(), $proposal);
        // Form tambah tampil untuk ketua maupun anggota Diterima.
        $canWrite = $this->canWrite(auth()->user(), $proposal);

        return view('pasca-hibah.luaran-show', compact('proposal', 'luarans', 'isOwner', 'canWrite'));
    }

    /**
     * Simpan luaran baru. Ketua pemilik atau anggota Diterima,
     * untuk proposal yang didanai.
     */
    public function store(Request $request, Proposal $proposal)
    {
        if (! $this->canWrite(auth()->user(), $proposal) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya ketua pengusul atau anggota tim yang sudah Diterima pada proposal yang didanai yang bisa menambah luaran.');
        }

        $validated = $request->validate([
            'jenis' => 'required|in:Jurnal,Prosiding,HKI,Paten,Buku',
            'judul' => 'required|string|max:255',
            'status' => 'required|in:Draft,Submitted,Published,Granted',
            'file' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        // Luaran yang diklaim sudah terbit/terdaftar wajib ada buktinya.
        if (in_array($validated['status'], ['Published', 'Granted'], true) && ! $request->hasFile('file')) {
            return back()->withErrors(['file' => 'Luaran berstatus '.$validated['status'].' wajib melampirkan file bukti.'])->withInput();
        }

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('luaran', 'public');
        }

        Luaran::create([
            'proposal_id' => $proposal->id,
            'jenis' => $validated['jenis'],
            'judul' => $validated['judul'],
            'status' => $validated['status'],
            'file' => $path,
            'created_by' => auth()->user()->id,
        ]);

        $this->logActivity("Menambah luaran proposal ID {$proposal->id}");

        return redirect()->route('luaran.show', $proposal)->with('success', 'Luaran berhasil ditambahkan.');
    }

    /**
     * Form ubah luaran. Ketua boleh mengubah semua luaran; anggota
     * Diterima hanya luaran buatannya sendiri.
     */
    public function edit(Luaran $luaran)
    {
        $proposal = $luaran->proposal;

        if (! $this->canManageEntry(auth()->user(), $proposal, $luaran) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya ketua pengusul atau pembuat luaran ini yang bisa mengubah luaran.');
        }

        return view('pasca-hibah.luaran-edit', compact('luaran', 'proposal'));
    }

    /**
     * Simpan perubahan luaran. File lama dihapus bila diganti.
     */
    public function update(Request $request, Luaran $luaran)
    {
        $proposal = $luaran->proposal;

        if (! $this->canManageEntry(auth()->user(), $proposal, $luaran) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya ketua pengusul atau pembuat luaran ini yang bisa mengubah luaran.');
        }

        $validated = $request->validate([
            'jenis' => 'required|in:Jurnal,Prosiding,HKI,Paten,Buku',
            'judul' => 'required|string|max:255',
            'status' => 'required|in:Draft,Submitted,Published,Granted',
            'file' => 'nullable|file|mimes:pdf|max:2048',
        ]);

        // Naik status ke Published/Granted wajib ada file bukti —
        // file baru, atau file yang sudah tersimpan sebelumnya.
        if (in_array($validated['status'], ['Published', 'Granted'], true)
            && ! $request->hasFile('file')
            && ! $luaran->file
        ) {
            return back()->withErrors(['file' => 'Luaran berstatus '.$validated['status'].' wajib melampirkan file bukti.'])->withInput();
        }

        $data = [
            'jenis' => $validated['jenis'],
            'judul' => $validated['judul'],
            'status' => $validated['status'],
        ];

        if ($request->hasFile('file')) {
            if ($luaran->file && Storage::disk('public')->exists($luaran->file)) {
                Storage::disk('public')->delete($luaran->file);
            }
            $data['file'] = $request->file('file')->store('luaran', 'public');
        }

        $luaran->update($data);

        $this->logActivity("Mengubah luaran ID {$luaran->id} proposal ID {$proposal->id}");

        return redirect()->route('luaran.show', $proposal)->with('success', 'Luaran berhasil diperbarui.');
    }

    /**
     * Hapus luaran beserta filenya.
     */
    public function destroy(Luaran $luaran)
    {
        $proposal = $luaran->proposal;

        if (! $this->canManageEntry(auth()->user(), $proposal, $luaran) || ! $this->isDidanai($proposal)) {
            abort(403, 'Hanya ketua pengusul atau pembuat luaran ini yang bisa menghapus luaran.');
        }

        if ($luaran->file && Storage::disk('public')->exists($luaran->file)) {
            Storage::disk('public')->delete($luaran->file);
        }

        $luaranId = $luaran->id;
        $proposalId = $proposal->id;
        $luaran->delete();

        $this->logActivity("Menghapus luaran ID {$luaranId} proposal ID {$proposalId}");

        return redirect()->route('luaran.show', $proposal)->with('success', 'Luaran berhasil dihapus.');
    }

    /**
     * Unduh file luaran.
     */
    public function download(Luaran $luaran)
    {
        if (! $this->canView(auth()->user(), $luaran->proposal)) {
            abort(403, 'Anda tidak berhak mengunduh file luaran ini.');
        }

        if (! $luaran->file || ! Storage::disk('public')->exists($luaran->file)) {
            return redirect()->back()->with('error', 'File luaran tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($luaran->file);
    }

    /**
     * Preview file luaran.
     * Otorisasi sama seperti download(): cek canView dulu (403).
     */
    public function preview(Luaran $luaran)
    {
        if (! $this->canView(auth()->user(), $luaran->proposal)) {
            abort(403, 'Anda tidak berhak mengunduh file luaran ini.');
        }

        if (! $luaran->file || ! Storage::disk('public')->exists($luaran->file)) {
            return redirect()->back()->with('error', 'File luaran tidak ditemukan di server.');
        }

        return $this->previewStoredFile(
            $luaran->file,
            basename($luaran->file),
            'luaran.download',
            ['luaran' => $luaran->id]
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
            // Proposal milik sendiri atau yang diikuti sebagai anggota
            // Diterima (butir 6).
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id)->where('status_konfirmasi', 'Diterima');
                    });
            });
        } elseif ($user->role === 'dekan') {
            if ($user->fakultas_id) {
                $query->where('fakultas_prodi', $user->fakultas_id);
            }
        } elseif (! in_array($user->role, ['lppm', 'kepala_lppm', 'admin'], true)) {
            abort(403, 'Anda tidak berhak melihat luaran.');
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
     * Apakah user boleh melihat luaran proposal ini (baca + unduh)?
     */
    protected function canView($user, Proposal $proposal): bool
    {
        if (! $this->isDidanai($proposal)) {
            return false;
        }

        if ($user->role === 'pengaju') {
            return (int) $proposal->user_id === (int) $user->id
                || $proposal->isAnggotaDiterima((int) $user->id);
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

    /**
     * Apakah user boleh menambah luaran (tulis) untuk proposal ini?
     * Ketua pemilik atau anggota tim berstatus Diterima (butir 6).
     */
    protected function canWrite($user, Proposal $proposal): bool
    {
        return $this->isOwner($user, $proposal)
            || $proposal->isAnggotaDiterima((int) $user->id);
    }

    /**
     * Apakah user boleh mengubah/menghapus satu luaran ini?
     * Ketua boleh mengelola SEMUA luaran proposalnya; anggota
     * Diterima hanya luaran yang dibuatnya sendiri (created_by).
     * Luaran lama tanpa created_by hanya bisa dikelola ketua.
     */
    protected function canManageEntry($user, Proposal $proposal, Luaran $luaran): bool
    {
        if ($this->isOwner($user, $proposal)) {
            return true;
        }

        return $proposal->isAnggotaDiterima((int) $user->id)
            && $luaran->created_by !== null
            && (int) $luaran->created_by === (int) $user->id;
    }
}
