<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\HibahPeriod;
use Illuminate\Http\Request;

// import class NotificationController

class CalendarController extends Controller
{
    /**
     * Menampilkan halaman kalender + periode hibah dari DB.
     */
    public function index()
    {
        $hibahPeriod = HibahPeriod::first();

        return view('monitoring-data', compact('hibahPeriod'));
    }

    /**
     * Hanya ADMIN yang boleh menyimpan / update periode hibah.
     * Juga kirim notifikasi ke semua pengaju.
     *
     * Pemeriksaan role ada di controller (bukan hanya middleware
     * route role:admin) sebagai pertahanan berlapis — docblock lama
     * mengklaim "hanya admin" tetapi method ini sebelumnya tidak
     * memeriksa role sama sekali.
     *
     * CATATAN (tidak diubah): selalu menulis baris PERTAMA
     * (HibahPeriod::first()). Bila sudah ada lebih dari satu
     * periode, update dari kalender tetap mengenai periode pertama
     * saja — lihat PeriodeController untuk keputusan satu vs banyak
     * periode aktif yang masih terbuka.
     */
    public function updatePeriod(Request $request)
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], PesanValidasi::pesan([
            'start_date' => 'Tanggal mulai',
            'end_date' => 'Tanggal selesai',
        ]));

        $existing = HibahPeriod::first();

        if ($existing) {
            $existing->update($validated);
        } else {
            HibahPeriod::create($validated);
        }

        // 🔔 Kirim notifikasi ke semua pengaju
        NotificationController::notifyPeriodUpdated();

        return redirect()
            ->route('monitoring.kalender')
            ->with('success', 'Periode pengajuan hibah berhasil disimpan dan notifikasi telah dikirim.');
    }
}
