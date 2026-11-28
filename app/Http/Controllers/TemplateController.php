<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    /**
     * Jenis template yang dikenal aplikasi (dipakai view laporan dan
     * AppServiceProvider). Jenis lain tidak boleh dibuat lewat unggahan.
     */
    private const JENIS_TEMPLATE = ['laporan_kemajuan', 'laporan_akhir'];

    /**
     * Kelola template hanya boleh dilakukan admin. Route sudah di dalam
     * grup middleware role:admin, tapi controller tetap memeriksa
     * sendiri (defense in depth).
     */
    private function ensureAdmin(): void
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang boleh mengelola template.');
        }
    }

    /**
     * Menyimpan atau memperbarui template (Khusus Admin)
     */
    public function store(Request $request)
    {
        $this->ensureAdmin();

        // 1. Validasi Input
        $request->validate([
            'file_template' => 'required|mimes:docx,doc,pdf,xlsx|max:5120', // Maks 5MB
            'jenis' => 'required|string|in:'.implode(',', self::JENIS_TEMPLATE),
        ]);

        try {
            if ($request->hasFile('file_template')) {
                $file = $request->file('file_template');
                $jenis = $request->jenis;

                // 2. Cari data lama untuk dihapus filenya (agar tidak memenuhi storage)
                $oldTemplate = Template::where('jenis', $jenis)->first();
                if ($oldTemplate && Storage::disk('public')->exists($oldTemplate->file_path)) {
                    Storage::disk('public')->delete($oldTemplate->file_path);
                }

                // 3. Simpan file baru ke folder 'templates' di public storage.
                // Nama asli dari client dibersihkan dulu: basename()
                // membuang komponen direktori ('../') dan karakter
                // selain huruf/angka/titik/dash/underscore dihapus,
                // supaya nama file tidak bisa dipakai untuk traversal.
                $originalName = basename($file->getClientOriginalName());
                $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $originalName);
                $safeName = $safeName !== '' ? $safeName : 'template';
                $fileName = time().'_'.$safeName;
                $path = $file->storeAs('templates', $fileName, 'public');

                // 4. Update Database
                Template::updateOrCreate(
                    ['jenis' => $jenis],
                    [
                        'nama_template' => $safeName,
                        'file_path' => $path,
                    ]
                );

                return back()->with('success', 'Template '.str_replace('_', ' ', $jenis).' berhasil diperbarui!');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengunggah template: '.$e->getMessage());
        }
    }

    /**
     * Menghapus template (Opsional untuk Admin)
     */
    public function destroy($id)
    {
        $this->ensureAdmin();

        $template = Template::findOrFail($id);

        if (Storage::disk('public')->exists($template->file_path)) {
            Storage::disk('public')->delete($template->file_path);
        }

        $template->delete();

        return back()->with('success', 'Template berhasil dihapus.');
    }

    /**
     * Mengunduh template (menggantikan link asset('storage/...')
     * langsung di view — lewat controller agar file dicek dulu).
     */
    public function download($id)
    {
        $template = Template::findOrFail($id);

        if (! $template->file_path || ! Storage::disk('public')->exists($template->file_path)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        return Storage::disk('public')->download($template->file_path, $template->nama_template);
    }

    /**
     * Preview template (inline bila PDF/gambar, unduh bila bukan).
     */
    public function preview($id)
    {
        $template = Template::findOrFail($id);

        if (! $template->file_path || ! Storage::disk('public')->exists($template->file_path)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        return $this->previewStoredFile(
            $template->file_path,
            $template->nama_template,
            'template.download',
            ['id' => $id]
        );
    }
}
