<?php

namespace App\Http\Controllers;

use App\Models\DokumenResmi;
use Illuminate\Support\Facades\Storage;

/**
 * Unduh dokumen resmi (panduan / RAB / template / pengumuman) — Fase 6,
 * story 8.
 *
 * Perbaikan dari versi lama (yang mati + bug):
 * - Dulu memakai model AdminDocument padahal dokumen resmi punya model
 *   dan tabel sendiri (DokumenResmi / dokumen_resmi, dengan kolom
 *   kategori + deskripsi yang tidak dimiliki AdminDocument).
 * - Dulu memakai $doc->file padahal kolomnya file_path — kolom file
 *   tidak ada di model mana pun, jadi download selalu error.
 * - Dulu tanpa route sehingga tidak terjangkau; route
 *   dokumen-resmi.download (GET /dokumen-resmi/{id}/download, auth
 *   saja) didaftarkan terpisah.
 *
 * Pola mengikuti AdminDocumentController@download: cek file fisik
 * dulu, lalu unduh dengan nama judul + ekstensi asli. DokumenResmi
 * tidak punya kolom is_visible, jadi tidak ada cek visibilitas —
 * semua dokumen resmi boleh diunduh user yang login.
 */
class DokumenResmiController extends Controller
{
    public function download($id)
    {
        $doc = DokumenResmi::findOrFail($id);

        if (! $doc->file_path || ! Storage::disk('public')->exists($doc->file_path)) {
            return back()->with('error', 'Berkas fisik tidak ditemukan.');
        }

        $ext = pathinfo($doc->file_path, PATHINFO_EXTENSION);

        return Storage::disk('public')->download($doc->file_path, $doc->judul.'.'.$ext);
    }
}
