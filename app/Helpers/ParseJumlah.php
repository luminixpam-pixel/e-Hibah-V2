<?php

namespace App\Helpers;

/**
 * Parsing jumlah uang (RAB) dari input form.
 *
 * Latar: saveRabItems() dan penghitung total RAB di store()/update()
 * memakai (float) preg_replace('/[^0-9.]/', ...) — pola itu membuang
 * huruf 'e' sehingga '1e5' (yang lolos validasi numeric) dihitung
 * sebagai 15, bukan 100000. Helper ini menggantikan pola tersebut
 * di semua jalur RAB agar nilai yang disimpan sama dengan nilai
 * yang dihitung dan dipakai validasi plafon.
 *
 * Format yang ditangani:
 * - angka biasa / desimal titik: '1500000', '1500.50'
 * - notasi ilmiah: '1e5' → 100000, '1.5e3' → 1500
 * - format Indonesia (titik pemisah ribuan, koma desimal opsional):
 *   '1.000.000' → 1000000, '1.500.000,50' → 1500000.5
 * - awalan Rupiah: 'Rp 1.500.000' → 1500000
 * - spasi pemisah ribuan: '1 500 000' → 1500000
 *
 * Format yang TIDAK ditangani (parse() mengembalikan null sehingga
 * validasi menolaknya dengan 'Jumlah RAB harus berupa angka.'):
 * '1,5 juta', 'satu juta', '1,500', '1500,50' (desimal koma tanpa
 * pemisah ribuan — ambigu dengan pemisah ribuan gaya AS), dan
 * teks lain yang bukan angka.
 */
class ParseJumlah
{
    /**
     * Ubah nilai jumlah menjadi float, atau null bila nilai tidak
     * bisa dipahami sebagai angka.
     */
    public static function parse(mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (! is_string($value)) {
            return null;
        }

        $teks = trim($value);

        if ($teks === '') {
            return null;
        }

        // Awalan "Rp" (boleh diikuti spasi) dan spasi pemisah ribuan.
        $teks = (string) preg_replace('/^rp\s*/i', '', $teks);
        $teks = str_replace(' ', '', $teks);

        if ($teks === '') {
            return null;
        }

        // Angka biasa, desimal titik, dan notasi ilmiah — PHP sudah
        // memahaminya lewat is_numeric().
        if (is_numeric($teks)) {
            return (float) $teks;
        }

        // Format Indonesia: titik pemisah ribuan, koma desimal
        // opsional ('1.000.000', '1.500.000,50').
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $teks) === 1) {
            return (float) str_replace(',', '.', str_replace('.', '', $teks));
        }

        return null;
    }
}
