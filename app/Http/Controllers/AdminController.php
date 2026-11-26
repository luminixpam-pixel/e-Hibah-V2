<?php

namespace App\Http\Controllers;

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
        ]);

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

    public function update(Request $request, $id)
    {
        $request->validate([
            'username' => 'required|unique:users,username,'.$id,
            'password' => 'nullable|min:6|confirmed', // password opsional, diisi jika ingin diubah
        ]);

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
