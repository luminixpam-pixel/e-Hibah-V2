<?php

// Perbaikan bug uji manual web ke-5, 28 Nov 2026 (Butir 2 — pagination
// /admin/users):
// - Gejala: klik nomor halaman dan "Next »" tidak berpindah (4x
//   percobaan) padahal href benar (?page=3); navigasi lewat URL
//   langsung berfungsi ("Showing 21 to 40 of 313 results").
// - Penyelidikan dari kode: TIDAK ada handler klik global di layout
//   yang menelan klik tautan pagination (overlay #notifPopup sudah
//   pointer-events: none + penutupnya tidak preventDefault; skrip
//   pemindah .modal hanya memindah modal display:none; tidak ada
//   aturan pointer-events pada pembungkus pagination), dan sisi server
//   sudah benar (paginate(20) + withQueryString di
//   AdminController@indexUsers). Jadi "klik tidak berpindah" TIDAK
//   punya penyebab yang bisa dibuktikan dari kode — lihat laporan
//   koordinator untuk sisa hipotesis yang butuh cek browser hidup.
// - Cacat yang TERBUKTI dan diperbaiki di view ini: links() default
//   merender view pagination Tailwind, padahal layout
//   (resources/views/layouts/app.blade.php) hanya memuat CSS
//   Bootstrap 5.3 dari CDN dan tidak pernah memuat Tailwind
//   (tanpa @vite; resources/css/app.css yang mengimpor Tailwind
//   tidak di-include; public/build tidak ada). Akibatnya class
//   responsif Tailwind (hidden / sm:flex / sm:hidden) tidak berdaya
//   sehingga blok mobile DAN blok desktop pagination tampil
//   bersamaan tanpa gaya. Perbaikan: links('pagination::bootstrap-5')
//   — markup Bootstrap yang memang dimuat layout; URL, jumlah
//   per halaman, dan teks "Showing ... results" tidak berubah.
// Helper berprefix ub5b* agar tidak bentrok dengan file test lain.

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function ub5bUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

/** Buat $jumlah pengguna pengaju bernama "Pengguna 01" dst. (urut nama). */
function ub5bBuatPengguna(int $jumlah): void
{
    for ($i = 1; $i <= $jumlah; $i++) {
        ub5bUser('pengaju', ['name' => 'Pengguna '.str_pad((string) $i, 2, '0', STR_PAD_LEFT)]);
    }
}

// --- Butir 2: pagination memakai markup Bootstrap 5, bukan Tailwind ---

test('ub5b: pagination admin users merender markup bootstrap-5 bukan tailwind', function () {
    $admin = ub5bUser('admin', ['name' => 'Admin Uji']);
    ub5bBuatPengguna(25);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    // Markup Bootstrap 5: satu <ul class="pagination"> berisi tautan
    // .page-link — class yang memang digayakan CSS Bootstrap layout.
    $response->assertSee('<ul class="pagination">', false);
    $response->assertSee('page-link', false);
    $response->assertSee('?page=2', false);
    // Teks ringkasan tetap ada (view bootstrap-5 merender kalimat yang
    // sama seperti view Tailwind).
    $response->assertSee('Showing', false);
    // Tidak ada lagi sisa markup view Tailwind: blok mobile
    // (sm:hidden) dan blok desktop (hidden sm:flex-1) beserta
    // pembungkus tautannya (relative z-0 inline-flex) dan label
    // navigasinya — ketiganya hanya berasal dari view Tailwind.
    $response->assertDontSee('sm:hidden', false);
    $response->assertDontSee('Pagination Navigation', false);
    $response->assertDontSee('relative z-0 inline-flex', false);
});

test('ub5b: halaman pertama menampilkan 20 baris pertama terurut nama', function () {
    $admin = ub5bUser('admin', ['name' => 'Admin Uji']);
    ub5bBuatPengguna(25);

    $response = $this->actingAs($admin)->get(route('admin.users.index'));

    $response->assertOk();
    // 'Admin Uji' terurut sebelum 'Pengguna 01', jadi halaman 1 berisi
    // admin + Pengguna 01-19 (20 baris). Periksa baris tabel, bukan
    // seluruh halaman: layout memuat <datalist id="dosenList"> berisi
    // nama SEMUA pengaju/reviewer (AppServiceProvider composer '*').
    $response->assertSee('<td><strong>Pengguna 01</strong></td>', false);
    $response->assertDontSee('<td><strong>Pengguna 20</strong></td>', false);
});

test('ub5b: halaman kedua menampilkan baris berikutnya beserta tautan kembali', function () {
    $admin = ub5bUser('admin', ['name' => 'Admin Uji']);
    ub5bBuatPengguna(25);

    $response = $this->actingAs($admin)->get(route('admin.users.index', ['page' => 2]));

    $response->assertOk();
    $response->assertSee('<td><strong>Pengguna 20</strong></td>', false);
    $response->assertSee('<td><strong>Pengguna 25</strong></td>', false);
    $response->assertDontSee('<td><strong>Pengguna 01</strong></td>', false);
    $response->assertSee('?page=1', false);
});

test('ub5b: parameter pencarian dipertahankan di tautan pagination', function () {
    $admin = ub5bUser('admin', ['name' => 'Admin Uji']);
    ub5bBuatPengguna(25);

    // withQueryString(): tautan halaman membawa kembali ?search=...
    $response = $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'Pengguna']));

    $response->assertOk();
    $response->assertSee('search=Pengguna', false);
    $response->assertSee('page=2', false);
    $response->assertSee('value="Pengguna"', false);
});

test('ub5b: bukan admin tidak boleh membuka daftar pengguna', function () {
    $this->actingAs(ub5bUser('pengaju', ['name' => 'Pengaju Biasa']))
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

// --- Permukaan klik lain di halaman yang sama: modal edit per pengguna ---

test('ub5b: setiap pengguna di halaman punya satu modal edit dengan id unik', function () {
    $admin = ub5bUser('admin', ['name' => 'Admin Uji']);
    $satu = ub5bUser('pengaju', ['name' => 'Pengguna A']);
    $dua = ub5bUser('pengaju', ['name' => 'Pengguna B']);

    $html = $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()
        ->getContent();

    // Tombol Edit menunjuk ke modalnya sendiri, dan modal itu hanya
    // ada satu (id ganda akan membuat data-bs-target selalu membuka
    // modal pertama yang cocok).
    foreach ([$satu, $dua] as $user) {
        expect(substr_count($html, 'data-bs-target="#editUserModal-'.$user->id.'"'))->toBe(1)
            ->and(substr_count($html, 'id="editUserModal-'.$user->id.'"'))->toBe(1);
    }
});
