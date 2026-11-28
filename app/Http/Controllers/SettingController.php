<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingController extends Controller
{
    /**
     * Daftar semua setelan admin + form tambah.
     */
    public function index()
    {
        $settings = Setting::orderBy('key')->get();

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Simpan setelan baru. Key wajib snake_case dan unik.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:settings,key'],
            'value' => ['nullable', 'string'],
        ], array_merge(PesanValidasi::pesan([
            'key' => 'Key setelan',
            'value' => 'Nilai setelan',
        ]), [
            'key.regex' => 'Key setelan harus snake_case (huruf kecil, diawali huruf, boleh angka dan underscore).',
        ]));

        Setting::create($validated);

        return back()->with('success', 'Setelan berhasil ditambahkan.');
    }

    /**
     * Ubah setelan yang sudah ada. Key tetap wajib snake_case;
     * keunikan dicek dengan mengabaikan baris ini sendiri.
     */
    public function update(Request $request, $id)
    {
        $setting = Setting::findOrFail($id);

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('settings', 'key')->ignore($setting->id)],
            'value' => ['nullable', 'string'],
        ], array_merge(PesanValidasi::pesan([
            'key' => 'Key setelan',
            'value' => 'Nilai setelan',
        ]), [
            'key.regex' => 'Key setelan harus snake_case (huruf kecil, diawali huruf, boleh angka dan underscore).',
        ]));

        $setting->update($validated);

        return back()->with('success', 'Setelan berhasil diperbarui.');
    }

    /**
     * Hapus setelan.
     */
    public function destroy($id)
    {
        Setting::findOrFail($id)->delete();

        return back()->with('success', 'Setelan berhasil dihapus.');
    }
}
