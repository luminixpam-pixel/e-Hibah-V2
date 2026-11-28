<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Pengumuman hibah (Fase 6, story 18).
 *
 * CRUD oleh LPPM + admin (otorisasi lewat middleware route
 * `role:admin,lppm` yang didaftarkan koordinator; controller tetap
 * memeriksa role dan abort 403 bila bukan admin/lppm).
 * Pengumuman yang terbit tampil di landing page (lihat
 * resources/views/landing/index.blade.php).
 *
 * Catatan: jangan pakai Auth::id() / auth()->id() untuk membandingkan
 * ID angka (itu mengembalikan USERNAME string) — selalu pakai
 * auth()->user()->id.
 */
class PengumumanController extends Controller
{
    /**
     * GET /admin/pengumuman — daftar semua pengumuman.
     */
    public function index()
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $pengumuman = Announcement::with('creator')->latest('published_at')->latest()->get();

        return view('admin.pengumuman.index', compact('pengumuman'));
    }

    /**
     * GET /admin/pengumuman/buat — form tambah pengumuman.
     */
    public function create()
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        return view('admin.pengumuman.create');
    }

    /**
     * POST /admin/pengumuman — simpan pengumuman baru.
     * published_at diisi otomatis now() bila kosong.
     */
    public function store(Request $request)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|in:eksternal,internal',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'published_at' => 'nullable|date',
        ], array_merge(PesanValidasi::pesan([
            'judul' => 'Judul',
            'isi' => 'Isi pengumuman',
            'kategori' => 'Kategori',
            'file' => 'File lampiran',
            'published_at' => 'Tanggal tayang',
        ]), [
            'file.max' => 'Ukuran file lampiran maksimal 2 MB.',
        ]));

        $data = [
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kategori' => $validated['kategori'],
            'created_by' => auth()->user()->id,
            'published_at' => $validated['published_at'] ?? now(),
        ];

        if ($request->hasFile('file')) {
            $data['file'] = $request->file('file')->store('pengumuman', 'public');
        }

        Announcement::create($data);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil diterbitkan.');
    }

    /**
     * GET /admin/pengumuman/{id}/edit — form ubah pengumuman.
     */
    public function edit($id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $pengumuman = Announcement::findOrFail($id);

        return view('admin.pengumuman.edit', compact('pengumuman'));
    }

    /**
     * PUT /admin/pengumuman/{id} — simpan perubahan.
     * File lama dihapus bila diganti; published_at yang sudah terisi
     * tidak diubah bila field dikosongkan.
     */
    public function update(Request $request, $id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $pengumuman = Announcement::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'isi' => 'required|string',
            'kategori' => 'required|in:eksternal,internal',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'published_at' => 'nullable|date',
        ], array_merge(PesanValidasi::pesan([
            'judul' => 'Judul',
            'isi' => 'Isi pengumuman',
            'kategori' => 'Kategori',
            'file' => 'File lampiran',
            'published_at' => 'Tanggal tayang',
        ]), [
            'file.max' => 'Ukuran file lampiran maksimal 2 MB.',
        ]));

        $data = [
            'judul' => $validated['judul'],
            'isi' => $validated['isi'],
            'kategori' => $validated['kategori'],
        ];

        if (! empty($validated['published_at'])) {
            $data['published_at'] = $validated['published_at'];
        }

        if ($request->hasFile('file')) {
            if ($pengumuman->file && Storage::disk('public')->exists($pengumuman->file)) {
                Storage::disk('public')->delete($pengumuman->file);
            }

            $data['file'] = $request->file('file')->store('pengumuman', 'public');
        }

        $pengumuman->update($data);

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    /**
     * DELETE /admin/pengumuman/{id} — hapus pengumuman beserta filenya.
     */
    public function destroy($id)
    {
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403);
        }

        $pengumuman = Announcement::findOrFail($id);

        if ($pengumuman->file && Storage::disk('public')->exists($pengumuman->file)) {
            Storage::disk('public')->delete($pengumuman->file);
        }

        $pengumuman->delete();

        return redirect()->route('admin.pengumuman.index')
            ->with('success', 'Pengumuman berhasil dihapus.');
    }
}
