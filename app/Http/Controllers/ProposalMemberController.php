<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\ProposalMember;
use Illuminate\Http\Request;

class ProposalMemberController extends Controller
{
    /**
     * Daftar undangan keanggotaan untuk user yang login.
     * Undangan yang masih 'Menunggu' tampil di atas, lalu riwayat
     * yang sudah dijawab.
     */
    public function index()
    {
        // auth()->id() = username (lihat User::getAuthIdentifierName),
        // bukan users.id — pakai auth()->user()->id agar undangan ketemu.
        $userId = (int) auth()->user()->id;

        $undanganMenunggu = ProposalMember::with(['proposal.skema', 'proposal.user'])
            ->where('user_id', $userId)
            ->where('status_konfirmasi', 'Menunggu')
            ->latest()
            ->get();

        $riwayat = ProposalMember::with(['proposal.skema', 'proposal.user'])
            ->where('user_id', $userId)
            ->where('status_konfirmasi', '!=', 'Menunggu')
            ->latest()
            ->get();

        return view('proposal.undangan-anggota', compact('undanganMenunggu', 'riwayat'));
    }

    /**
     * Jawab undangan keanggotaan (Setuju / Tolak).
     * Hanya anggota yang diundang yang boleh menjawab undangannya sendiri.
     */
    public function confirm(Request $request, $id)
    {
        $member = ProposalMember::with('proposal')->findOrFail($id);

        if ((int) $member->user_id !== (int) auth()->user()->id) {
            abort(403, 'Undangan ini bukan untuk Anda.');
        }

        $request->validate([
            'aksi' => 'required|in:setuju,tolak',
        ]);

        if ($member->status_konfirmasi !== 'Menunggu') {
            return redirect()->route('anggota.undangan')->with('error', 'Undangan ini sudah Anda jawab sebelumnya.');
        }

        $member->update([
            'status_konfirmasi' => $request->aksi === 'setuju' ? 'Diterima' : 'Ditolak',
        ]);

        // Beri tahu ketua pengusul atas jawaban anggota.
        $proposal = $member->proposal;
        if ($proposal && $proposal->user_id) {
            $jawaban = $request->aksi === 'setuju' ? 'menerima' : 'menolak';
            NotificationHelper::send(
                (int) $proposal->user_id,
                'Jawaban Undangan Anggota Tim',
                auth()->user()->name . ' ' . $jawaban . ' undangan menjadi anggota tim proposal "' . ($proposal->judul ?? '-') . '".',
                'info',
                $proposal->id
            );
        }

        return redirect()->route('anggota.undangan')->with('success', $request->aksi === 'setuju'
            ? 'Terima kasih! Anda tercatat sebagai anggota tim proposal ini.'
            : 'Undangan ditolak. Ketua pengusul telah diberi tahu.');
    }
}
