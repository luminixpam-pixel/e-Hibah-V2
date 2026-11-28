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
        $periodes = HibahPeriod::latest()->get();

        return view('monitoring-data', compact('hibahPeriod', 'periodes'));
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
     * Banyak periode aktif DIBOLEHKAN (keputusan audit 28 Nov 2026):
     * tidak ada validasi satu-periode-aktif di sini maupun di
     * PeriodeController. Form kalender mengirim period_id periode
     * yang dimaksud; update mengenai periode itu saja, bukan selalu
     * periode pertama. Bila period_id tidak dikirim (mis. klien lama
     * / pemanggil lama), perilaku lama dipertahankan: update baris
     * pertama bila ada, atau buat periode baru bila tabel kosong.
     */
    public function updatePeriod(Request $request)
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'period_id' => ['nullable', 'integer', 'exists:hibah_periods,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], array_merge(
            PesanValidasi::pesan([
                'start_date' => 'Tanggal mulai',
                'end_date' => 'Tanggal selesai',
            ]),
            ['period_id.exists' => 'Periode yang dipilih tidak ditemukan.']
        ));

        if (! empty($validated['period_id'])) {
            $periode = HibahPeriod::findOrFail($validated['period_id']);
            $periode->update([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);
        } else {
            $existing = HibahPeriod::first();

            if ($existing) {
                $existing->update([
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            } else {
                HibahPeriod::create([
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            }
        }

        // 🔔 Kirim notifikasi ke semua pengaju
        NotificationController::notifyPeriodUpdated();

        return redirect()
            ->route('monitoring.kalender')
            ->with('success', 'Periode pengajuan hibah berhasil disimpan dan notifikasi telah dikirim.');
    }
}
