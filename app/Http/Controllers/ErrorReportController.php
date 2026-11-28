<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\ErrorReport;
use Illuminate\Http\Request;

/**
 * Pelaporan error (Fase 6, story 30).
 *
 *  - Form laporan (create/store): semua user yang login.
 *  - Daftar + detail + ubah status (index/show/update): admin/LPPM
 *    (otorisasi lewat middleware route `role:admin,lppm` yang
 *    didaftarkan koordinator; controller tetap memeriksa role dan
 *    abort 403 bila bukan admin/lppm).
 *  - update() HANYA mengubah status penanganan, bukan isi laporan.
 *
 * Catatan: jangan pakai Auth::id() / auth()->id() untuk membandingkan
 * ID angka (itu mengembalikan USERNAME string) — selalu pakai
 * auth()->user()->id.
 */
class ErrorReportController extends Controller
{
    /**
     * GET /laporan-error/buat — form laporan error (semua role).
     */
    public function create()
    {
        return view('error-report.create');
    }

    /**
     * POST /laporan-error — simpan laporan baru (status awal 'Open').
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
        ], PesanValidasi::pesan([
            'judul' => 'Judul laporan',
            'deskripsi' => 'Deskripsi error',
        ]));

        ErrorReport::create([
            'user_id' => auth()->user()->id,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'status' => 'Open',
        ]);

        return redirect()->route('error-report.create')
            ->with('success', 'Laporan error berhasil dikirim. Terima kasih, laporan Anda akan ditindaklanjuti oleh admin.');
    }

    /**
     * GET /admin/laporan-error — daftar semua laporan (admin/LPPM).
     */
    public function index()
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $laporan = ErrorReport::with('user')->latest()->get();

        return view('admin.error-reports.index', compact('laporan'));
    }

    /**
     * GET /admin/laporan-error/{id} — detail satu laporan (admin/LPPM).
     */
    public function show($id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $laporan = ErrorReport::with('user')->findOrFail($id);

        return view('admin.error-reports.show', compact('laporan'));
    }

    /**
     * PATCH /admin/laporan-error/{id} — ubah status penanganan saja.
     */
    public function update(Request $request, $id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $laporan = ErrorReport::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:Open,Diproses,Selesai',
        ], PesanValidasi::pesan([
            'status' => 'Status laporan',
        ]));

        $laporan->update(['status' => $validated['status']]);

        return redirect()->route('admin.error-report.show', $laporan)
            ->with('success', 'Status laporan berhasil diubah menjadi '.$validated['status'].'.');
    }
}
