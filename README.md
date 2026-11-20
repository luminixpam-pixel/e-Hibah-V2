# E-Hibah V2 — Sistem Informasi E-Hibah Universitas YARSI

Aplikasi web untuk mengelola proses pengajuan, verifikasi, review, persetujuan, monitoring, dan pelaporan hibah penelitian di Universitas YARSI.

## Teknologi

- Laravel 12, PHP ^8.2
- MySQL
- Filament 3.3, Jetstream + Fortify + Sanctum
- LDAP — login dosen memakai akun kampus YARSI
- Vite + Tailwind CSS 4
- Dompdf — cetak PDF

## Syarat

- PHP 8.2+ (disarankan lewat [Laravel Herd](https://herd.laravel.com))
- Composer
- MySQL
- Node.js + npm

## Cara Menjalankan

```bash
# 1. Clone repo
git clone https://github.com/luminixpam-pixel/e-Hibah-V2.git
cd e-Hibah-V2

# 2. Install dependency PHP
composer install

# 3. Salin file environment, lalu isi pengaturan database & LDAP
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Migrasi database + isi data awal (termasuk akun tes 7 role)
php artisan migrate --seed

# 6. Buat symlink storage (file proposal disimpan di disk public)
php artisan storage:link

# 7. Install dependency JS lalu build aset
npm install
npm run build

# 8. Jalankan aplikasi
# - Via Herd: buka http://e-hibah-v2.test
# - Atau: php artisan serve, lalu buka http://127.0.0.1:8000
```

Saat pengembangan, jalankan `npm run dev` agar aset Vite ter-compile otomatis.

### Pengaturan `.env` yang wajib diisi

| Key | Isi |
| --- | --- |
| `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Koneksi MySQL |
| `LDAP_HOST`, `LDAP_PORT`, `LDAP_BASE_DN`, `LDAP_USERNAME`, `LDAP_PASSWORD` | Server LDAP kampus YARSI |

Contoh lengkap ada di `.env.example`. **Jangan pernah commit file `.env` ke repo.**

## Akun Tes (1 per Role)

Akun dummy untuk mencoba login tiap role. Dibuat oleh `AkunRoleSeeder` (jalan otomatis lewat `php artisan db:seed`).

| Role | Username | Password |
| --- | --- | --- |
| Administrator TI | `admin` | `admin123` |
| Pengaju (Dosen) | `pengaju` | `password123` |
| Reviewer | `reviewer` | `password123` |
| Verifikator Administrasi | `verifikator` | `password123` |
| Pengelola LPPM | `lppm` | `password123` |
| Pimpinan Fakultas (Dekan) | `dekan` | `password123` |
| Kepala LPPM | `kepala_lppm` | `password123` |

Setiap akun login akan mendarat di dashboard-nya masing-masing (`/dashboard/<role>`).

Akun asli (dosen YARSI) login lewat **LDAP** memakai akun kampus. Saat login LDAP pertama kali dan belum punya role, sistem memberi role `pengaju` — role sesuai jabatan diatur oleh admin lewat menu **Kelola Pengguna**. Akun dummy di atas bisa dihapus setelah akun asli dipakai.

## Skill & Aturan untuk AI Agent

Proyek ini memakai dua skill untuk agen AI (misalnya di Antigravity / Claude Code / Cursor) supaya hasil kerjanya konsisten dengan aturan proyek.

### 1. Laravel Boost — skill & panduan Laravel

Sudah terpasang (`laravel/boost` di `composer.json`). Panduannya ada di file **`AGENTS.md`** di root repo: ikuti konvensi kode yang sudah ada, cek file sejenis sebelum membuat file baru, pakai named route + `route()`, dan jangan membuat file dokumentasi baru kecuali diminta.

Perbarui panduannya bila perlu:

```bash
php artisan boost:install
```

### 2. Ponytail — aturan "lazy senior dev"

Skill agen (oleh Dietrich Gebert, lisensi MIT) yang membuat agen mengambil **perubahan sekecil mungkin** yang menyelesaikan tugas: jangan buat fitur yang tidak diminta, pakai dulu yang sudah ada di codebase, lalu standard library / fitur bawaan, lalu dependency yang sudah terpasang — baru tulis kode baru seminimal mungkin. Validasi, error handling, keamanan, dan yang diminta user tidak boleh dipotong.

Install ke proyek:

```bash
npx skills add https://github.com/dietrichgebert/ponytail --skill ponytail
```

Level aturan (default: `full`):

| Level | Perilaku |
| --- | --- |
| `lite` | Menyebutkan opsi yang lebih kecil, user yang memilih |
| `full` | Menerapkan aturan (default) |
| `ultra` | Juga mempertanyakan bagian request yang tidak perlu |

Ganti level dengan perintah `/ponytail lite|full|ultra`. Aturan berlaku selama sesi sampai dimatikan (`stop ponytail`).

Alternatif install: sebagai plugin di Claude Code / Codex (`/plugin marketplace add DietrichGebert/ponytail`, lalu `/plugin install ponytail@ponytail`), atau salin `SKILL.md`-nya ke proyek.

## Perintah yang Sering Dipakai

```bash
php artisan migrate            # jalankan migrasi baru
php artisan db:seed            # isi data awal (termasuk akun tes 7 role)
php artisan db:seed --class=AkunRoleSeeder   # hanya akun tes 7 role
php artisan route:clear        # bersihkan cache route
php artisan view:clear         # bersihkan cache view (wajib setelah ubah Blade)
php artisan config:clear       # bersihkan cache config
php artisan test               # jalankan test
```

## Catatan

- File `.env` tidak boleh di-commit. Database SQLite (`e_hibah`, `laravel`) dan file sensitif lain tidak boleh masuk repo.
- Role pengguna disimpan di kolom `users.role`. Daftar role: `admin`, `pengaju`, `reviewer`, `verifikator`, `lppm`, `dekan`, `kepala_lppm`.
- Setiap dosen maksimal terlibat dalam 3 proposal (sebagai ketua atau anggota).
