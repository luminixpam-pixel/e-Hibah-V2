<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Key settings untuk daftar komponen RAB kustom (JSON array string).
     */
    public const RAB_KOMPONEN_KUSTOM_KEY = 'rab_komponen_kustom';

    /**
     * Daftar nama komponen RAB bawaan (dipakai form pengajuan proposal).
     * Nama 'Honorarium' dan 'ATK' wajib ada di sini karena dipakai
     * test Fase 2 (PengajuanProposalTest) dan TanggunganTest.
     *
     * @return array<int, string>
     */
    public static function komponenRabBawaan(): array
    {
        return [
            'Honorarium',
            'ATK',
            'Bahan Habis Pakai',
            'Perjalanan Dinas',
            'Publikasi',
            'Peralatan',
            'Konsumsi',
            'Dokumentasi',
            'Sewa',
        ];
    }

    /**
     * Daftar komponen RAB kustom dari settings key
     * 'rab_komponen_kustom' (JSON array string, mis.
     * ["Sewa Lab", "Uji Sampel"]). Bila key belum ada atau isinya
     * bukan JSON array yang valid, dianggap daftar kustom kosong.
     *
     * @return array<int, string>
     */
    public static function komponenRabKustom(): array
    {
        $setting = static::where('key', self::RAB_KOMPONEN_KUSTOM_KEY)->first();

        if (! $setting || $setting->value === null || trim($setting->value) === '') {
            return [];
        }

        $decoded = json_decode($setting->value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), $decoded),
            fn ($v) => $v !== ''
        ));
    }

    /**
     * Daftar lengkap nama komponen RAB yang boleh dipakai di form
     * pengajuan: bawaan + kustom (tanpa duplikat).
     *
     * @return array<int, string>
     */
    public static function daftarKomponenRab(): array
    {
        return array_values(array_unique(array_merge(
            static::komponenRabBawaan(),
            static::komponenRabKustom()
        )));
    }

    /**
     * Ambil nilai satu settings key, atau $default bila belum ada.
     */
    public static function nilai(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }
}
