<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
use App\Helpers\ParseJumlah;
use App\Models\ActivityLog;
use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\MonevFinding;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Skema;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProposalController extends Controller
{
    /**
     * Nama anggota yang dibuang resolveAnggota() karena ternyata ketua
     * proposal (B8): ketua tidak boleh tercatat sebagai anggota tim
     * proposalnya sendiri. Diisi ulang setiap kali resolveAnggota()
     * dipanggil; dibaca store()/update() untuk memberi tahu pengguna
     * lewat pesan flash, agar pembuangan itu tidak lagi diam-diam.
     *
     * @var array<int, string>
     */
    private array $anggotaKetuaDibuang = [];

    /**
     * Helper internal untuk mencatat log aktivitas pengguna
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
     * Periode hibah yang sedang berjalan (dipakai mengisi proposals.periode_id).
     */
    private function activePeriod(): ?HibahPeriod
    {
        return HibahPeriod::whereDate('start_date', '<=', now())
            ->whereDate('end_date', '>=', now())
            ->first();
    }

    /**
     * Status proposal yang masih boleh diubah lewat form edit
     * (edit/update). Proposal yang sudah Dikirim dan seterusnya
     * tidak boleh lagi diubah isinya — sebelumnya update() menerima
     * PUT untuk status apa pun selama pengirimnya pemilik, sehingga
     * isi proposal yang sedang diverifikasi/direview bisa diubah
     * diam-diam tanpa mengubah statusnya.
     */
    private const STATUS_DAPAT_DIEDIT = ['Draft', 'Revisi Administrasi'];

    /**
     * Aturan akses baca proposal (A1): ketua pengusul, anggota tim
     * (status keanggotaan Menunggu/Diterima — yang Ditolak sudah
     * bukan anggota lagi, selaras dengan ProposalMemberController@show),
     * reviewer yang ditugaskan pada proposal itu, dan staf
     * (verifikator, lppm, dekan, kepala_lppm, admin). Selain itu 403.
     * Dipakai download(), preview(), tinjau(), dan downloadReviewPdf().
     */
    private function dapatMengaksesProposal(Proposal $proposal, User $user): bool
    {
        if (in_array($user->role, ['admin', 'verifikator', 'lppm', 'dekan', 'kepala_lppm'], true)) {
            return true;
        }

        if ((int) $proposal->user_id === (int) $user->id) {
            return true;
        }

        if ($proposal->members()->where('user_id', $user->id)->whereIn('status_konfirmasi', ['Menunggu', 'Diterima'])->exists()) {
            return true;
        }

        return $proposal->reviewers()->where('proposal_reviewers.reviewer_id', $user->id)->exists();
    }

    /**
     * Role staf untuk halaman daftar monitoring (keputusan audit #10):
     * admin, verifikator, lppm, dekan, dan kepala_lppm melihat semua
     * proposal di halaman tersebut.
     */
    private const ROLE_STAF_MONITORING = ['admin', 'verifikator', 'lppm', 'dekan', 'kepala_lppm'];

    /**
     * Batasi query daftar monitoring menurut role (keputusan audit #10).
     * Staf (ROLE_STAF_MONITORING) melihat semua proposal; pengaju
     * hanya proposalnya sendiri — sebagai ketua, atau sebagai anggota
     * tim yang status konfirmasinya Menunggu/Diterima (selaras dengan
     * dapatMengaksesProposal); reviewer hanya proposal yang ditugaskan
     * kepadanya. Halaman yang murni antrean kerja staf (perluDireview,
     * proposalSedangDireview, daftarReview) tidak memakai helper ini —
     * pengaju ditolak 403 di sana.
     */
    private function batasiDaftarMonitoring($query)
    {
        $user = auth()->user();

        if (in_array($user->role, self::ROLE_STAF_MONITORING, true)) {
            return $query;
        }

        if ($user->role === 'pengaju') {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('user_id', $user->id)
                            ->whereIn('status_konfirmasi', ['Menunggu', 'Diterima']);
                    });
            });
        }

        return $query->whereHas('reviewers', function ($q) use ($user) {
            $q->where('proposal_reviewers.reviewer_id', $user->id);
        });
    }

    /**
     * Tahun filter yang aman untuk index()/dikirim(): hanya angka
     * 4 digit yang masuk akal; nilai lain kembali ke tahun berjalan.
     */
    private function tahunFilter(Request $request): int
    {
        $tahun = (int) $request->get('tahun', date('Y'));

        if ($tahun < 2000 || $tahun > 2100) {
            $tahun = (int) date('Y');
        }

        return $tahun;
    }

    /**
     * Nomor pengajuan sementara: EH-TAHUN-ID (berurutan dari ID proposal).
     *
     * TODO: Format resmi nomor pengajuan menunggu konfirmasi LPPM.
     * Jangan menganggap format ini final.
     */
    private function generateNoPengajuan(Proposal $proposal): string
    {
        return sprintf('EH-%d-%04d', $proposal->created_at->year, $proposal->id);
    }

    /**
     * Pesan validasi Bahasa Indonesia untuk alur pengajuan/edit proposal
     * (store + update). Locale aplikasi masih 'en' dan tidak ada
     * lang/id/validation.php, jadi pesan kustom ini yang dipakai —
     * selaras dengan pesan kustom di assignReviewer().
     * Aturan validasi tidak diubah, hanya bahasanya.
     *
     * @return array<string, string>
     */
    private function pesanValidasiPengajuan(): array
    {
        return [
            'judul.required' => 'Judul proposal wajib diisi.',
            'judul.string' => 'Judul proposal harus berupa teks.',
            'judul.max' => 'Judul proposal maksimal 255 karakter.',
            'nama_ketua.required' => 'Nama ketua wajib diisi.',
            'nama_ketua.string' => 'Nama ketua harus berupa teks.',
            'nama_ketua.max' => 'Nama ketua maksimal 255 karakter.',
            'fakultas_prodi.required' => 'Fakultas/program studi wajib dipilih.',
            'fakultas_prodi.exists' => 'Fakultas/program studi yang dipilih tidak valid.',
            'skema_id.required' => 'Skema hibah wajib dipilih.',
            'skema_id.exists' => 'Skema hibah yang dipilih tidak valid.',
            'file.required' => 'File proposal wajib diunggah.',
            'file.file' => 'File proposal harus berupa berkas.',
            'file.mimes' => 'File proposal harus berformat PDF, DOC, atau DOCX.',
            'file.max' => 'Ukuran file proposal maksimal 100 MB.',
            'rab.required' => 'RAB wajib diisi minimal satu komponen.',
            'rab.array' => 'RAB harus berupa daftar komponen.',
            'rab.min' => 'Isi minimal satu komponen RAB.',
            'rab.*.nama_komponen.required' => 'Nama komponen RAB wajib diisi.',
            'rab.*.nama_komponen.string' => 'Nama komponen RAB harus berupa teks.',
            'rab.*.nama_komponen.max' => 'Nama komponen RAB maksimal 255 karakter.',
            'rab.*.nama_komponen.in' => 'Nama komponen RAB tidak ada dalam daftar yang diizinkan.',
            'rab.*.jumlah.required' => 'Jumlah RAB wajib diisi.',
            'rab.*.jumlah.numeric' => 'Jumlah RAB harus berupa angka.',
            'rab.*.jumlah.min' => 'Jumlah RAB tidak boleh kurang dari 0.',
            'rab.*.keterangan.string' => 'Keterangan RAB harus berupa teks.',
            'rab.*.keterangan.max' => 'Keterangan RAB maksimal 500 karakter.',
        ];
    }

    /**
     * Buang baris RAB yang sepenuhnya kosong (nama, jumlah, dan
     * keterangan semuanya kosong) sebelum validasi (bug #7). Baris
     * seperti itu muncul dari klik "Tambah Komponen" yang belum diisi;
     * sebelumnya baris itu ikut divalidasi sehingga submit gagal
     * dengan "Nama komponen RAB wajib diisi." / "Jumlah RAB wajib
     * diisi.". Baris yang terisi sebagian tetap dipertahankan agar
     * divalidasi normal.
     */
    private function saringBarisRabKosong(Request $request): void
    {
        $rab = (array) $request->input('rab', []);

        $bersih = array_values(array_filter($rab, function ($item) {
            $item = is_array($item) ? $item : [];

            return trim((string) ($item['nama_komponen'] ?? '')) !== ''
                || trim((string) ($item['jumlah'] ?? '')) !== ''
                || trim((string) ($item['keterangan'] ?? '')) !== '';
        }));

        $request->merge(['rab' => $bersih]);
    }

    /**
     * Normalisasi jumlah RAB sebelum validasi (keputusan audit #15).
     * Nilai yang bisa dipahami ParseJumlah (notasi ilmiah, format
     * Indonesia '1.000.000', awalan 'Rp') diubah menjadi angka biasa
     * agar lolos aturan numeric|min:0; nilai yang tidak bisa dipahami
     * ('1,5 juta', 'satu juta') dibiarkan apa adanya supaya validasi
     * menolaknya dengan 'Jumlah RAB harus berupa angka.'. Dipanggil
     * di store()/update() tepat setelah saringBarisRabKosong() dan
     * sebelum validate(), sehingga total RAB, plafon, dan item yang
     * disimpan semuanya memakai nilai yang sama.
     */
    private function normalisasiJumlahRab(Request $request): void
    {
        $rab = (array) $request->input('rab', []);

        foreach ($rab as $i => $item) {
            if (! is_array($item) || ! array_key_exists('jumlah', $item)) {
                continue;
            }
            $parsed = ParseJumlah::parse($item['jumlah']);
            if ($parsed !== null) {
                $rab[$i]['jumlah'] = $parsed;
            }
        }

        $request->merge(['rab' => $rab]);
    }

    /**
     * Simpan berkas proposal yang baru diunggah ke disk LEBIH AWAL,
     * sebelum validasi dan cek pengajuan (bug #5). Sebelumnya berkas
     * baru disimpan di dalam transaksi di akhir store()/update(),
     * sehingga setiap redirect-back karena validasi / tanggungan /
     * plafon membuang berkas — browser tidak bisa mengisi ulang
     * input file — dan pengguna harus mengunggah ulang. Path hasil
     * penyimpanan awal dibawa lewat flash `file_tersimpan` (lihat
     * sertakanFileTersimpan()) dan dipakai ulang saat kirim ulang.
     *
     * Hanya berkas yang lolos aturan dasar (format PDF/DOC/DOCX,
     * maks 100 MB) yang disimpan; berkas yang tidak valid dibiarkan
     * gagal di $request->validate() dengan pesan Indonesianya.
     * Bila ada berkas tersimpan dari percobaan sebelumnya
     * (`file_tersimpan`) dan kini ada unggahan baru, berkas lama yang
     * tergantikan dihapus agar tidak menjadi orphan.
     *
     * @return string|null path berkas di disk 'public', atau null bila tidak ada unggahan baru yang valid
     */
    private function simpanFileUnggahanAwal(Request $request): ?string
    {
        if (! $request->hasFile('file') || ! $request->file('file')->isValid()) {
            return null;
        }

        $file = $request->file('file');
        $ekstensi = strtolower($file->getClientOriginalExtension());

        if (! in_array($ekstensi, ['pdf', 'doc', 'docx'], true) || $file->getSize() > 102400 * 1024) {
            return null;
        }

        $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '', (string) $request->input('judul', ''));
        $finalName = substr($cleanName, 0, 50).'_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ekstensi;
        $path = $file->storeAs('proposal_files', $finalName, 'public');

        $lama = $this->fileTersimpanSebelumnya($request);
        if ($lama && $lama !== $path) {
            Storage::disk('public')->delete($lama);
        }

        return $path;
    }

    /**
     * Path berkas dari percobaan submit sebelumnya (hidden input
     * `file_tersimpan`), bila berkasnya masih ada di disk. Path
     * dibatasi ke folder proposal_files agar nilai dari client tidak
     * bisa menunjuk berkas lain (bug #5).
     */
    private function fileTersimpanSebelumnya(Request $request): ?string
    {
        $path = trim((string) $request->input('file_tersimpan', ''));

        if ($path === '' || ! str_starts_with($path, 'proposal_files/') || str_contains($path, '..')) {
            return null;
        }

        if (! in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['pdf', 'doc', 'docx'], true)) {
            return null;
        }

        return Storage::disk('public')->exists($path) ? $path : null;
    }

    /**
     * Lampirkan path berkas yang sudah tersimpan ke redirect-back
     * (flash `file_tersimpan`) agar form menampilkannya dan kirim
     * ulang bisa memakai berkas itu tanpa unggah ulang (bug #5/#6).
     */
    private function sertakanFileTersimpan(\Illuminate\Http\RedirectResponse $redirect, ?string $filePathAwal): \Illuminate\Http\RedirectResponse
    {
        if ($filePathAwal) {
            $redirect->with('file_tersimpan', $filePathAwal);
        }

        return $redirect;
    }

    /**
     * Peringatan dinamis bila pengaju saat ini diblokir mengajukan
     * proposal baru (blacklist / tanggungan laporan) — ditampilkan di
     * form buat/edit sebagai pengganti spanduk statis saja (bug #6).
     * Kalimatnya sama dengan yang dikembalikan checkPengajuan(),
     * yang dikunci TanggunganTest / KuotaNolTanggunganTest.
     */
    private function peringatanTanggungan(User $user, ?int $exceptProposalId = null): ?string
    {
        if ($user->is_blacklisted) {
            return 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.';
        }

        if ($this->hasTanggunganLaporan($user->id, $exceptProposalId)) {
            return 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.';
        }

        return null;
    }

    /**
     * Selesaikan nama anggota (teks bebas dari form) menjadi user terdaftar.
     * Nama yang tidak cocok dengan akun dosen tetap dicatat (user_id null)
     * dan hanya tersimpan di kolom JSON `anggota` proposal.
     *
     * @return array<int, array{user_id: ?int, nama: string}>
     */
    private function resolveAnggota(array $names, ?int $ketuaId = null): array
    {
        $resolved = [];
        $seen = [];

        // Catatan pembuangan ketua dihitung ulang per panggilan.
        $this->anggotaKetuaDibuang = [];

        foreach ($names as $nama) {
            $nama = trim((string) $nama);
            if ($nama === '' || isset($seen[mb_strtolower($nama)])) {
                continue;
            }
            $seen[mb_strtolower($nama)] = true;

            $user = User::whereIn('role', ['pengaju', 'reviewer'])->where('name', $nama)->first();

            // Ketua tidak dicatat lagi sebagai anggota tim. Nama yang
            // dibuang dicatat agar store()/update() bisa memberi tahu
            // pengguna (B8) — sebelumnya pembuangan ini diam-diam.
            if ($user && $ketuaId && (int) $user->id === (int) $ketuaId) {
                $this->anggotaKetuaDibuang[] = $nama;

                continue;
            }

            $resolved[] = ['user_id' => $user?->id, 'nama' => $nama];
        }

        return $resolved;
    }

    /**
     * Kalimat catatan untuk pesan flash bila ada anggota yang dibuang
     * resolveAnggota() karena ia ketua proposal (B8). String kosong
     * bila tidak ada yang dibuang.
     */
    private function catatanAnggotaKetua(): string
    {
        if ($this->anggotaKetuaDibuang === []) {
            return '';
        }

        $nama = implode(', ', $this->anggotaKetuaDibuang);

        return ' Catatan: '.$nama.' adalah ketua proposal ini sehingga tidak dicatat sebagai anggota tim.';
    }

    /**
     * Hitung keikutsertaan seorang dosen pada satu skema (sebagai ketua
     * + sebagai anggota). Hanya proposal yang sudah dikirim yang dihitung;
     * draft belum menghabiskan kuota. Anggota lama (JSON `anggota` sebelum
     * Fase 2) tidak tercatat di proposal_members sehingga tidak terhitung.
     */
    private function countParticipation(int $userId, int $skemaId, ?int $exceptProposalId = null): int
    {
        $sebagaiKetua = Proposal::where('user_id', $userId)
            ->where('skema_id', $skemaId)
            ->where('status', '!=', 'Draft')
            ->when($exceptProposalId, fn ($q) => $q->where('id', '!=', $exceptProposalId))
            ->count();

        $sebagaiAnggota = ProposalMember::where('user_id', $userId)
            ->whereHas('proposal', function ($q) use ($skemaId, $exceptProposalId) {
                $q->where('skema_id', $skemaId)->where('status', '!=', 'Draft');
                if ($exceptProposalId) {
                    $q->where('id', '!=', $exceptProposalId);
                }
            })
            ->count();

        return $sebagaiKetua + $sebagaiAnggota;
    }

    /**
     * Cek apakah seorang dosen masih punya tanggungan laporan.
     *
     * Aturan awal (bisa diubah LPPM — jawaban LPPM atas pertanyaan
     * "aturan tanggungan" masih ditunggu): pengaju dianggap bertanggungan
     * bila punya proposal berstatus 'Disetujui' (kecuali
     * $exceptProposalId) yang (i) belum mengunggah laporan akhir
     * (kolom proposals.file_laporan_akhir null/kosong), ATAU (ii) masih
     * punya temuan Monev berstatus Open atau In_Review (tabel
     * monev_findings, kolom status_temuan).
     *
     * Catatan: keputusan() menulis kolom status dan status_pendanaan
     * dengan nilai yang sama, jadi cukup cek kolom status.
     */
    private function hasTanggunganLaporan(int $userId, ?int $exceptProposalId = null): bool
    {
        $proposalIds = Proposal::where('user_id', $userId)
            ->where('status', 'Disetujui')
            ->when($exceptProposalId, fn ($q) => $q->where('id', '!=', $exceptProposalId))
            ->pluck('id');

        if ($proposalIds->isEmpty()) {
            return false;
        }

        // (i) Ada proposal Disetujui yang laporan akhirnya belum diunggah.
        $belumLapor = Proposal::whereIn('id', $proposalIds)
            ->where(function ($q) {
                $q->whereNull('file_laporan_akhir')->orWhere('file_laporan_akhir', '');
            })
            ->exists();

        if ($belumLapor) {
            return true;
        }

        // (ii) Masih ada temuan Monev berstatus Open atau In_Review.
        return MonevFinding::whereIn('proposal_id', $proposalIds)
            ->whereIn('status_temuan', ['Open', 'In_Review'])
            ->exists();
    }

    /**
     * Cek aturan pengajuan untuk satu skema: blacklist + tanggungan
     * laporan + kuota. Mengembalikan pesan error, atau null bila boleh
     * dilanjutkan.
     *
     * Cek tanggungan sudah diimplementasikan (Fase 5, story 12):
     * pengusul/anggota yang punya proposal 'Disetujui' tanpa laporan
     * akhir atau dengan temuan Monev Open/In_Review tidak boleh
     * mengajukan baru. Aturan awalnya ada di hasTanggunganLaporan().
     */
    private function checkPengajuan(User $user, Skema $skema, array $memberUserIds, ?int $exceptProposalId = null): ?string
    {
        if ($user->is_blacklisted) {
            return 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.';
        }

        if ($this->hasTanggunganLaporan($user->id, $exceptProposalId)) {
            return 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.';
        }

        // Kuota 0 berarti batas belum ditetapkan — lewati pengecekan
        // kuota SAJA. Cek blacklist + tanggungan anggota di bawah tetap
        // berjalan (sebelumnya early-return di sini membuat keduanya
        // terlewat bila kuota skema = 0).
        $cekKuota = (int) $skema->kuota > 0;

        if ($cekKuota && $this->countParticipation($user->id, $skema->id, $exceptProposalId) >= (int) $skema->kuota) {
            return "Anda sudah mencapai kuota skema {$skema->label()} ({$skema->kuota} keikutsertaan).";
        }

        foreach ($memberUserIds as $memberId) {
            $member = User::find($memberId);
            if (! $member) {
                continue;
            }
            if ($member->is_blacklisted) {
                return "Anggota {$member->name} ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat diikutsertakan.";
            }
            if ($this->hasTanggunganLaporan($member->id, $exceptProposalId)) {
                return "Anggota {$member->name} masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat diikutsertakan.";
            }
            if ($cekKuota && $this->countParticipation($member->id, $skema->id, $exceptProposalId) >= (int) $skema->kuota) {
                return "Anggota {$member->name} sudah mencapai kuota skema {$skema->label()} ({$skema->kuota} keikutsertaan).";
            }
        }

        return null;
    }

    /**
     * Cek batas ukuran tim skema menurut angka resmi LPPM (27 Nov 2026).
     *
     * Sistem hanya memodelkan 1 ketua (proposals.user_id) + anggota,
     * tanpa konsep ketua ganda — jadi yang dihitung adalah total ukuran
     * tim = 1 ketua + jumlah anggota. Kompetisi maks 3 orang (2 ketua +
     * 1 anggota atau sebaliknya, total 3); Khusus maks 1 orang (hanya
     * ketua, tanpa anggota). Pembinaan belum ditetapkan LPPM sehingga
     * tidak dibatasi (maksUkuranTim() null).
     *
     * Hanya dipanggil saat submit/kirim pengajuan, bukan saat simpan
     * draft — selaras dengan validasi plafon.
     *
     * @param  int  $jumlahAnggota  jumlah nama anggota yang diselesaikan (resolveAnggota)
     */
    private function cekBatasTim(Skema $skema, int $jumlahAnggota): ?string
    {
        $maks = $skema->maksUkuranTim();

        if ($maks === null) {
            return null;
        }

        $ukuranTim = 1 + $jumlahAnggota;

        if ($ukuranTim <= $maks) {
            return null;
        }

        if ($skema->kategori === 'Khusus') {
            return "Skema Khusus hanya boleh diajukan oleh 1 orang (ketua saja, tanpa anggota). Tim saat ini {$ukuranTim} orang.";
        }

        return "Skema {$skema->label()} maksimal {$maks} orang per tim (ketua + anggota). Tim saat ini {$ukuranTim} orang.";
    }

    /**
     * Simpan ulang item RAB dari input form. Mengembalikan total RAB.
     */
    private function saveRabItems(Proposal $proposal, array $rabInput): float
    {
        $proposal->rabItems()->delete();

        $total = 0;
        foreach ($rabInput as $item) {
            $nama = trim((string) ($item['nama_komponen'] ?? ''));
            $jumlah = ParseJumlah::parse($item['jumlah'] ?? 0) ?? 0;
            if ($nama === '' && $jumlah <= 0) {
                continue;
            }

            $proposal->rabItems()->create([
                'nama_komponen' => $nama !== '' ? $nama : 'Komponen RAB',
                'jumlah' => $jumlah,
                'keterangan' => $item['keterangan'] ?? null,
            ]);
            $total += $jumlah;
        }

        return $total;
    }

    /**
     * Sinkronkan anggota tim dari daftar nama yang sudah diselesaikan
     * (resolveAnggota). Baris yang sudah ada dipertahankan status
     * konfirmasinya; baris yang tidak lagi tercantum dihapus.
     */
    private function syncMembers(Proposal $proposal, array $anggotaResolved): void
    {
        $desiredUserIds = array_values(array_filter(array_column($anggotaResolved, 'user_id')));
        $existingIds = $proposal->members()->whereNotNull('user_id')->pluck('user_id')->all();

        // Hapus anggota yang tidak lagi tercantum. Baris tanpa user
        // (anggota tanpa akun) hanya tercatat di kolom JSON `anggota`,
        // jadi selalu dibuat ulang dari daftar nama saat ini.
        $proposal->members()->whereNotNull('user_id')->whereNotIn('user_id', $desiredUserIds)->delete();
        $proposal->members()->whereNull('user_id')->delete();

        foreach ($desiredUserIds as $userId) {
            if (! in_array($userId, $existingIds)) {
                $proposal->members()->create([
                    'user_id' => $userId,
                    'peran' => 'Anggota',
                    'status_konfirmasi' => 'Menunggu',
                ]);
            }
        }
    }

    /**
     * Kirim undangan keanggotaan ke semua anggota yang statusnya masih
     * 'Menunggu'. Sudah diundang sebelumnya tidak dikirim ulang.
     */
    private function sendMemberInvitations(Proposal $proposal): void
    {
        $members = $proposal->members()
            ->where('status_konfirmasi', 'Menunggu')
            ->whereNotNull('user_id')
            ->get();

        foreach ($members as $member) {
            $sudahDiundang = \App\Models\Notification::where('user_id', $member->user_id)
                ->where('proposal_id', $proposal->id)
                ->where('title', 'Undangan Anggota Tim Proposal')
                ->exists();

            if ($sudahDiundang) {
                continue;
            }

            NotificationHelper::send(
                (int) $member->user_id,
                'Undangan Anggota Tim Proposal',
                'Anda diundang menjadi anggota tim proposal "'.($proposal->judul ?? '-').'" oleh '.($proposal->nama_ketua ?? '-').'. Buka menu Undangan Keanggotaan di dasbor untuk konfirmasi.',
                'info',
                $proposal->id
            );
        }
    }

    /**
     * Menampilkan form pengajuan proposal baru.
     * (Route GET /proposal/create — dulu method ini tidak ada, route 500.)
     */
    public function create()
    {
        if (! in_array(auth()->user()->role, ['pengaju', 'reviewer'])) {
            abort(403, 'Anda tidak memiliki izin untuk mengajukan proposal.');
        }

        $list_fakultas = \App\Models\Fakultas::all();
        $list_skema = \App\Models\Skema::orderBy('kategori')->orderBy('bidang')->get();
        $all_dosen = \App\Models\User::whereIn('role', ['pengaju', 'reviewer'])
            ->orderBy('name')
            ->get();
        // Pilihan nama komponen RAB: bawaan + kustom dari settings
        // (key 'rab_komponen_kustom'); dipakai datalist di form.
        $daftarKomponenRab = Setting::daftarKomponenRab();
        // Peringatan dinamis bila pengaju diblokir tanggungan/blacklist
        // (bug #6): tampil di form, bukan hanya spanduk statis.
        $peringatanTanggungan = $this->peringatanTanggungan(auth()->user());

        return view('proposal.create', compact('list_fakultas', 'list_skema', 'all_dosen', 'daftarKomponenRab', 'peringatanTanggungan'));
    }

    /**
     * Halaman pengumuman pendanaan — daftar proposal yang disetujui.
     * (Route GET /pengumuman-pendanaan — dulu method ini tidak ada, route 500.)
     */
    public function pengumumanPendanaan()
    {
        $proposals = \App\Models\Proposal::with('user')
            ->where('status_pendanaan', 'Disetujui')
            ->latest()
            ->get();

        return view('proposal.pengumuman', compact('proposals'));
    }

    /**
     * Halaman cek kuota dosen — keikutsertaan per kategori skema
     * (sebagai ketua atau anggota) dibanding kuota skema tersebut.
     * Hanya proposal yang sudah dikirim yang dihitung; draft tidak
     * menghabiskan kuota.
     */
    public function cekKuota()
    {
        $user = auth()->user();

        $kuotaPerSkema = Skema::orderBy('kategori')->orderBy('bidang')->get()->map(function ($skema) use ($user) {
            $sebagaiKetua = Proposal::where('user_id', $user->id)
                ->where('skema_id', $skema->id)
                ->where('status', '!=', 'Draft')
                ->count();
            $sebagaiAnggota = ProposalMember::where('user_id', $user->id)
                ->whereHas('proposal', function ($q) use ($skema) {
                    $q->where('skema_id', $skema->id)->where('status', '!=', 'Draft');
                })
                ->count();

            return [
                'skema' => $skema,
                'sebagaiKetua' => $sebagaiKetua,
                'sebagaiAnggota' => $sebagaiAnggota,
                'total' => $sebagaiKetua + $sebagaiAnggota,
                'kuota' => (int) $skema->kuota,
                'sisa' => max(0, (int) $skema->kuota - $sebagaiKetua - $sebagaiAnggota),
            ];
        });

        return view('proposal.cek-kuota', compact('user', 'kuotaPerSkema'));
    }

    /**
     * Menampilkan daftar proposal & Dropdown Fakultas
     */
    public function index(Request $request)
    {
        $tahun = $this->tahunFilter($request);
        $user = auth()->user();
        $role = $user->role;

        $list_fakultas = \App\Models\Fakultas::all();
        $all_dosen = \App\Models\User::whereIn('role', ['pengaju', 'reviewer'])->get();

        // --- LOGIKA MASA HIBAH ---
        $now = now();
        $isMasaHibah = \Illuminate\Support\Facades\DB::table('hibah_periods')
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->exists();
        // -------------------------

        $query = \App\Models\Proposal::with(['fakultas', 'user', 'skema'])
            ->whereYear('created_at', $tahun);

        if ($role === 'pengaju' || $role === 'reviewer') {
            $query->where('user_id', $user->id);
        }

        $proposals = $query->latest()->get();

        // Pastikan semua variabel dipanggil dengan benar di compact()
        return view('proposal.daftar_proposal', compact(
            'proposals',
            'tahun',
            'role',
            'list_fakultas',
            'all_dosen',
            'isMasaHibah'
        ));
    }

    /**
     * Menyimpan proposal baru (draft atau diajukan).
     * Draft boleh dilengkapi bertahap; submit wajib lengkap dan lolos
     * cek kuota per skema + blacklist + plafon RAB.
     */
    public function store(Request $request)
    {
        // 1. Proteksi Role
        if (! in_array(auth()->user()->role, ['pengaju', 'reviewer'])) {
            abort(403, 'Anda tidak memiliki izin untuk mengunggah proposal.');
        }

        $user = auth()->user();
        $aksi = $request->input('aksi', 'submit');
        $isDraft = $aksi === 'draft';

        // Berkas yang baru diunggah disimpan ke disk LEBIH AWAL (bug #5),
        // sebelum validasi dan cek pengajuan, agar tidak hilang bila
        // request kemudian gagal dan form di-reload.
        $filePathAwal = $this->simpanFileUnggahanAwal($request);

        // 2. Akun yang ditandai memiliki tanggungan tidak boleh mengajukan baru.
        if ($user->is_blacklisted) {
            return $this->sertakanFileTersimpan(
                redirect()->back()->withInput()
                    ->with('error', 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.'),
                $filePathAwal
            );
        }

        // Baris RAB yang sepenuhnya kosong diabaikan sebelum validasi (bug #7).
        $this->saringBarisRabKosong($request);

        // Jumlah RAB dinormalisasi sebelum validasi (keputusan audit
        // #15): '1e5' / '1.000.000' / 'Rp 1.500.000' menjadi angka.
        $this->normalisasiJumlahRab($request);

        // 3. Validasi form (draft boleh dilengkapi bertahap, submit harus lengkap).
        // Nama komponen RAB harus ada di daftar bawaan + kustom (settings
        // key 'rab_komponen_kustom'); bila key belum ada, daftar kustom kosong.
        // Bila berkas sudah tersimpan dari percobaan sebelumnya
        // (`file_tersimpan`), unggahan baru tidak wajib diulang (bug #5).
        $komponenRab = Setting::daftarKomponenRab();
        $fileSudahTersimpan = $this->fileTersimpanSebelumnya($request);
        try {
            if ($isDraft) {
                $request->validate([
                    'judul' => 'required|string|max:255',
                    'nama_ketua' => 'nullable|string|max:255',
                    'fakultas_prodi' => 'nullable|exists:fakultas,id',
                    'skema_id' => 'nullable|exists:skemas,id',
                    'file' => 'nullable|file|mimes:pdf,doc,docx|max:102400',
                    'anggota' => 'nullable|array',
                    'rab' => 'nullable|array',
                    'rab.*.nama_komponen' => ['nullable', 'string', 'max:255', Rule::in($komponenRab)],
                    'rab.*.jumlah' => 'nullable|numeric|min:0',
                    'rab.*.keterangan' => 'nullable|string|max:500',
                ], $this->pesanValidasiPengajuan());
            } else {
                $request->validate([
                    'judul' => 'required|string|max:255',
                    'nama_ketua' => 'required|string|max:255',
                    'fakultas_prodi' => 'required|exists:fakultas,id',
                    'skema_id' => 'required|exists:skemas,id',
                    'file' => ($fileSudahTersimpan ? 'nullable' : 'required').'|file|mimes:pdf,doc,docx|max:102400', // Maks 100 MB
                    'anggota' => 'nullable|array',
                    'rab' => 'required|array|min:1',
                    'rab.*.nama_komponen' => ['required', 'string', 'max:255', Rule::in($komponenRab)],
                    'rab.*.jumlah' => 'required|numeric|min:0',
                    'rab.*.keterangan' => 'nullable|string|max:500',
                ], $this->pesanValidasiPengajuan());
            }
        } catch (ValidationException $e) {
            // Validasi gagal: bawa berkas yang sudah tersimpan agar form
            // menampilkannya dan kirim ulang tidak perlu unggah ulang
            // (bug #5). Berkas yang gagal validasi format/ukuran tidak
            // ikut dibawa — pengguna harus memilih berkas yang valid.
            $redirect = redirect()->back()->withInput()->withErrors($e->errors());
            if ($filePathAwal && ! isset($e->errors()['file'])) {
                $redirect->with('file_tersimpan', $filePathAwal);
            }

            return $redirect;
        }

        $skema = $request->filled('skema_id') ? Skema::find($request->skema_id) : null;

        // 4. Selesaikan nama anggota menjadi user (bila terdaftar).
        $anggotaInput = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), (array) $request->input('anggota', []))
        ));
        $anggotaResolved = $this->resolveAnggota($anggotaInput, $user->id);
        $memberUserIds = array_values(array_filter(array_column($anggotaResolved, 'user_id')));

        // 5. Cek kuota per skema + blacklist anggota + batas ukuran tim
        // (hanya saat submit). Gagal di sini tetap membawa berkas yang
        // sudah tersimpan (bug #5/#6).
        if (! $isDraft && $skema) {
            $error = $this->checkPengajuan($user, $skema, $memberUserIds);
            if ($error) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', $error), $filePathAwal);
            }
            if ($error = $this->cekBatasTim($skema, count($anggotaResolved))) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', $error), $filePathAwal);
            }
        }

        // 6. Hitung total RAB dan validasi terhadap plafon skema (hanya saat submit).
        $rabTotal = 0;
        foreach ((array) $request->input('rab', []) as $item) {
            $rabTotal += ParseJumlah::parse($item['jumlah'] ?? 0) ?? 0;
        }
        if (! $isDraft && $skema && $skema->plafon > 0 && $rabTotal > (float) $skema->plafon) {
            return $this->sertakanFileTersimpan(
                redirect()->back()->withInput()
                    ->with('error', 'Total RAB Rp '.number_format($rabTotal, 0, ',', '.').' melebihi plafon skema '.$skema->label().' (Rp '.number_format((float) $skema->plafon, 0, ',', '.').').'),
                $filePathAwal
            );
        }

        // Pengajuan wajib punya nilai RAB lebih dari 0: RAB yang semua
        // komponennya 0 sebelumnya lolos (validasi hanya min:0 per
        // komponen) sehingga proposal bernilai Rp0 bisa terkirim.
        if (! $isDraft && $rabTotal <= 0) {
            return $this->sertakanFileTersimpan(
                redirect()->back()->withInput()
                    ->with('error', 'Total RAB harus lebih dari 0. Isi minimal satu komponen RAB dengan jumlah lebih dari 0.'),
                $filePathAwal
            );
        }

        try {
            $proposal = DB::transaction(function () use ($request, $user, $isDraft, $skema, $anggotaResolved, $rabTotal, $filePathAwal) {
                // Berkas sudah disimpan di awal request (bug #5); bila
                // tidak ada unggahan baru, pakai berkas dari percobaan
                // submit sebelumnya (hidden input `file_tersimpan`).
                // Upload File (wajib saat submit, opsional saat draft).
                $filePath = $filePathAwal ?? $this->fileTersimpanSebelumnya($request);

                $proposal = Proposal::create([
                    'judul' => $request->judul,
                    'nama_ketua' => $request->nama_ketua ?: $user->name,
                    'fakultas_prodi' => $request->fakultas_prodi,
                    'file_path' => $filePath,
                    'anggota' => array_column($anggotaResolved, 'nama'),
                    'biaya' => $rabTotal,
                    'status' => $isDraft ? 'Draft' : 'Dikirim',
                    'user_id' => $user->id,
                    'skema_id' => $skema?->id,
                    'periode_id' => $this->activePeriod()?->id,
                ]);

                $this->saveRabItems($proposal, (array) $request->input('rab', []));
                $this->syncMembers($proposal, $anggotaResolved);

                if (! $isDraft) {
                    $proposal->update(['no_pengajuan' => $this->generateNoPengajuan($proposal)]);
                    $this->sendMemberInvitations($proposal);
                    NotificationController::notifyProposalSubmitted($user->id);
                    $this->logActivity("Mengajukan proposal ID {$proposal->id} (status: Dikirim)");
                }

                return $proposal;
            });

            if ($isDraft) {
                return redirect()->route('proposal.index')
                    ->with('success', 'Draft proposal berhasil disimpan. Anda dapat melengkapinya kembali sebelum dikirim.'.$this->catatanAnggotaKetua());
            }

            return redirect()->route('proposal.index')
                ->with('success', 'Proposal berhasil diajukan! Nomor pengajuan: '.$proposal->no_pengajuan.$this->catatanAnggotaKetua());
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
        }
    }

    /**
     * Halaman Edit Proposal
     */
    public function edit($id)
    {
        // 1. Ambil data proposal yang akan diedit
        $proposal = \App\Models\Proposal::with(['rabItems', 'members', 'skema'])->findOrFail($id);

        // Hanya pemilik proposal atau admin yang boleh membukanya.
        // auth()->id() = username, bukan users.id — pakai auth()->user()->id.
        if (auth()->user()->role !== 'admin' && (int) $proposal->user_id !== (int) auth()->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah proposal ini.');
        }

        // Hanya Draft dan Revisi Administrasi yang boleh dibuka untuk
        // diubah; proposal yang sudah Dikirim dan seterusnya tidak
        // bisa lagi diedit isinya (lihat STATUS_DAPAT_DIEDIT).
        if (! in_array($proposal->status, self::STATUS_DAPAT_DIEDIT, true)) {
            return redirect()->back()->with('error', 'Proposal dengan status '.$proposal->status.' sudah tidak bisa diubah.');
        }

        // 2. Ambil data dosen/pengaju (untuk list anggota di datalist) —
        // selaras dengan form buat proposal (hanya pengaju + reviewer
        // yang bisa menjadi anggota tim).
        $users = \App\Models\User::whereIn('role', ['pengaju', 'reviewer'])
            ->orderBy('name')
            ->get();
        $list_skema = Skema::orderBy('kategori')->orderBy('bidang')->get();
        // Pilihan nama komponen RAB: bawaan + kustom dari settings
        // (key 'rab_komponen_kustom'); dipakai datalist di form.
        $daftarKomponenRab = Setting::daftarKomponenRab();

        // 3. Cek Masa Hibah (Keamanan)
        $now = now();
        $isMasaHibah = \Illuminate\Support\Facades\DB::table('hibah_periods')
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->exists();

        if (! $isMasaHibah) {
            return redirect()->back()->with('error', 'Masa pengeditan proposal telah berakhir.');
        }

        // Peringatan dinamis bila pengaju diblokir tanggungan/blacklist
        // (bug #6) — hanya relevan bila proposal masih bisa dikirim.
        $user = auth()->user();
        $peringatanTanggungan = in_array($proposal->status, ['Draft', 'Revisi Administrasi'], true)
            ? $this->peringatanTanggungan($user, (int) $proposal->id)
            : null;

        // 4. Kirim semua variabel ke view
        return view('proposal.edit-proposal', compact('proposal', 'users', 'list_skema', 'daftarKomponenRab', 'peringatanTanggungan'));
    }

    /**
     * Update Data Proposal (lengkapi draft, kirim draft, atau revisi).
     */
    public function update(Request $request, $id)
    {
        $proposal = \App\Models\Proposal::with('members')->findOrFail($id);

        // Hanya pemilik proposal atau admin yang boleh mengubahnya.
        // auth()->id() = username, bukan users.id — pakai auth()->user()->id.
        if (auth()->user()->role !== 'admin' && (int) $proposal->user_id !== (int) auth()->user()->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah proposal ini.');
        }

        // Sama seperti edit(): hanya Draft dan Revisi Administrasi
        // yang boleh diubah. Sebelumnya PUT ke proposal Dikirim /
        // Sedang Direview / Disetujui tetap mengubah judul, berkas,
        // RAB, anggota, dan skema tanpa mengubah statusnya.
        if (! in_array($proposal->status, self::STATUS_DAPAT_DIEDIT, true)) {
            return redirect()->back()->withInput()->with('error', 'Proposal dengan status '.$proposal->status.' sudah tidak bisa diubah.');
        }

        // 3. Cek Masa Hibah (Keamanan) — sama seperti edit():
        // update di luar masa hibah ditolak.
        $now = now();
        $isMasaHibah = \Illuminate\Support\Facades\DB::table('hibah_periods')
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->exists();

        if (! $isMasaHibah) {
            return redirect()->back()->withInput()->with('error', 'Masa pengeditan proposal telah berakhir.');
        }

        $user = auth()->user();
        $aksi = $request->input('aksi', 'submit');
        // Kirim ulang hasil Revisi Administrasi dihitung sebagai pengajuan juga,
        // supaya proposal kembali ke antrean verifikasi (status Dikirim).
        $kirimPengajuan = $aksi === 'submit' && in_array($proposal->status, ['Draft', 'Revisi Administrasi']);

        // Berkas baru disimpan ke disk LEBIH AWAL (bug #5), sebelum
        // validasi dan cek pengajuan, agar tidak hilang bila request
        // kemudian gagal dan form di-reload.
        $filePathBaru = $this->simpanFileUnggahanAwal($request);

        // Baris RAB yang sepenuhnya kosong diabaikan sebelum validasi (bug #7).
        $this->saringBarisRabKosong($request);

        // Jumlah RAB dinormalisasi sebelum validasi (keputusan audit
        // #15): '1e5' / '1.000.000' / 'Rp 1.500.000' menjadi angka.
        $this->normalisasiJumlahRab($request);

        // Validasi: draft boleh dilengkapi bertahap; pengiriman dan revisi harus lengkap.
        // Nama komponen RAB harus ada di daftar bawaan + kustom (settings
        // key 'rab_komponen_kustom'); bila key belum ada, daftar kustom kosong.
        try {
            $request->validate([
                'judul' => 'required|string|max:255',
                'nama_ketua' => 'required|string|max:255',
                'fakultas_prodi' => 'required|exists:fakultas,id',
                'skema_id' => 'nullable|exists:skemas,id',
                'file' => 'nullable|file|mimes:pdf,doc,docx|max:102400',
                'anggota' => 'nullable|array',
                'rab' => 'nullable|array',
                'rab.*.nama_komponen' => ['nullable', 'string', 'max:255', Rule::in(Setting::daftarKomponenRab())],
                'rab.*.jumlah' => 'nullable|numeric|min:0',
                'rab.*.keterangan' => 'nullable|string|max:500',
            ], $this->pesanValidasiPengajuan());
        } catch (ValidationException $e) {
            // Validasi gagal: berkas lama tetap tertaut di proposal
            // (tidak disentuh), dan berkas baru yang sudah tersimpan
            // dibawa lewat flash agar tidak perlu diunggah ulang
            // (bug #5). Berkas yang gagal validasi format/ukuran
            // tidak ikut dibawa.
            $redirect = redirect()->back()->withInput()->withErrors($e->errors());
            if ($filePathBaru && ! isset($e->errors()['file'])) {
                $redirect->with('file_tersimpan', $filePathBaru);
            }

            return $redirect;
        }

        $skema = $request->filled('skema_id') ? Skema::find($request->skema_id) : $proposal->skema;

        $anggotaInput = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), (array) $request->input('anggota', []))
        ));
        $anggotaResolved = $this->resolveAnggota($anggotaInput, (int) $proposal->user_id);
        $memberUserIds = array_values(array_filter(array_column($anggotaResolved, 'user_id')));

        // Saat draft dikirim: berlaku semua aturan pengajuan.
        // Gagal di sini tetap membawa berkas yang sudah tersimpan (bug #5/#6).
        if ($kirimPengajuan) {
            if ($user->is_blacklisted) {
                return $this->sertakanFileTersimpan(
                    redirect()->back()->withInput()
                        ->with('error', 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.'),
                    $filePathBaru
                );
            }
            if (! $skema) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', 'Pilih skema hibah terlebih dahulu sebelum mengirim pengajuan.'), $filePathBaru);
            }
            // Pesan disamakan dengan pesan validasi server
            // 'File proposal wajib diunggah.' (bug #4d); berkas bisa
            // berasal dari proposal, unggahan baru, atau percobaan
            // sebelumnya (`file_tersimpan`).
            if (! $proposal->file_path && ! $filePathBaru && ! $this->fileTersimpanSebelumnya($request)) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', 'File proposal wajib diunggah.'), $filePathBaru);
            }
            // Proposal yang dikirim ulang tidak dihitung sebagai keikutsertaan baru.
            $error = $this->checkPengajuan($user, $skema, $memberUserIds, (int) $proposal->id);
            if ($error) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', $error), $filePathBaru);
            }
            if ($error = $this->cekBatasTim($skema, count($anggotaResolved))) {
                return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', $error), $filePathBaru);
            }
        }

        // Total RAB dari input form (bila ada); bila tidak ada, biaya lama dipertahankan.
        $rabInput = (array) $request->input('rab', []);
        $rabTotal = 0;
        $hasRab = false;
        foreach ($rabInput as $item) {
            $namaItem = trim((string) ($item['nama_komponen'] ?? ''));
            $jumlahItem = ParseJumlah::parse($item['jumlah'] ?? 0) ?? 0;
            if ($namaItem !== '' || $jumlahItem > 0) {
                $hasRab = true;
            }
            $rabTotal += $jumlahItem;
        }

        if ($kirimPengajuan && ! $hasRab) {
            return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', 'Isi minimal satu komponen RAB sebelum mengirim pengajuan.'), $filePathBaru);
        }
        // RAB yang semua komponennya 0 juga ditolak (selaras dengan store()).
        if ($kirimPengajuan && $hasRab && $rabTotal <= 0) {
            return $this->sertakanFileTersimpan(redirect()->back()->withInput()->with('error', 'Total RAB harus lebih dari 0. Isi minimal satu komponen RAB dengan jumlah lebih dari 0.'), $filePathBaru);
        }
        // Plafon hanya dicek saat kirim pengajuan, bukan saat simpan draft —
        // selaras dengan store() (bug #7: simpan draft di form edit dulu
        // menolak RAB di atas plafon karena cek ini tidak dijaga $kirimPengajuan).
        if ($kirimPengajuan && $hasRab && $skema && $skema->plafon > 0 && $rabTotal > (float) $skema->plafon) {
            return $this->sertakanFileTersimpan(
                redirect()->back()->withInput()
                    ->with('error', 'Total RAB Rp '.number_format($rabTotal, 0, ',', '.').' melebihi plafon skema '.$skema->label().' (Rp '.number_format((float) $skema->plafon, 0, ',', '.').').'),
                $filePathBaru
            );
        }

        DB::transaction(function () use ($request, $proposal, $user, $kirimPengajuan, $skema, $anggotaResolved, $rabInput, $rabTotal, $hasRab, $filePathBaru) {
            // Handle File: berkas baru sudah disimpan di awal request
            // (bug #5); bila tidak ada unggahan baru, pakai berkas dari
            // percobaan sebelumnya (`file_tersimpan`). Berkas lama
            // dihapus hanya setelah berkas pengganti pasti ada, dan
            // bila tidak ada pengganti, berkas lama tetap dipertahankan.
            $filePathDipakai = $filePathBaru ?? $this->fileTersimpanSebelumnya($request);
            if ($filePathDipakai) {
                if ($proposal->file_path && $proposal->file_path !== $filePathDipakai) {
                    Storage::disk('public')->delete($proposal->file_path);
                }
                $proposal->file_path = $filePathDipakai;
            }

            $data = [
                'judul' => $request->judul,
                'nama_ketua' => $request->nama_ketua,
                'fakultas_prodi' => $request->fakultas_prodi,
                'anggota' => array_column($anggotaResolved, 'nama'),
                'skema_id' => $skema?->id,
            ];
            if ($hasRab) {
                $data['biaya'] = $rabTotal;
            }

            // Status logic
            if ($kirimPengajuan) {
                $data['status'] = 'Dikirim';
                $data['no_pengajuan'] = $this->generateNoPengajuan($proposal);
            }

            $proposal->update($data);

            if ($hasRab) {
                $this->saveRabItems($proposal, $rabInput);
            }
            $this->syncMembers($proposal, $anggotaResolved);

            if ($kirimPengajuan) {
                $this->sendMemberInvitations($proposal);
                NotificationController::notifyProposalSubmitted($user->id);
                $this->logActivity("Mengirim proposal ID {$proposal->id} (status: Dikirim)");
            }
        });

        if ($kirimPengajuan) {
            return redirect()->route('monitoring.proposalDikirim')
                ->with('success', 'Proposal berhasil diajukan! Nomor pengajuan: '.$proposal->fresh()->no_pengajuan.$this->catatanAnggotaKetua());
        }

        return redirect()->route('monitoring.proposalDikirim')->with('success', 'Proposal diperbarui!'.$this->catatanAnggotaKetua());
    }

    /**
     * Kirim proposal Draft / Revisi Administrasi langsung dari daftar
     * (tombol "Kirim" di /proposal). Sebelumnya pengajuan hanya bisa
     * lewat form edit, dan tombol "Simpan & Kirim" di form edit tampil
     * juga untuk status yang tidak bisa dikirim — sehingga tombol Kirim
     * terlihat tidak konsisten. Semua pemeriksaan pengiriman di sini
     * sama dengan jalur update() (skema, berkas, RAB, plafon, kuota,
     * blacklist/tanggungan), hanya tanpa mengharuskan isi ulang form.
     */
    public function submit($id)
    {
        $proposal = \App\Models\Proposal::with('members')->findOrFail($id);
        $user = auth()->user();

        // Hanya pemilik proposal yang boleh mengirimkannya.
        if ((int) $proposal->user_id !== (int) $user->id) {
            abort(403, 'Anda tidak memiliki izin untuk mengirim proposal ini.');
        }

        // Hanya Draft dan Revisi Administrasi yang bisa dikirim.
        if (! in_array($proposal->status, ['Draft', 'Revisi Administrasi'], true)) {
            return back()->with('error', 'Proposal ini sudah tidak bisa dikirim (status: '.$proposal->status.').');
        }

        if (! $proposal->skema) {
            return back()->with('error', 'Pilih skema hibah terlebih dahulu sebelum mengirim pengajuan.');
        }

        if (! $proposal->file_path) {
            // Disamakan dengan pesan file di pesanValidasiPengajuan() (bug #4):
            // "File proposal wajib diunggah." — tidak dikunci test lama.
            return back()->with('error', 'File proposal wajib diunggah.');
        }

        $memberUserIds = $proposal->members()->whereNotNull('user_id')->pluck('user_id')->all();

        if ($error = $this->checkPengajuan($user, $proposal->skema, $memberUserIds, (int) $proposal->id)) {
            return back()->with('error', $error);
        }

        // Jumlah anggota = nama di kolom JSON `anggota` (sudah mencakup
        // anggota tanpa akun); bila kosong, pakai baris proposal_members.
        $jumlahAnggota = count((array) $proposal->anggota);
        if ($jumlahAnggota === 0) {
            $jumlahAnggota = $proposal->members()->count();
        }

        if ($error = $this->cekBatasTim($proposal->skema, $jumlahAnggota)) {
            return back()->with('error', $error);
        }

        // Total RAB harus lebih dari 0. Bila ada item RAB, pakai
        // jumlahnya; bila tidak ada, pakai kolom biaya proposal.
        // Sebelumnya proposal bernilai Rp0 lolos selama masih ada
        // satu baris item RAB (berapa pun jumlahnya).
        $totalRab = $proposal->rabItems()->exists()
            ? (float) $proposal->rabItems()->sum('jumlah')
            : (float) $proposal->biaya;

        if ($totalRab <= 0) {
            return back()->with('error', 'Isi minimal satu komponen RAB dengan jumlah lebih dari 0 sebelum mengirim pengajuan.');
        }

        if ($proposal->skema->plafon > 0 && (float) $proposal->biaya > (float) $proposal->skema->plafon) {
            return back()->with('error', 'Total RAB Rp '.number_format((float) $proposal->biaya, 0, ',', '.').' melebihi plafon skema '.$proposal->skema->label().' (Rp '.number_format((float) $proposal->skema->plafon, 0, ',', '.').').');
        }

        // Kunci baris proposal selama pengiriman dan periksa ulang
        // statusnya di dalam transaksi: dua kirim cepat berurutan
        // sebelumnya sama-sama membaca status Draft sebelum salah
        // satunya menulis, sehingga pengiriman ganda (undangan dan
        // notifikasi dobel) bisa lolos. Bila status sudah berubah,
        // kembalikan pesan yang sama seperti penjaga di atas.
        $gagal = null;

        DB::transaction(function () use ($proposal, $user, &$gagal) {
            $terkunci = Proposal::where('id', $proposal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($terkunci->status, ['Draft', 'Revisi Administrasi'], true)) {
                $gagal = 'Proposal ini sudah tidak bisa dikirim (status: '.$terkunci->status.').';

                return;
            }

            $terkunci->update([
                'status' => 'Dikirim',
                'no_pengajuan' => $this->generateNoPengajuan($terkunci),
            ]);

            $this->sendMemberInvitations($terkunci);
            NotificationController::notifyProposalSubmitted($user->id);
            $this->logActivity("Mengirim proposal ID {$proposal->id} (status: Dikirim)");
        });

        if ($gagal) {
            return back()->with('error', $gagal);
        }

        return redirect()->route('monitoring.proposalDikirim')
            ->with('success', 'Proposal berhasil diajukan! Nomor pengajuan: '.$proposal->fresh()->no_pengajuan);
    }

    /**
     * Download File Proposal
     */
    public function download($id)
    {
        $proposal = \App\Models\Proposal::findOrFail($id);

        // A1: berkas proposal hanya boleh diunduh pihak yang berhak
        // atas proposal itu (ketua, anggota tim, reviewer yang
        // ditugaskan, verifikator, lppm, dekan, kepala_lppm, admin).
        // Sebelumnya user login mana pun bisa mengunduh berkas
        // proposal orang lain hanya dengan menebak ID-nya.
        if (! $this->dapatMengaksesProposal($proposal, auth()->user())) {
            abort(403, 'Anda tidak memiliki izin untuk mengunduh berkas proposal ini.');
        }

        // Cek apakah file ada di disk public
        if (! $proposal->file_path || ! \Storage::disk('public')->exists($proposal->file_path)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $this->logActivity('Mendownload file proposal ID: '.$id);

        // Menggunakan Storage download lebih aman daripada response()->download(storage_path(...))
        return \Storage::disk('public')->download($proposal->file_path);
    }

    /**
     * Preview File Proposal (inline bila PDF/gambar, unduh bila bukan).
     * Otorisasi sama seperti download() (A1) — sebelumnya preview
     * juga terbuka untuk user login mana pun tanpa cek kepemilikan.
     */
    public function preview($id)
    {
        $proposal = \App\Models\Proposal::findOrFail($id);

        if (! $this->dapatMengaksesProposal($proposal, auth()->user())) {
            abort(403, 'Anda tidak memiliki izin untuk melihat berkas proposal ini.');
        }

        if (! $proposal->file_path) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $this->logActivity('Melihat preview file proposal ID: '.$id);

        return $this->previewStoredFile(
            $proposal->file_path,
            basename($proposal->file_path),
            'proposal.download',
            ['id' => $id]
        );
    }

    /**
     * Assign Reviewer oleh Admin
     */
    public function assignReviewer(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        // Gunakan after_or_equal:today agar tidak error gara-gara selisih jam/menit
        $request->validate([
            'reviewer_1' => 'required|exists:users,id',
            'reviewer_2' => 'required|exists:users,id',
            'review_deadline' => 'required|date|after_or_equal:today',
        ], [
            'reviewer_1.required' => 'Reviewer 1 harus dipilih dari list.',
            'reviewer_2.required' => 'Reviewer 2 harus dipilih dari list.',
            'review_deadline.after_or_equal' => 'Tenggat waktu tidak boleh tanggal yang sudah lewat.',
        ]);

        // Proteksi tambahan
        // Hanya proposal yang Lolos Administrasi yang boleh di-plotting.
        // Sebelumnya plotting bisa dilakukan pada proposal Draft /
        // Dikirim / bahkan yang sudah direview, sehingga proposal
        // bisa meloncat langsung ke Sedang Direview tanpa verifikasi.
        if ($proposal->status !== 'Lolos Administrasi') {
            return back()->withInput()->with('error', 'Hanya proposal yang Lolos Administrasi yang bisa di-plotting (status: '.$proposal->status.').');
        }

        if ($request->reviewer_1 == $request->reviewer_2) {
            return back()->withInput()->with('error', 'Reviewer 1 dan 2 tidak boleh orang yang sama.');
        }

        if ($request->reviewer_1 == $proposal->user_id || $request->reviewer_2 == $proposal->user_id) {
            return back()->withInput()->with('error', 'Pemilik proposal tidak boleh menjadi reviewer sendiri.');
        }

        // Anggota tim proposal tidak boleh mereview proposal itu sendiri (blind review).
        $memberIds = $proposal->members()->whereNotNull('user_id')->pluck('user_id')->all();
        if (in_array((int) $request->reviewer_1, $memberIds, true) || in_array((int) $request->reviewer_2, $memberIds, true)) {
            return back()->withInput()->with('error', 'Anggota tim proposal tidak boleh menjadi reviewer proposal itu sendiri.');
        }

        // Kedua yang ditugaskan harus benar-benar berperan reviewer.
        // Sebelumnya validasi hanya exists:users,id sehingga pengaju,
        // admin, atau peran lain bisa ditugaskan sebagai reviewer.
        // Dicek di sini (bukan di Rule validasi) agar pesan errornya
        // tetap berupa flash 'error' seperti penjaga lain di atas.
        $calonReviewer = User::whereIn('id', [$request->reviewer_1, $request->reviewer_2])
            ->where('role', 'reviewer')
            ->count();
        if ($calonReviewer !== 2) {
            return back()->withInput()->with('error', 'Reviewer yang dipilih harus memiliki peran reviewer.');
        }

        // Eksekusi Simpan
        try {
            // Simpan ke tabel pivot
            $proposal->reviewers()->sync([$request->reviewer_1, $request->reviewer_2]);

            // Update data proposal
            $proposal->update([
                'review_deadline' => \Carbon\Carbon::parse($request->review_deadline),
                'status' => 'Sedang Direview',
            ]);

            // ===============================
            // ✅ NOTIF KE REVIEWER (langsung muncul di bell)
            // ===============================
            $deadlineWib = Carbon::parse($proposal->review_deadline)->timezone('Asia/Jakarta');

            $title = 'Tugas Review Proposal';
            $message = 'Anda ditugaskan mereview proposal "'.($proposal->judul ?? '-').'". '.
                'Tenggat penilaian: '.$deadlineWib->translatedFormat('d M Y H:i').' WIB.';

            NotificationHelper::send((int) $request->reviewer_1, $title, $message, 'info', $proposal->id);
            NotificationHelper::send((int) $request->reviewer_2, $title, $message, 'info', $proposal->id);

            // Plotting tercatat di sini juga (endpoint ini yang dipakai
            // halaman plotting LPPM): penugasan 2 reviewer + tenggat.
            $this->logActivity("Menugaskan reviewer untuk proposal ID {$id} (status: Sedang Direview)");

            return back()->with('success', 'Berhasil! Reviewer telah ditugaskan.');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Database Error: '.$e->getMessage());
        }
    }

    /**
     * Hapus Proposal
     */
    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);
        $user = auth()->user();

        // 1. Jalur Khusus Admin (Admin bisa hapus siapa saja) dan
        // 2. Jalur Pengaju/Reviewer (Hanya bisa hapus milik sendiri).
        // Gunakan != (bukan !==) untuk menghindari bug tipe data String vs Integer.
        $bolehHapus = $user->role === 'admin' || $proposal->user_id == $user->id;

        if (! $bolehHapus) {
            return back()->with('error', "Anda tidak diizinkan menghapus proposal ini (Milik ID: {$proposal->user_id}).");
        }

        // Hanya Draft yang boleh dihapus. Proposal yang sudah berjalan
        // (Dikirim dan seterusnya) punya riwayat verifikasi, review,
        // dan keputusan — sebelumnya pemilik (dan admin) bisa
        // menghapus proposal berstatus apa pun beserta berkasnya.
        if ($proposal->status !== 'Draft') {
            return back()->with('error', 'Proposal dengan status '.$proposal->status.' tidak bisa dihapus.');
        }

        return $this->prosesHapus($proposal);
    }

    /**
     * Helper internal agar kode tidak duplikat
     */
    private function prosesHapus($proposal)
    {
        // Hapus file jika ada
        if ($proposal->file_path && \Storage::disk('public')->exists($proposal->file_path)) {
            \Storage::disk('public')->delete($proposal->file_path);
        }

        $proposal->delete();

        return back()->with('success', 'Proposal berhasil dihapus.');
    }

    /**
     * Cetak Review ke PDF — hanya pihak yang berhak atas proposal
     * itu (aturan yang sama dengan download berkas proposal).
     * Sebelumnya user login mana pun bisa mengunduh PDF hasil review
     * proposal orang lain hanya dengan menebak ID review-nya.
     */
    public function downloadReviewPdf($id)
    {
        $review = Review::with(['reviewer', 'proposal'])->findOrFail($id);

        if (! $review->proposal || ! $this->dapatMengaksesProposal($review->proposal, auth()->user())) {
            abort(403, 'Anda tidak memiliki izin untuk melihat hasil review ini.');
        }

        $data = ['reviews' => collect([$review]), 'is_pdf' => true];

        $pdf = Pdf::loadView('proposal.admin.hasil-review', $data);

        // Gunakan stream() agar muncul di tab baru browser
        return $pdf->setPaper('a4', 'portrait')->stream('Hasil-Review-'.$id.'.pdf');
    }

    /**
     * Preview PDF hasil review — stream() di downloadReviewPdf sudah
     * inline, jadi preview cukup mendelegasikan ke method itu.
     */
    public function previewReviewPdf($id)
    {
        return $this->downloadReviewPdf($id);
    }

    // --- View Monitoring Methods ---

    // Sesuaikan nama function dengan Route (perluDireview)
    public function perluDireview()
    {
        $user = auth()->user();

        // Inisialisasi Query
        $query = Proposal::with(['reviewers', 'user']);

        // Halaman antrean plotting/review (keputusan audit #10):
        // staf (admin, verifikator, lppm, dekan, kepala_lppm) melihat
        // antrean yang perlu diplot / sedang direview; reviewer hanya
        // yang ditugaskan kepadanya. Pengaju ditolak — ia memantau
        // proposalnya lewat Status Proposal. Sebelumnya pengaju (dan
        // staf selain admin) melihat SEMUA proposal di sini karena
        // tidak ada cabang else.
        if (in_array($user->role, self::ROLE_STAF_MONITORING, true)) {
            // Staf bisa melihat semua yang perlu diplot atau sedang direview
            $query->whereIn('status', ['Dikirim', 'Perlu Direview', 'Sedang Direview', 'Hasil Revisi']);
        } elseif ($user->role === 'reviewer') {
            // Reviewer HANYA melihat proposal yang ditugaskan kepadanya
            $query->where('status', 'Sedang Direview')
                ->whereHas('reviewers', function ($q) use ($user) {
                    $q->where('reviewer_id', $user->id);
                });
        } else {
            abort(403, 'Halaman ini hanya untuk staf LPPM dan reviewer yang ditugaskan.');
        }

        $proposals = $query->latest()->get();
        $reviewers = User::where('role', 'reviewer')->orderBy('name')->get();

        return view('proposal.proposal-perlu-direview', compact('proposals', 'reviewers'));
    }

    public function proposalSedangDireview()
    {
        // Halaman monitoring review (keputusan audit #10): staf
        // melihat semua yang sedang direview, reviewer hanya yang
        // ditugaskan kepadanya, pengaju ditolak (ia memantau status
        // proposalnya lewat Status Proposal). Sebelumnya halaman ini
        // menampilkan semua proposal ke semua role tanpa pembatasan.
        $user = auth()->user();

        if ($user->role === 'reviewer') {
            $query = Proposal::where('status', 'Sedang Direview')
                ->whereHas('reviewers', function ($q) use ($user) {
                    $q->where('proposal_reviewers.reviewer_id', $user->id);
                });
        } elseif (in_array($user->role, self::ROLE_STAF_MONITORING, true)) {
            $query = Proposal::where('status', 'Sedang Direview');
        } else {
            abort(403, 'Halaman ini hanya untuk staf LPPM dan reviewer yang ditugaskan.');
        }

        $proposals = $query->with(['reviewers', 'reviews'])
            ->latest()
            ->get();

        return view('proposal.proposal-sedang-direview', compact('proposals'));
    }

    public function reviewSelesai()
    {
        // Daftar monitoring (keputusan audit #10): staf melihat
        // semua, pengaju hanya proposalnya sendiri, reviewer hanya
        // yang ditugaskan kepadanya. Kondisi OR dibungkus closure
        // agar pembatasan role (AND) berlaku untuk kedua cabangnya —
        // tanpa bungkus ini, 'Review Selesai' milik siapa pun lolos
        // karena AND mengikat lebih kuat daripada OR.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::where(function ($q) {
                $q->where('status', 'Review Selesai')
                    ->orWhereIn('status_pendanaan', ['Disetujui', 'Ditolak', 'Direvisi']);
            })
        )
            ->with(['user', 'reviews.reviewer'])
            ->latest('updated_at')
            ->get();

        return view('proposal.proposal-selesai', compact('proposals'));
    }

    public function proposalDitolak()
    {
        // Daftar monitoring (keputusan audit #10): staf melihat
        // semua, pengaju hanya proposalnya sendiri, reviewer hanya
        // yang ditugaskan kepadanya.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::where('status', 'Ditolak')
        )->latest()->get();

        return view('proposal.proposal-ditolak', compact('proposals'));
    }

    public function proposalDirevisi()
    {
        // Daftar monitoring (keputusan audit #10): staf melihat
        // semua, pengaju hanya proposalnya sendiri, reviewer hanya
        // yang ditugaskan kepadanya.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::whereIn('status', ['Ditolak', 'Direvisi'])
        )->latest()->get();

        return view('proposal.proposal-direvisi', compact('proposals'));
    }

    // Fungsi untuk Proposal Disetujui
    public function proposalDisetujui()
    {
        // Halaman monitoring (menu Verifikasi, untuk verifikator + admin):
        // menampilkan SEMUA proposal berstatus 'Disetujui', bukan hanya
        // milik pengguna yang login. Sebelumnya query difilter
        // user_id = pengguna login, sehingga verifikator — yang tidak
        // pernah menjadi pemilik proposal — selalu melihat halaman kosong
        // ("Belum ada proposal yang disetujui") meski ada proposal
        // Disetujui. Pola ini sama dengan halaman monitoring lain
        // (proposalDitolak, proposalSedangDireview, reviewSelesai) yang
        // tidak memfilter pemilik.
        //
        // Keputusan audit #10: staf tetap melihat semua; pengaju
        // tetap bisa membuka halaman ini (terbukti di uji web:
        // pengaju melihat proposalnya sendiri di sini) tapi HANYA
        // proposalnya sendiri — sebelumnya pengaju melihat proposal
        // Disetujui milik semua orang. Reviewer hanya yang ditugaskan
        // kepadanya.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::where('status', 'Disetujui')
        )
            ->with(['reviewers', 'user']) // Eager loading untuk menampilkan nama reviewer + pengusul
            ->latest()
            ->get();

        return view('proposal.proposal-disetujui', compact('proposals'));
    }

    // Tambahkan juga fungsi ini jika belum ada (antisipasi error berikutnya)
    public function proposalSelesai()
    {
        // Metode ini tidak punya route (route /proposal-selesai
        // memakai reviewSelesai()), tapi tetap dibatasi sama seperti
        // reviewSelesai (keputusan audit #10) bila suatu saat di-route.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::whereIn('status', [
                'Review Selesai',
                'Disetujui',
                'Ditolak',
                'Selesai',
            ])
        )
            ->latest()
            ->get();

        return view('proposal.proposal-selesai', compact('proposals'));
    }

    public function hasilRevisi()
    {
        // Daftar monitoring (keputusan audit #10): staf melihat
        // semua, pengaju hanya proposalnya sendiri, reviewer hanya
        // yang ditugaskan kepadanya.
        $proposals = $this->batasiDaftarMonitoring(
            Proposal::where('status', 'Hasil Revisi')
        )->latest()->get();

        return view('proposal.hasil-review', compact('proposals'));
    }

    public function keputusan(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        // Nilai 'Review Selesai' dikeluarkan dari pilihan yang sah:
        // itu status alur, bukan keputusan pendanaan, dan sebelumnya
        // bisa dipakai untuk menandai review selesai tanpa ada review.
        $request->validate([
            'status_pendanaan' => 'required|string|in:Disetujui,Ditolak,Direvisi',
        ]);

        // Keputusan pendanaan hanya boleh diberikan setelah review
        // selesai. Sebelumnya admin bisa memutuskan proposal Draft /
        // Dikirim / Sedang Direview, termasuk sebelum reviewer mana
        // pun mengirim review.
        if ($proposal->status !== 'Review Selesai') {
            return redirect()->back()->with('error', 'Keputusan pendanaan hanya bisa diberikan setelah review selesai (status saat ini: '.$proposal->status.').');
        }

        // Kedua reviewer yang ditugaskan harus sudah mengirim review.
        $jumlahReviewer = $proposal->reviewers()->count();
        $jumlahReview = $proposal->reviews()->count();
        if ($jumlahReview < 1 || $jumlahReview < $jumlahReviewer) {
            return redirect()->back()->with('error', 'Keputusan pendanaan menunggu semua reviewer mengirim review ('.$jumlahReview.' dari '.$jumlahReviewer.' review masuk).');
        }

        // 2. Update status utama agar tidak hilang dari query 'whereIn' halaman Selesai
        $proposal->status = $request->status_pendanaan;

        // 3. Simpan juga ke kolom khusus pendanaan jika Anda menggunakannya
        $proposal->status_pendanaan = $request->status_pendanaan;

        $proposal->save();

        if (method_exists($this, 'logActivity')) {
            $this->logActivity("Admin mengubah status proposal ID {$id} menjadi: ".$request->status_pendanaan);
        }

        return redirect()->back()->with('success', 'Keputusan berhasil disimpan!');
    }

    public function setReview($id)
    {
        $proposal = Proposal::findOrFail($id);

        // Route ini tidak punya middleware role (hanya auth), sehingga
        // sebelumnya user login mana pun bisa memindahkan proposal
        // apa pun ke Sedang Direview. Hanya admin/LPPM yang boleh.
        if (! in_array(auth()->user()->role, ['admin', 'lppm'], true)) {
            abort(403, 'Hanya admin atau LPPM yang boleh memindahkan proposal ke tahap review.');
        }

        // Hanya dari status pra-review. Pemindahan ini tetap melewati
        // plotting (tidak menugaskan reviewer) — lihat laporan audit
        // bila alur ini masih dipakai, karena proposal Sedang Direview
        // tanpa reviewer tidak akan pernah selesai direview.
        if (! in_array($proposal->status, ['Lolos Administrasi', 'Perlu Direview'], true)) {
            return redirect()->route('monitoring.proposalPerluDireview')
                ->with('error', 'Hanya proposal yang Lolos Administrasi yang bisa dipindahkan ke tahap review (status: '.$proposal->status.').');
        }

        // Ubah status agar masuk ke kategori "Perlu Direview"
        $proposal->status = 'Sedang Direview';
        $proposal->save();

        // Alihkan ke route halaman "Proposal Perlu Direview"
        return redirect()->route('monitoring.proposalPerluDireview')
            ->with('success', 'Proposal telah dipindahkan ke daftar review.');
    }

    public function tinjau($id)
    {
        $proposal = Proposal::with(['user', 'reviewers', 'reviews.reviewer', 'verifications.verifikator'])->findOrFail($id);

        // Halaman detail proposal sebelumnya terbuka untuk user login
        // mana pun tanpa cek kepemilikan — pakai aturan akses yang
        // sama dengan download/preview berkas (A1).
        if (! $this->dapatMengaksesProposal($proposal, auth()->user())) {
            abort(403, 'Anda tidak memiliki izin untuk melihat proposal ini.');
        }

        // Riwayat keputusan pendanaan dari log aktivitas admin. Pola LIKE
        // dengan ' menjadi:' tepat setelah ID mencegah kecocokan dengan ID
        // lain (mis. ID 1 vs 12) dan mengecualikan log unduhan
        // ("Mendownload file proposal ID: ...").
        $riwayatKeputusan = ActivityLog::where('activity', 'like', '%proposal ID '.$proposal->id.' menjadi:%')
            ->with('user')
            ->latest()
            ->get();

        return view('proposal.tinjau-proposal', compact('proposal', 'riwayatKeputusan'));
    }

    public function daftarReview(Request $request)
    {
        $user = Auth::user();

        // Daftar review (keputusan audit #10): pengaju ditolak —
        // sebelumnya pengaju melihat SEMUA proposal beserta
        // reviewernya di sini. Staf melihat semua; reviewer hanya
        // yang ditugaskan kepadanya (sudah ada di bawah).
        if ($user->role === 'pengaju') {
            abort(403, 'Halaman ini hanya untuk staf LPPM dan reviewer yang ditugaskan.');
        }

        $query = Proposal::with(['user', 'reviewers']);

        if ($user->role === 'reviewer') {
            // HANYA menampilkan proposal yang ditugaskan ke reviewer ini
            $query->whereHas('reviewers', function ($q) use ($user) {
                $q->where('reviewer_id', $user->id);
            });

            // Opsional: Hanya tampilkan yang statusnya sedang dalam tahap review
            $query->whereIn('status', ['Perlu Direview', 'Sedang Direview', 'Review Selesai']);
        }

        $proposals = $query->latest()->get();

        return view('admin.daftar_review', compact('proposals'));
    }

    // ===============================
    // ✅ FIX ROUTE MONITORING
    // ===============================
    public function dikirim(Request $request)
    {
        $tahun = $this->tahunFilter($request);
        $user = auth()->user();
        $role = $user->role;

        $list_fakultas = Fakultas::all();
        $all_dosen = User::whereIn('role', ['pengaju', 'reviewer'])->get();

        // --- LOGIKA MASA HIBAH ---
        $now = now();
        $isMasaHibah = DB::table('hibah_periods')
            ->whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->exists();
        // -------------------------

        $query = Proposal::with(['fakultas', 'user', 'skema'])
            ->whereYear('created_at', $tahun)
            ->where('status', 'Dikirim');

        if ($role === 'pengaju' || $role === 'reviewer') {
            $query->where('user_id', $user->id);
        }

        $proposals = $query->latest()->get();

        return view('proposal.daftar_proposal', compact(
            'proposals',
            'tahun',
            'role',
            'list_fakultas',
            'all_dosen',
            'isMasaHibah'
        ));
    }

    // Alias biar nama method di route aman (kalau route pakai sedangDireview/disetujui/ditolak)
    public function sedangDireview()
    {
        return $this->proposalSedangDireview();
    }

    public function disetujui()
    {
        return $this->proposalDisetujui();
    }

    public function ditolak()
    {
        return $this->proposalDitolak();
    }
}
