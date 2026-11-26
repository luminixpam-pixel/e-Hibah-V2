<?php

namespace App\Http\Controllers;

use App\Models\Skema;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master data skema hibah (Fase 6, story 15).
 *
 * Pola mengikuti AdminDocumentController / LogbookController: controller
 * flat, validasi di trust boundary, redirect + flash message.
 *
 * Otorisasi ditangani route (middleware role:admin,lppm) — controller
 * ini tidak membandingkan role sendiri.
 *
 * Catatan: menghapus skema aman untuk proposal yang memakainya —
 * FK proposals.skema_id memakai nullOnDelete, jadi skema_id proposal
 * terkait menjadi null, bukan error.
 */
class SkemaController extends Controller
{
    /**
     * Daftar semua skema, urut abjad kategori.
     */
    public function index()
    {
        $skemas = Skema::orderBy('kategori')->get();

        return view('admin.skema.index', compact('skemas'));
    }

    /**
     * Form tambah skema.
     */
    public function create()
    {
        return view('admin.skema.create');
    }

    /**
     * Simpan skema baru. Kategori unik (satu baris per kategori).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'in:Pembinaan,Kompetisi,Khusus', Rule::unique('skemas', 'kategori')],
            'plafon' => 'required|numeric|min:0',
            'kuota' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'syarat' => 'nullable|string',
        ]);

        Skema::create($validated);

        return redirect()->route('admin.skema.index')->with('success', 'Skema berhasil ditambahkan.');
    }

    /**
     * Form ubah skema.
     */
    public function edit(Skema $skema)
    {
        return view('admin.skema.edit', compact('skema'));
    }

    /**
     * Simpan perubahan skema. Unique kategori mengabaikan diri sendiri,
     * jadi menyimpan tanpa mengubah kategori tidak kena error duplikat.
     */
    public function update(Request $request, Skema $skema)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'in:Pembinaan,Kompetisi,Khusus', Rule::unique('skemas', 'kategori')->ignore($skema->id)],
            'plafon' => 'required|numeric|min:0',
            'kuota' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'syarat' => 'nullable|string',
        ]);

        $skema->update($validated);

        return redirect()->route('admin.skema.index')->with('success', 'Skema berhasil diperbarui.');
    }

    /**
     * Hapus skema. Proposal yang memakai skema ini tidak ikut terhapus
     * (skema_id-nya menjadi null via FK nullOnDelete).
     */
    public function destroy(Skema $skema)
    {
        $skema->delete();

        return redirect()->route('admin.skema.index')->with('success', 'Skema berhasil dihapus.');
    }
}
