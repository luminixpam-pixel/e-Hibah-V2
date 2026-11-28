<?php

namespace App\Helpers;

/**
 * Pesan validasi Bahasa Indonesia untuk controller di luar alur
 * pengajuan proposal.
 *
 * Mengikuti pola ProposalController::pesanValidasiPengajuan(): locale
 * aplikasi tetap 'en' dan tidak ada lang/id/validation.php, jadi pesan
 * kustom per-controller yang dipakai — bukan pengubahan locale global
 * (yang akan mengubah pesan bawaan Fortify/Jetstream dan berisiko
 * memecahkan test lama yang mengunci teks Inggris).
 *
 * Pemakaian: PesanValidasi::pesan(['nama_field' => 'Label Indonesia', ...])
 * menghasilkan pesan untuk aturan umum (required, string, max, email,
 * unique, numeric, integer, min, date, after_or_equal, confirmed,
 * mimes, file, regex, in, exists). Aturan khusus ditambahkan sendiri
 * oleh pemanggil.
 */
class PesanValidasi
{
    /**
     * @param  array<string, string>  $label  Nama field => label Bahasa Indonesia.
     * @return array<string, string>
     */
    public static function pesan(array $label): array
    {
        $pesan = [];

        foreach ($label as $field => $nama) {
            $pesan["{$field}.required"] = "{$nama} wajib diisi.";
            $pesan["{$field}.string"] = "{$nama} harus berupa teks.";
            $pesan["{$field}.max"] = "{$nama} maksimal :max karakter.";
            $pesan["{$field}.email"] = "{$nama} harus berupa alamat email yang valid.";
            $pesan["{$field}.unique"] = "{$nama} sudah digunakan.";
            $pesan["{$field}.numeric"] = "{$nama} harus berupa angka.";
            $pesan["{$field}.integer"] = "{$nama} harus berupa bilangan bulat.";
            $pesan["{$field}.min"] = "{$nama} tidak boleh kurang dari :min.";
            $pesan["{$field}.date"] = "{$nama} harus berupa tanggal yang valid.";
            $pesan["{$field}.after_or_equal"] = "{$nama} tidak boleh lebih awal dari tanggal mulai.";
            $pesan["{$field}.confirmed"] = "Konfirmasi {$nama} tidak cocok.";
            $pesan["{$field}.file"] = "{$nama} harus berupa berkas.";
            $pesan["{$field}.mimes"] = "{$nama} harus berformat :values.";
            $pesan["{$field}.regex"] = "Format {$nama} tidak valid.";
            $pesan["{$field}.in"] = "{$nama} yang dipilih tidak valid.";
            $pesan["{$field}.exists"] = "{$nama} yang dipilih tidak valid.";
        }

        return $pesan;
    }
}
