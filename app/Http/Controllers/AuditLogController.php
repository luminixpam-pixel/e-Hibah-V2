<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;

class AuditLogController extends Controller
{
    /**
     * Halaman daftar activity_logs (baca saja, untuk admin).
     * Urut terbaru dulu, 20 baris per halaman.
     *
     * Pemeriksaan role ada di controller sebagai pertahanan berlapis
     * di samping middleware route role:admin.
     */
    public function index()
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $logs = ActivityLog::with('user')->latest()->paginate(20);

        return view('admin.audit-log.index', compact('logs'));
    }
}
