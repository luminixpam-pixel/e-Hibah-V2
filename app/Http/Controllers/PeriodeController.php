<?php

namespace App\Http\Controllers;

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
 * OVERLAP (dilaporkan, tidak diubah): CalendarController@updatePeriod
 * juga menulis tabel hibah_periods — tetapi selalu pada baris PERTAMA
 * (HibahPeriod::first()) dan tanpa mengisi kolom nama. Bila LPPM
 * membuat lebih dari satu periode lewat CRUD ini, halaman kalender
 * (monitoring-data) tetap memakai periode pertama saja.
 *
 * Catatan: menghapus periode aman untuk proposal yang memakainya —
 * FK proposals.periode_id memakai nullOnDelete, jadi periode_id
 * proposal terkait menjadi null, bukan error.
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
        ]);

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
        ]);

        $periode->update($validated);

        return redirect()->route('admin.periode.index')->with('success', 'Periode berhasil diperbarui.');
    }

    /**
     * Hapus periode. Proposal yang memakai periode ini tidak ikut
     * terhapus (periode_id-nya menjadi null via FK nullOnDelete).
     */
    public function destroy(HibahPeriod $periode)
    {
        $periode->delete();

        return redirect()->route('admin.periode.index')->with('success', 'Periode berhasil dihapus.');
    }
}
