<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Models\ActivityLog;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
// ⬅️ WAJIB INI
use Illuminate\Support\Facades\Auth; // ✅ TAMBAH INI (buat notif bell)

class ReviewerController extends Controller
{
    /**
     * Helper internal untuk mencatat log aktivitas pengguna
     * (pola yang sama dengan ProposalController::logActivity).
     */
    private function logActivity($activity)
    {
        ActivityLog::create([
            'user_id' => auth()->user()->id,
            'activity' => $activity,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Ambil daftar reviewer (buat search di admin) - DIPANGGIL VIA AJAX
     */
    public function searchReviewer(Request $request)
    {
        $q = $request->q;

        // ✅ ADMIN: boleh cari user siapa saja
        if (Auth::check() && Auth::user()->role === 'admin') {
            $users = User::where('name', 'like', "%$q%")
                ->select('id', 'name', 'role')
                ->limit(10)
                ->get();

            return response()->json($users);
        }

        // ✅ DEFAULT: hanya role reviewer
        $reviewers = User::where('role', 'reviewer')
            ->where('name', 'like', "%$q%")
            ->select('id', 'name')
            ->limit(10)
            ->get();

        return response()->json($reviewers);
    }

    /**
     * ✅ ADMIN: halaman list user untuk dijadikan reviewer
     * ✅ REVIEWER: halaman list proposal yang ditugaskan
     */
    public function index()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            // Pagination 20/halaman (uji ke-5 butir 10): sebelumnya
            // User::all() memuat ratusan baris sekaligus — pola yang
            // sama dengan /admin/users (AdminController@indexUsers).
            $users = User::orderBy('name')->paginate(20);

            return view('reviewer.pilih-reviewer', compact('users'));
        }

        // ✅ kalau reviewer buka menu reviewer
        // Auth::id() = username, bukan users.id — pakai Auth::user()->id.
        $proposals = Proposal::with('reviewers')
            ->whereHas('reviewers', function ($q) {
                $q->where('users.id', Auth::user()->id);
            })
            ->orderBy('review_deadline', 'asc')
            ->get();

        // View 'proposal-perlu-direview' tidak ada di disk -> halaman 500.
        // File yang ada: 'proposal/proposal-perlu-direview.blade.php'.
        return view('proposal.proposal-perlu-direview', compact('proposals'));
    }

    /**
     * ✅ ADMIN bisa jadikan user sebagai reviewer
     * route: POST /admin/reviewer/{user}
     */
    public function setReviewer(User $user)
    {
        // 🔒 pastikan admin
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        // 🔒 Admin tidak boleh mengubah role akunnya sendiri lewat
        // jalur ini (lihat AdminController@updateUser).
        if ((int) $user->id === (int) Auth::user()->id) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        // admin tidak boleh diubah
        if ($user->role === 'admin') {
            return back()->with('error', 'Admin tidak bisa dijadikan reviewer.');
        }

        // ✅ kalau sudah reviewer, stop aja
        if ($user->role === 'reviewer') {
            return back()->with('error', 'User ini sudah menjadi reviewer.');
        }

        // ✅ Hanya pengaju yang bisa dijadikan reviewer. Sebelumnya
        // user lppm/dekan/verifikator/kepala_lppm pun bisa diubah
        // menjadi reviewer lewat sini, sehingga role istimewanya
        // hilang diam-diam. Perubahan role lintas jabatan tetap bisa
        // lewat halaman manajemen user (AdminController@updateUser).
        if ($user->role !== 'pengaju') {
            return back()->with('error', 'Hanya user dengan role Pengaju yang bisa dijadikan reviewer.');
        }

        $user->role = 'reviewer';
        $user->save();

        // ✅ kirim notif bell
        NotificationHelper::send(
            $user->id,
            'Anda Ditugaskan Sebagai Reviewer',
            'Role akun Anda telah diubah menjadi Reviewer oleh Admin. Anda tidak dapat mengunggah proposal selama berstatus Reviewer.',
            'info'
        );

        return back()->with('success', 'User berhasil dijadikan reviewer.');
    }

    /**
     * ✅ ADMIN bisa berhentikan reviewer (balikin role jadi pengaju)
     * route: POST /admin/reviewer/{user}/remove
     */
    public function removeReviewer(User $user)
    {
        // 🔒 pastikan admin
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403);
        }

        // 🔒 Admin tidak boleh mengubah role akunnya sendiri lewat
        // jalur ini (lihat AdminController@updateUser).
        if ((int) $user->id === (int) Auth::user()->id) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        // ✅ kalau bukan reviewer, stop
        if ($user->role !== 'reviewer') {
            return back()->with('error', 'User ini bukan reviewer.');
        }

        // ✅ Tolak bila reviewer masih punya tugas review yang belum
        // dinilai: sebelumnya role dikembalikan ke pengaju begitu
        // saja, tetapi penugasan di proposal_reviewers tetap ada
        // sehingga proposal itu menggantung tanpa reviewer aktif.
        // Cabut dulu penugasannya (atau tunggu review selesai),
        // baru hentikan status reviewernya.
        $tugasAktif = \Illuminate\Support\Facades\DB::table('proposal_reviewers')
            ->where('reviewer_id', $user->id)
            ->pluck('proposal_id');
        $sudahDinilai = Review::where('reviewer_id', $user->id)
            ->whereIn('proposal_id', $tugasAktif->all())
            ->pluck('proposal_id');
        $belumDinilai = $tugasAktif->diff($sudahDinilai)->count();

        if ($belumDinilai > 0) {
            return back()->with('error', "Reviewer ini masih memiliki {$belumDinilai} tugas review yang belum dinilai. Selesaikan atau cabut penugasannya terlebih dahulu.");
        }

        $user->role = 'pengaju';
        $user->save();

        // ✅ kirim notif bell
        NotificationHelper::send(
            $user->id,
            'Status Reviewer Dihentikan',
            'Role akun Anda telah dikembalikan menjadi Pengaju oleh Admin. Anda sekarang bisa mengunggah proposal.',
            'success'
        );

        return back()->with('success', 'Reviewer berhasil dihentikan (role dikembalikan jadi pengaju).');
    }

    /**
     * Halaman isi review oleh reviewer
     */
    public function isiReview($id)
    {
        $proposal = Proposal::with('reviewers')->findOrFail($id);

        $userId = Auth::user()->id;

        // ✅ Pastikan reviewer benar-benar ditugaskan
        if (Auth::user()->role === 'reviewer') {
            $isAssigned = $proposal->reviewers->pluck('id')->contains($userId);

            if (! $isAssigned) {
                abort(403, 'Anda bukan reviewer yang ditugaskan. (ID Anda: '.$userId.')');
            }
        }

        // 🔒 Form review hanya saat proposal sedang direview. Tanpa
        // penjaga ini, form bisa dibuka untuk proposal yang sudah
        // selesai/ditolak, lalu submit-nya lolos diam-diam.
        if ($proposal->status !== 'Sedang Direview') {
            return back()->with('error', 'Proposal ini sudah tidak dalam tahap review.');
        }

        // 🔒 Tenggat penilaian sudah lewat.
        if ($proposal->review_deadline && now()->greaterThan($proposal->review_deadline)) {
            return back()->with('error', 'Tenggat penilaian proposal ini sudah lewat.');
        }

        // 🔒 Satu reviewer hanya menilai sekali.
        if (Review::where('proposal_id', $proposal->id)->where('reviewer_id', $userId)->exists()) {
            return back()->with('error', 'Anda sudah mengirim review untuk proposal ini.');
        }

        return view('reviewer.isi-review', compact('proposal'));
    }

    /**
     * Submit review oleh reviewer
     */
    public function submitReview(Request $request, $id)
    {
        $proposal = Proposal::with('reviewers')->findOrFail($id);

        // ✅ Pastikan reviewer benar-benar ditugaskan (seperti isiReview).
        // Tanpa cek ini, reviewer mana pun bisa mengirim review untuk
        // proposal yang bukan tugasnya lewat POST langsung.
        // Catatan: route ini sudah di dalam grup middleware
        // role:reviewer, jadi admin/peran lain tidak pernah sampai
        // ke sini — cek di bawah adalah pertahanan berlapis.
        if (Auth::user()->role === 'reviewer') {
            $isAssigned = $proposal->reviewers->pluck('id')->contains(Auth::user()->id);

            if (! $isAssigned) {
                abort(403, 'Anda bukan reviewer yang ditugaskan. (ID Anda: '.Auth::user()->id.')');
            }
        }

        // 🔒 Hanya saat proposal sedang direview. Tanpa penjaga ini,
        // review bisa masuk untuk proposal Draft/Dikirim/Disetujui.
        if ($proposal->status !== 'Sedang Direview') {
            return back()->with('error', 'Proposal ini sudah tidak dalam tahap review.');
        }

        // 🔒 Tenggat penilaian sudah lewat.
        if ($proposal->review_deadline && now()->greaterThan($proposal->review_deadline)) {
            return back()->with('error', 'Tenggat penilaian proposal ini sudah lewat.');
        }

        // 🔒 Satu reviewer hanya boleh menilai sekali. Tanpa penjaga
        // ini, reviewer yang sama bisa submit 2x dan penghitung
        // updateProposalStatus() mengira semua reviewer sudah
        // menilai, sehingga proposal jadi "Review Selesai" padahal
        // reviewer kedua belum mereview.
        if (Review::where('proposal_id', $proposal->id)->where('reviewer_id', Auth::user()->id)->exists()) {
            return back()->with('error', 'Anda sudah mengirim review untuk proposal ini.');
        }

        // ✅ validasi
        $validated = $request->validate([
            'nilai_1' => 'required|integer|min:0|max:5',
            'nilai_2' => 'required|integer|min:0|max:5',
            'nilai_3' => 'required|integer|min:0|max:5',
            'nilai_4' => 'required|integer|min:0|max:5',
            'nilai_5' => 'required|integer|min:0|max:5',
            'nilai_6' => 'required|integer|min:0|max:5',
            'nilai_7' => 'required|integer|min:0|max:5',
            'catatan' => 'nullable|string',
            'komentar' => 'nullable|string',
            'status' => 'nullable|in:disetujui,ditolak',
            'rekomendasi_biaya' => 'nullable|numeric|min:0',
        ], array_merge(
            \App\Helpers\PesanValidasi::pesan([
                'nilai_1' => 'Nilai kriteria 1',
                'nilai_2' => 'Nilai kriteria 2',
                'nilai_3' => 'Nilai kriteria 3',
                'nilai_4' => 'Nilai kriteria 4',
                'nilai_5' => 'Nilai kriteria 5',
                'nilai_6' => 'Nilai kriteria 6',
                'nilai_7' => 'Nilai kriteria 7',
                'catatan' => 'Catatan',
                'komentar' => 'Komentar',
                'status' => 'Keputusan review',
                'rekomendasi_biaya' => 'Rekomendasi biaya',
            ]),
            [
                'nilai_1.max' => 'Nilai kriteria 1 maksimal 5.',
                'nilai_2.max' => 'Nilai kriteria 2 maksimal 5.',
                'nilai_3.max' => 'Nilai kriteria 3 maksimal 5.',
                'nilai_4.max' => 'Nilai kriteria 4 maksimal 5.',
                'nilai_5.max' => 'Nilai kriteria 5 maksimal 5.',
                'nilai_6.max' => 'Nilai kriteria 6 maksimal 5.',
                'nilai_7.max' => 'Nilai kriteria 7 maksimal 5.',
            ]
        ));

        // ✅ bobot (sesuaikan kalau berbeda)
        $bobot = [5, 5, 5, 3, 5, 5, 10];

        $totalSkorMurni = 0;
        for ($i = 1; $i <= 7; $i++) {
            $skorMurni = $request->input("nilai_$i");
            $totalSkorMurni += ($skorMurni * $bobot[$i - 1]);
        }

        // maksimum murni = 190 (berdasarkan bobot di atas)
        $maxMurni = 190;

        // konversi ke skala 500
        $finalScore500 = round(($totalSkorMurni / $maxMurni) * 500);

        // simpan review
        Review::create([
            'proposal_id' => $proposal->id,
            'reviewer_id' => Auth::user()->id,
            'nilai_1' => $request->nilai_1,
            'nilai_2' => $request->nilai_2,
            'nilai_3' => $request->nilai_3,
            'nilai_4' => $request->nilai_4,
            'nilai_5' => $request->nilai_5,
            'nilai_6' => $request->nilai_6,
            'nilai_7' => $request->nilai_7,
            'total_score' => $finalScore500,
            // Form isi-review mengirim field `komentar` (bukan `catatan`);
            // simpan ke kolom `catatan` agar tampil di hasil review.
            'catatan' => $request->catatan ?? $request->komentar,
            // Keputusan review dari form (disetujui/ditolak); kolom
            // `status` ditambah migrasi 2026_11_26_140000.
            'status' => $request->status,
            'rekomendasi_biaya' => $request->rekomendasi_biaya,
        ]);

        // update status proposal
        $this->updateProposalStatus($proposal);

        $this->logActivity("Reviewer mengirim review untuk proposal ID {$id} (skor: {$finalScore500})");

        return redirect()->route('proposal.index')->with('success', 'Review berhasil dikirim dengan skor: '.$finalScore500);
    }

    /**
     * Update status proposal setelah review masuk
     */
    private function updateProposalStatus($proposal)
    {
        $jumlahReviewer = $proposal->reviewers->count();
        $jumlahReviewMasuk = Review::where('proposal_id', $proposal->id)->count();

        if ($jumlahReviewer > 0 && $jumlahReviewMasuk >= $jumlahReviewer) {
            $proposal->update(['status' => 'Review Selesai']);
        } else {
            $proposal->update(['status' => 'Sedang Direview']);
        }
    }
}
