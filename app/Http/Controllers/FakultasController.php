<?php

namespace App\Http\Controllers;

use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master data fakultas / unit (Fase 6, story 15).
 *
 * Pola mengikuti AdminDocumentController / LogbookController: controller
 * flat, validasi di trust boundary, redirect + flash message.
 *
 * Otorisasi ditangani route (middleware role:admin,lppm) — controller
 * ini tidak membandingkan role sendiri.
 *
 * Catatan:
 * - Hanya kolom nama_fakultas yang dikelola. Fakultas::$fillable juga
 *   memuat kode_fakultas, tetapi kolom itu TIDAK ADA di tabel fakultas
 *   (diskrepansi model ↔ migrasi, sudah dicatat di analisis) — jadi
 *   sengaja tidak dipakai di sini.
 * - Menghapus fakultas aman: FK proposals.fakultas_prodi dan
 *   users.fakultas_id sama-sama onDelete set null, jadi referensi
 *   terkait menjadi null, bukan error.
 */
class FakultasController extends Controller
{
    /**
     * Daftar semua fakultas beserta jumlah proposal per fakultas.
     */
    public function index()
    {
        $fakultas = Fakultas::withCount('proposals')->orderBy('nama_fakultas')->get();

        return view('admin.fakultas.index', compact('fakultas'));
    }

    /**
     * Form tambah fakultas.
     */
    public function create()
    {
        return view('admin.fakultas.create');
    }

    /**
     * Simpan fakultas baru. Nama wajib dan unik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_fakultas' => ['required', 'string', 'max:255', Rule::unique('fakultas', 'nama_fakultas')],
        ]);

        Fakultas::create($validated);

        return redirect()->route('admin.fakultas.index')->with('success', 'Fakultas berhasil ditambahkan.');
    }

    /**
     * Form ubah fakultas.
     */
    public function edit(Fakultas $fakultas)
    {
        return view('admin.fakultas.edit', compact('fakultas'));
    }

    /**
     * Simpan perubahan fakultas. Unique mengabaikan diri sendiri, jadi
     * menyimpan tanpa mengubah nama tidak kena error duplikat.
     */
    public function update(Request $request, Fakultas $fakultas)
    {
        $validated = $request->validate([
            'nama_fakultas' => ['required', 'string', 'max:255', Rule::unique('fakultas', 'nama_fakultas')->ignore($fakultas->id)],
        ]);

        $fakultas->update($validated);

        return redirect()->route('admin.fakultas.index')->with('success', 'Fakultas berhasil diperbarui.');
    }

    /**
     * Hapus fakultas. Proposal dan user yang merujuk fakultas ini tidak
     * ikut terhapus (referensinya menjadi null via FK onDelete set null).
     */
    public function destroy(Fakultas $fakultas)
    {
        $fakultas->delete();

        return redirect()->route('admin.fakultas.index')->with('success', 'Fakultas berhasil dihapus.');
    }
}
