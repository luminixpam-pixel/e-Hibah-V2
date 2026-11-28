<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Tambahkan ini untuk hashing password
use Illuminate\Validation\Rule;       // Tambahkan ini untuk validasi unique

class AdminController extends Controller
{
    /**
     * Halaman hasil review
     */
    public function hasilReview()
    {
        // Ambil review asli (dulu $reviews = [] kosong dan view-nya
        // 'admin.hasil-review' tidak ada di disk -> halaman 500).
        $reviews = \App\Models\Review::with(['proposal', 'reviewer'])->latest()->get();

        return view('proposal.admin.hasil-review', compact('reviews'));
    }

    /**
     * Halaman kalender
     */
    public function calendar()
    {
        // View 'admin.calendar' tidak ada di disk -> halaman 500.
        // File yang ada: 'proposal/admin/calendar.blade.php'.
        return view('proposal.admin.calendar');
    }

    /**
     * ================================
     * MANAJEMEN USER (LDAP & LOKAL)
     * ================================
     */

    /**
     * Menampilkan daftar user
     */
    // app/Http/Controllers/DashboardController.php

    public function index()
    {
        $role = auth()->user()->role;

        // Ambil jumlah dokumen panduan yang aktif
        $templateCount = \App\Models\AdminDocument::where('is_visible', true)->count();

        // Pastikan variabel ini dikirim ke view dashboard
        // Sebutkan saja variabel apa saja yang mau dikirim ke view
        return view('dashboard', compact('templateCount', 'role'));

    }

    public function indexUsers(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        // Pagination + pencarian sederhana: sebelumnya User::all() memuat
        // ratusan baris sekaligus tanpa pencarian.
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    /**
     * Update data user (Username & Password)
     */
    public function updateUser(Request $request, $id)
    {
        // 🔒 pastikan admin
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $user = User::findOrFail($id);

        // ✅ validasi
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => ['required', 'string', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,pengaju,reviewer,verifikator,lppm,dekan,kepala_lppm',
            'password' => 'nullable|min:8|confirmed', // 'confirmed' berarti butuh input password_confirmation
        ], PesanValidasi::pesan([
            'name' => 'Nama',
            'username' => 'Username',
            'role' => 'Role',
            'password' => 'Kata sandi',
        ]));

        // 🔒 Admin tidak boleh mengubah role akunnya sendiri: tanpa
        // guard ini admin bisa menurunkan dirinya menjadi pengaju
        // (mengunci diri keluar dari admin) atau menaikkan akun
        // lain sembarangan lewat request yang dimanipulasi. Ubah
        // role admin lain tetap dimungkinkan — keduanya sudah admin
        // penuh — dan bila itu pun ingin dibatasi, perlu keputusan tim.
        if ((int) $user->id === (int) auth()->user()->id && $request->role !== $user->role) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        // Update data dasar
        $user->name = $request->name;
        $user->username = $request->username;
        $user->role = $request->role;

        // 🔑 Jika password diisi (untuk user lokal), update hashed passwordnya
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Data user berhasil diperbarui.');
    }

    /**
     * CATATAN: method ini TIDAK dipakai route mana pun (tidak ada route
     * yang menunjuk ke AdminController@update) — kode mati. Tetap
     * diperketat agar tidak menjadi celah bila suatu saat di-route:
     * sebelumnya method ini tidak memeriksa role sama sekali, sehingga
     * user login mana pun bisa mengubah username/password user lain
     * lewat ID di URL. Sekarang hanya admin, dan hanya field
     * username/password yang disentuh (tidak ada mass assignment
     * role/is_blacklisted dari request).
     */
    public function update(Request $request, $id)
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }

        $request->validate([
            'username' => 'required|string|max:255|unique:users,username,'.$id,
            'password' => 'nullable|min:8|confirmed', // password opsional, diisi jika ingin diubah
        ], PesanValidasi::pesan([
            'username' => 'Username',
            'password' => 'Kata sandi',
        ]));

        $user = \App\Models\User::findOrFail($id);
        $user->username = $request->username;

        // Hanya update password jika kolom diisi
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Data user berhasil diperbarui!');
    }
}
