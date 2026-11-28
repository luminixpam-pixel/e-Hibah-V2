<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\ActivityLog;
use App\Models\Proposal;
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

        // Undangan tidak lagi berlaku bila proposalnya sudah diputuskan
        // (Disetujui/Ditolak/Selesai): anggota baru tidak bisa masuk
        // ke tim proposal yang sudah berjalan.
        if ($member->proposal && in_array($member->proposal->status, ['Disetujui', 'Ditolak', 'Selesai'], true)) {
            return redirect()->route('anggota.undangan')->with('error', 'Undangan ini sudah tidak berlaku karena proposal sudah diputuskan (status: '.$member->proposal->status.').');
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
                auth()->user()->name.' '.$jawaban.' undangan menjadi anggota tim proposal "'.($proposal->judul ?? '-').'".',
                'info',
                $proposal->id
            );
        }

        return redirect()->route('anggota.undangan')->with('success', $request->aksi === 'setuju'
            ? 'Terima kasih! Anda tercatat sebagai anggota tim proposal ini.'
            : 'Undangan ditolak. Ketua pengusul telah diberi tahu.');
    }

    /**
     * Detail proposal yang diikuti sebagai anggota tim (read-only).
     * Memakai tampilan detail yang sama dengan tinjau proposal, tanpa
     * formulir verifikasi (formulir itu hanya dirender untuk role
     * verifikator, jadi anggota tidak pernah melihatnya).
     *
     * Boleh dibuka oleh ketua pengusul dan oleh anggota tim yang
     * status keanggotaannya Menunggu/Diterima. Sesuai alur UML,
     * anggota meninjau detail dulu baru menjawab undangan, jadi
     * status Menunggu tetap boleh melihat. Yang Ditolak sudah bukan
     * anggota tim lagi dan tidak boleh melihat.
     */
    public function show($id)
    {
        $userId = (int) auth()->user()->id;

        $proposal = Proposal::with(['user', 'reviewers', 'reviews.reviewer', 'verifications.verifikator'])
            ->findOrFail($id);

        $isKetua = (int) $proposal->user_id === $userId;

        $keanggotaan = $isKetua ? null : ProposalMember::where('proposal_id', $proposal->id)
            ->where('user_id', $userId)
            ->whereIn('status_konfirmasi', ['Menunggu', 'Diterima'])
            ->first();

        if (! $isKetua && ! $keanggotaan) {
            abort(403, 'Anda bukan anggota tim proposal ini.');
        }

        // Riwayat keputusan pendanaan dari log aktivitas admin (pola yang
        // sama dengan ProposalController@tinjau).
        $riwayatKeputusan = ActivityLog::where('activity', 'like', '%proposal ID '.$proposal->id.' menjadi:%')
            ->with('user')
            ->latest()
            ->get();

        return view('proposal.tinjau-proposal', compact('proposal', 'riwayatKeputusan'));
    }
}
