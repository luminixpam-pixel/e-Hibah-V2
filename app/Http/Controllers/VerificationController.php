<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\Verification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VerificationController extends Controller
{
    /**
     * Simpan hasil pemeriksaan administrasi (story 22).
     * Hanya proposal berstatus 'Dikirim' yang bisa diperiksa; hasilnya
     * menulis status proposal 'Lolos Administrasi' atau
     * 'Revisi Administrasi' — status yang sebelumnya tidak pernah
     * ditulis kode mana pun, sehingga antrean plotting LPPM selalu kosong.
     */
    public function store(Request $request, $proposalId)
    {
        $proposal = Proposal::findOrFail($proposalId);

        if ($proposal->status !== 'Dikirim') {
            return redirect()->back()->with('error', 'Proposal ini sudah tidak menunggu verifikasi (status: '.$proposal->status.').');
        }

        $request->validate([
            'hasil' => 'required|in:Lolos,Revisi',
            'catatan' => 'nullable|string|max:2000|required_if:hasil,Revisi',
        ], [
            'catatan.required_if' => 'Alasan pengembalian wajib diisi bila proposal dikembalikan untuk revisi.',
        ]);

        $statusBaru = $request->hasil === 'Lolos' ? 'Lolos Administrasi' : 'Revisi Administrasi';

        DB::transaction(function () use ($request, $proposal, $statusBaru) {
            Verification::create([
                'proposal_id' => $proposal->id,
                'verifikator_id' => auth()->user()->id,
                'hasil' => $request->hasil,
                'catatan' => $request->catatan,
            ]);

            $proposal->update(['status' => $statusBaru]);
        });

        // Beri tahu pengaju agar ia tahu proposalnya lolos atau perlu diperbaiki.
        NotificationHelper::send(
            (int) $proposal->user_id,
            $request->hasil === 'Lolos' ? 'Verifikasi Lolos Administrasi' : 'Perlu Revisi Administrasi',
            $request->hasil === 'Lolos'
                ? 'Proposal "'.$proposal->judul.'" lolos verifikasi administrasi dan diteruskan ke LPPM untuk plotting reviewer.'
                : 'Proposal "'.$proposal->judul.'" perlu diperbaiki: '.($request->catatan ?? '-'),
            $request->hasil === 'Lolos' ? 'success' : 'warning',
            $proposal->id
        );

        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'activity' => 'Verifikator '.auth()->user()->name.' '.($request->hasil === 'Lolos' ? 'meloloskan' : 'mengembalikan').' proposal "'.$proposal->judul.'" ('.$statusBaru.')',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->back()->with('success', 'Hasil verifikasi tersimpan: '.$statusBaru.'.');
    }
}
