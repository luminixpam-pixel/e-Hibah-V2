<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
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
 * Catatan: menghapus skema yang masih dipakai proposal DITOLAK
 * (lihat destroy()) — FK proposals.skema_id memang nullOnDelete,
 * tetapi membiarkan skema_id menjadi null diam-diam akan merusak
 * jejak audit proposal (skema, plafon, dan kuota yang dipakai saat
 * pengajuan jadi tidak terlacak).
 */
class SkemaController extends Controller
{
    /**
     * Daftar semua skema, urut kategori lalu bidang.
     */
    public function index()
    {
        $skemas = Skema::orderBy('kategori')->orderBy('bidang')->get();

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
     * Simpan skema baru. Pasangan (kategori, bidang) unik — satu kategori
     * boleh punya dua bidang (Kesehatan/Humaniora), tetapi pasangan yang
     * sama tidak boleh duplikat. Khusus tidak punya bidang.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'in:Pembinaan,Kompetisi,Khusus', Rule::unique('skemas', 'kategori')->where(fn ($q) => $request->filled('bidang') ? $q->where('bidang', $request->bidang) : $q->whereNull('bidang'))],
            'bidang' => ['nullable', 'in:Kesehatan,Humaniora', Rule::prohibitedIf($request->input('kategori') === 'Khusus')],
            'plafon' => 'required|numeric|min:0',
            'kuota' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'syarat' => 'nullable|string',
        ], PesanValidasi::pesan([
            'kategori' => 'Kategori',
            'bidang' => 'Bidang',
            'plafon' => 'Plafon',
            'kuota' => 'Kuota',
            'deskripsi' => 'Deskripsi',
            'syarat' => 'Syarat',
        ]));

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
     * Simpan perubahan skema. Unique pasangan (kategori, bidang)
     * mengabaikan diri sendiri, jadi menyimpan tanpa mengubah pasangan
     * tidak kena error duplikat.
     */
    public function update(Request $request, Skema $skema)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'in:Pembinaan,Kompetisi,Khusus', Rule::unique('skemas', 'kategori')->where(fn ($q) => $request->filled('bidang') ? $q->where('bidang', $request->bidang) : $q->whereNull('bidang'))->ignore($skema->id)],
            'bidang' => ['nullable', 'in:Kesehatan,Humaniora', Rule::prohibitedIf($request->input('kategori') === 'Khusus')],
            'plafon' => 'required|numeric|min:0',
            'kuota' => 'required|integer|min:0',
            'deskripsi' => 'nullable|string',
            'syarat' => 'nullable|string',
        ], PesanValidasi::pesan([
            'kategori' => 'Kategori',
            'bidang' => 'Bidang',
            'plafon' => 'Plafon',
            'kuota' => 'Kuota',
            'deskripsi' => 'Deskripsi',
            'syarat' => 'Syarat',
        ]));

        $skema->update($validated);

        return redirect()->route('admin.skema.index')->with('success', 'Skema berhasil diperbarui.');
    }

    /**
     * Hapus skema. DITOLAK bila masih dipakai proposal — skema yang
     * dipakai tidak boleh hilang diam-diam (skema_id proposal terkait
     * akan menjadi null via FK nullOnDelete dan jejak skema/plafon
     * pengajuan rusak). Hapus atau pindahkan dulu proposal terkait,
     * baru hapus skemanya.
     */
    public function destroy(Skema $skema)
    {
        $jumlahProposal = $skema->proposals()->count();

        if ($jumlahProposal > 0) {
            return redirect()->route('admin.skema.index')
                ->with('error', "Skema {$skema->label()} tidak dapat dihapus karena masih dipakai oleh {$jumlahProposal} proposal.");
        }

        $skema->delete();

        return redirect()->route('admin.skema.index')->with('success', 'Skema berhasil dihapus.');
    }
}
