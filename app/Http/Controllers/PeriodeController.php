<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\HibahPeriod;
use Illuminate\Http\Request;

/**
 * Master data periode hibah (Fase 6, story 15).
 *
 * Pola mengikuti AdminDocumentController / LogbookController: controller
 * flat, validasi di trust boundary, redirect + flash message.
 *
 * Otorisasi ditangani route (middleware role:admin,lppm) — controller
 * ini tidak membandingkan role sendiri.
 *
 * OVERLAP (diperbaiki, keputusan audit 28 Nov 2026): banyak periode
 * aktif DIBOLEHKAN — tidak ada validasi satu-periode-aktif di sini.
 * CalendarController@updatePeriod kini menerima period_id dan
 * mengupdate periode yang dimaksud, bukan selalu baris pertama.
 *
 * Tumpang tindih antar-periode juga TIDAK divalidasi di sini:
 * periode boleh tumpang tindih tanggalnya; pengajuan memakai periode
 * yang tanggalnya mencakup hari ini (ProposalController::activePeriod).
 *
 * Catatan: menghapus periode yang masih dipakai proposal DITOLAK
 * (lihat destroy()) — FK proposals.periode_id memang nullOnDelete,
 * tetapi membiarkan periode_id menjadi null diam-diam akan merusak
 * jejak audit proposal.
 */
class PeriodeController extends Controller
{
    /**
     * Daftar semua periode, yang terbaru dulu.
     */
    public function index()
    {
        $periodes = HibahPeriod::latest()->get();

        return view('admin.periode.index', compact('periodes'));
    }

    /**
     * Form tambah periode.
     */
    public function create()
    {
        return view('admin.periode.create');
    }

    /**
     * Simpan periode baru. Nama wajib; tanggal selesai tidak boleh
     * lebih awal dari tanggal mulai.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], PesanValidasi::pesan([
            'nama' => 'Nama periode',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
        ]));

        HibahPeriod::create($validated);

        return redirect()->route('admin.periode.index')->with('success', 'Periode berhasil ditambahkan.');
    }

    /**
     * Form ubah periode.
     */
    public function edit(HibahPeriod $periode)
    {
        return view('admin.periode.edit', compact('periode'));
    }

    /**
     * Simpan perubahan periode.
     */
    public function update(Request $request, HibahPeriod $periode)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], PesanValidasi::pesan([
            'nama' => 'Nama periode',
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
        ]));

        $periode->update($validated);

        return redirect()->route('admin.periode.index')->with('success', 'Periode berhasil diperbarui.');
    }

    /**
     * Hapus periode. DITOLAK bila masih dipakai proposal (alasan sama
     * seperti SkemaController@destroy).
     */
    public function destroy(HibahPeriod $periode)
    {
        $jumlahProposal = $periode->proposals()->count();

        if ($jumlahProposal > 0) {
            return redirect()->route('admin.periode.index')
                ->with('error', "Periode {$periode->nama} tidak dapat dihapus karena masih dipakai oleh {$jumlahProposal} proposal.");
        }

        $periode->delete();

        return redirect()->route('admin.periode.index')->with('success', 'Periode berhasil dihapus.');
    }
}
