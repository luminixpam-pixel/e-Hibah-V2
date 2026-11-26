<?php

namespace App\Http\Controllers;

use App\Helpers\NotificationHelper;
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

class ProposalController extends Controller
{
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

        foreach ($names as $nama) {
            $nama = trim((string) $nama);
            if ($nama === '' || isset($seen[mb_strtolower($nama)])) {
                continue;
            }
            $seen[mb_strtolower($nama)] = true;

            $user = User::whereIn('role', ['pengaju', 'reviewer'])->where('name', $nama)->first();

            // Ketua tidak dicatat lagi sebagai anggota tim.
            if ($user && $ketuaId && (int) $user->id === (int) $ketuaId) {
                continue;
            }

            $resolved[] = ['user_id' => $user?->id, 'nama' => $nama];
        }

        return $resolved;
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
            return "Anda sudah mencapai kuota skema {$skema->kategori} ({$skema->kuota} keikutsertaan).";
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
                return "Anggota {$member->name} sudah mencapai kuota skema {$skema->kategori} ({$skema->kuota} keikutsertaan).";
            }
        }

        return null;
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
            $jumlah = (float) preg_replace('/[^0-9.]/', '', (string) ($item['jumlah'] ?? 0));
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
        $list_skema = \App\Models\Skema::orderBy('kategori')->get();
        $all_dosen = \App\Models\User::whereIn('role', ['pengaju', 'reviewer'])
            ->orderBy('name')
            ->get();
        // Pilihan nama komponen RAB: bawaan + kustom dari settings
        // (key 'rab_komponen_kustom'); dipakai datalist di form.
        $daftarKomponenRab = Setting::daftarKomponenRab();

        return view('proposal.create', compact('list_fakultas', 'list_skema', 'all_dosen', 'daftarKomponenRab'));
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

        $kuotaPerSkema = Skema::orderBy('kategori')->get()->map(function ($skema) use ($user) {
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
        $tahun = $request->get('tahun', date('Y'));
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

        // 2. Akun yang ditandai memiliki tanggungan tidak boleh mengajukan baru.
        if ($user->is_blacklisted) {
            return redirect()->back()->withInput()
                ->with('error', 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.');
        }

        // 3. Validasi form (draft boleh dilengkapi bertahap, submit harus lengkap).
        // Nama komponen RAB harus ada di daftar bawaan + kustom (settings
        // key 'rab_komponen_kustom'); bila key belum ada, daftar kustom kosong.
        $komponenRab = Setting::daftarKomponenRab();
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
            ]);
        } else {
            $request->validate([
                'judul' => 'required|string|max:255',
                'nama_ketua' => 'required|string|max:255',
                'fakultas_prodi' => 'required|exists:fakultas,id',
                'skema_id' => 'required|exists:skemas,id',
                'file' => 'required|file|mimes:pdf,doc,docx|max:102400', // Maks 100 MB
                'anggota' => 'nullable|array',
                'rab' => 'required|array|min:1',
                'rab.*.nama_komponen' => ['required', 'string', 'max:255', Rule::in($komponenRab)],
                'rab.*.jumlah' => 'required|numeric|min:0',
                'rab.*.keterangan' => 'nullable|string|max:500',
            ]);
        }

        $skema = $request->filled('skema_id') ? Skema::find($request->skema_id) : null;

        // 4. Selesaikan nama anggota menjadi user (bila terdaftar).
        $anggotaInput = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), (array) $request->input('anggota', []))
        ));
        $anggotaResolved = $this->resolveAnggota($anggotaInput, $user->id);
        $memberUserIds = array_values(array_filter(array_column($anggotaResolved, 'user_id')));

        // 5. Cek kuota per skema + blacklist anggota (hanya saat submit).
        if (! $isDraft && $skema) {
            $error = $this->checkPengajuan($user, $skema, $memberUserIds);
            if ($error) {
                return redirect()->back()->withInput()->with('error', $error);
            }
        }

        // 6. Hitung total RAB dan validasi terhadap plafon skema (hanya saat submit).
        $rabTotal = 0;
        foreach ((array) $request->input('rab', []) as $item) {
            $rabTotal += (float) preg_replace('/[^0-9.]/', '', (string) ($item['jumlah'] ?? 0));
        }
        if (! $isDraft && $skema && $skema->plafon > 0 && $rabTotal > (float) $skema->plafon) {
            return redirect()->back()->withInput()
                ->with('error', 'Total RAB Rp '.number_format($rabTotal, 0, ',', '.').' melebihi plafon skema '.$skema->kategori.' (Rp '.number_format((float) $skema->plafon, 0, ',', '.').').');
        }

        try {
            $proposal = DB::transaction(function () use ($request, $user, $isDraft, $skema, $anggotaResolved, $rabTotal) {
                // Upload File (wajib saat submit, opsional saat draft).
                $filePath = null;
                if ($request->hasFile('file')) {
                    $extension = $request->file('file')->getClientOriginalExtension();
                    $cleanName = preg_replace('/[^A-Za-z0-9\-]/', '', $request->judul);
                    $finalName = substr($cleanName, 0, 50).'_'.time().'.'.$extension;
                    $filePath = $request->file('file')->storeAs('proposal_files', $finalName, 'public');
                }

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
                    ->with('success', 'Draft proposal berhasil disimpan. Anda dapat melengkapinya kembali sebelum dikirim.');
            }

            return redirect()->route('proposal.index')
                ->with('success', 'Proposal berhasil diajukan! Nomor pengajuan: '.$proposal->no_pengajuan);
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

        // 2. Ambil semua data user (untuk list anggota di datalist)
        // Sesuaikan role jika hanya ingin menampilkan dosen/pengaju saja
        $users = \App\Models\User::all();
        $list_skema = Skema::orderBy('kategori')->get();
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

        // 4. Kirim semua variabel ke view
        return view('proposal.edit-proposal', compact('proposal', 'users', 'list_skema', 'daftarKomponenRab'));
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

        $user = auth()->user();
        $aksi = $request->input('aksi', 'submit');
        // Kirim ulang hasil Revisi Administrasi dihitung sebagai pengajuan juga,
        // supaya proposal kembali ke antrean verifikasi (status Dikirim).
        $kirimPengajuan = $aksi === 'submit' && in_array($proposal->status, ['Draft', 'Revisi Administrasi']);

        // Validasi: draft boleh dilengkapi bertahap; pengiriman dan revisi harus lengkap.
        // Nama komponen RAB harus ada di daftar bawaan + kustom (settings
        // key 'rab_komponen_kustom'); bila key belum ada, daftar kustom kosong.
        $request->validate([
            'judul' => 'required|string',
            'nama_ketua' => 'required|string',
            'fakultas_prodi' => 'required',
            'skema_id' => 'nullable|exists:skemas,id',
            'file' => 'nullable|file|mimes:pdf,doc,docx|max:102400',
            'anggota' => 'nullable|array',
            'rab' => 'nullable|array',
            'rab.*.nama_komponen' => ['nullable', 'string', 'max:255', Rule::in(Setting::daftarKomponenRab())],
            'rab.*.jumlah' => 'nullable|numeric|min:0',
            'rab.*.keterangan' => 'nullable|string|max:500',
        ]);

        $skema = $request->filled('skema_id') ? Skema::find($request->skema_id) : $proposal->skema;

        $anggotaInput = array_values(array_filter(
            array_map(fn ($v) => trim((string) $v), (array) $request->input('anggota', []))
        ));
        $anggotaResolved = $this->resolveAnggota($anggotaInput, (int) $proposal->user_id);
        $memberUserIds = array_values(array_filter(array_column($anggotaResolved, 'user_id')));

        // Saat draft dikirim: berlaku semua aturan pengajuan.
        if ($kirimPengajuan) {
            if ($user->is_blacklisted) {
                return redirect()->back()->withInput()
                    ->with('error', 'Akun Anda ditandai memiliki tanggungan laporan (blacklist) sehingga tidak dapat mengajukan proposal baru. Silakan hubungi LPPM.');
            }
            if (! $skema) {
                return redirect()->back()->withInput()->with('error', 'Pilih skema hibah terlebih dahulu sebelum mengirim pengajuan.');
            }
            if (! $proposal->file_path && ! $request->hasFile('file')) {
                return redirect()->back()->withInput()->with('error', 'Unggah file proposal terlebih dahulu sebelum mengirim pengajuan.');
            }
            // Proposal yang dikirim ulang tidak dihitung sebagai keikutsertaan baru.
            $error = $this->checkPengajuan($user, $skema, $memberUserIds, (int) $proposal->id);
            if ($error) {
                return redirect()->back()->withInput()->with('error', $error);
            }
        }

        // Total RAB dari input form (bila ada); bila tidak ada, biaya lama dipertahankan.
        $rabInput = (array) $request->input('rab', []);
        $rabTotal = 0;
        $hasRab = false;
        foreach ($rabInput as $item) {
            $namaItem = trim((string) ($item['nama_komponen'] ?? ''));
            $jumlahItem = (float) preg_replace('/[^0-9.]/', '', (string) ($item['jumlah'] ?? 0));
            if ($namaItem !== '' || $jumlahItem > 0) {
                $hasRab = true;
            }
            $rabTotal += $jumlahItem;
        }

        if ($kirimPengajuan && ! $hasRab) {
            return redirect()->back()->withInput()->with('error', 'Isi minimal satu komponen RAB sebelum mengirim pengajuan.');
        }
        if ($hasRab && $skema && $skema->plafon > 0 && $rabTotal > (float) $skema->plafon) {
            return redirect()->back()->withInput()
                ->with('error', 'Total RAB Rp '.number_format($rabTotal, 0, ',', '.').' melebihi plafon skema '.$skema->kategori.' (Rp '.number_format((float) $skema->plafon, 0, ',', '.').').');
        }

        DB::transaction(function () use ($request, $proposal, $user, $kirimPengajuan, $skema, $anggotaResolved, $rabInput, $rabTotal, $hasRab) {
            // Handle File
            if ($request->hasFile('file')) {
                if ($proposal->file_path) {
                    \Storage::disk('public')->delete($proposal->file_path);
                }
                $proposal->file_path = $request->file('file')->store('proposal_files', 'public');
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
            } elseif (in_array($proposal->status, ['Ditolak', 'Revisi'])) {
                $data['status'] = 'Hasil Revisi';
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
                ->with('success', 'Proposal berhasil diajukan! Nomor pengajuan: '.$proposal->fresh()->no_pengajuan);
        }

        return redirect()->route('monitoring.proposalDikirim')->with('success', 'Proposal diperbarui!');
    }

    /**
     * Download File Proposal
     */
    public function download($id)
    {
        $proposal = \App\Models\Proposal::findOrFail($id);

        // Cek apakah file ada di disk public
        if (! \Storage::disk('public')->exists($proposal->file_path)) {
            return back()->with('error', 'File tidak ditemukan di server.');
        }

        $this->logActivity('Mendownload file proposal ID: '.$id);

        // Menggunakan Storage download lebih aman daripada response()->download(storage_path(...))
        return \Storage::disk('public')->download($proposal->file_path);
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

        // 1. Jalur Khusus Admin (Admin bisa hapus siapa saja)
        if ($user->role === 'admin') {
            return $this->prosesHapus($proposal);
        }

        // 2. Jalur Pengaju/Reviewer (Hanya bisa hapus milik sendiri)
        // Gunakan != (bukan !==) untuk menghindari bug tipe data String vs Integer
        if ($proposal->user_id == $user->id) {
            return $this->prosesHapus($proposal);
        }

        // 3. Jika gagal semua, beri pesan error detail
        return back()->with('error', "Anda tidak diizinkan menghapus proposal ini (Milik ID: {$proposal->user_id}).");
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
     * Cetak Review ke PDF
     */
    public function downloadReviewPdf($id)
    {
        $review = Review::with(['reviewer', 'proposal'])->findOrFail($id);
        $data = ['reviews' => collect([$review]), 'is_pdf' => true];

        $pdf = Pdf::loadView('proposal.admin.hasil-review', $data);

        // Gunakan stream() agar muncul di tab baru browser
        return $pdf->setPaper('a4', 'portrait')->stream('Hasil-Review-'.$id.'.pdf');
    }

    // --- View Monitoring Methods ---

    // Sesuaikan nama function dengan Route (perluDireview)
    public function perluDireview()
    {
        $user = auth()->user();

        // Inisialisasi Query
        $query = Proposal::with(['reviewers', 'user']);

        if ($user->role === 'admin') {
            // Admin bisa melihat semua yang perlu diplot atau sedang direview
            $query->whereIn('status', ['Dikirim', 'Perlu Direview', 'Sedang Direview', 'Hasil Revisi']);
        } elseif ($user->role === 'reviewer') {
            // Reviewer HANYA melihat proposal yang ditugaskan kepadanya
            $query->where('status', 'Sedang Direview')
                ->whereHas('reviewers', function ($q) use ($user) {
                    $q->where('reviewer_id', $user->id);
                });
        }

        $proposals = $query->latest()->get();
        $reviewers = User::where('role', 'reviewer')->orderBy('name')->get();

        return view('proposal.proposal-perlu-direview', compact('proposals', 'reviewers'));
    }

    public function proposalSedangDireview()
    {
        $proposals = Proposal::where('status', 'Sedang Direview')
            ->with(['reviewers', 'reviews'])
            ->latest()
            ->get();

        return view('proposal.proposal-sedang-direview', compact('proposals'));
    }

    public function reviewSelesai()
    {
        $proposals = Proposal::where('status', 'Review Selesai')
            ->orWhereIn('status_pendanaan', ['Disetujui', 'Ditolak', 'Direvisi'])
            ->with(['user', 'reviews.reviewer'])
            ->latest('updated_at')
            ->get();

        return view('proposal.proposal-selesai', compact('proposals'));
    }

    public function proposalDitolak()
    {
        $proposals = Proposal::where('status', 'Ditolak')->latest()->get();

        return view('proposal.proposal-ditolak', compact('proposals'));
    }

    public function proposalDirevisi()
    {
        $proposals = Proposal::whereIn('status', ['Ditolak', 'Direvisi'])->latest()->get();

        return view('proposal.proposal-direvisi', compact('proposals'));
    }

    // Fungsi untuk Proposal Disetujui
    public function proposalDisetujui()
    {
        // Mengambil proposal yang statusnya 'Disetujui' DAN dimiliki oleh user yang login
        // auth()->id() = username, bukan users.id — pakai auth()->user()->id.
        $proposals = Proposal::where('user_id', auth()->user()->id)
            ->where('status', 'Disetujui')
            ->with('reviewers') // Eager loading untuk menampilkan nama reviewer
            ->latest()
            ->get();

        return view('proposal.proposal-disetujui', compact('proposals'));
    }

    // Tambahkan juga fungsi ini jika belum ada (antisipasi error berikutnya)
    public function proposalSelesai()
    {
        // Ambil semua proposal yang sudah melewati tahap review
        $proposals = Proposal::whereIn('status', [
            'Review Selesai',
            'Disetujui',
            'Ditolak',
            'Selesai',
        ])
            ->latest()
            ->get();

        return view('proposal.proposal-selesai', compact('proposals'));
    }

    public function hasilRevisi()
    {
        $proposals = Proposal::where('status', 'Hasil Revisi')->latest()->get();

        return view('proposal.hasil-review', compact('proposals'));
    }

    public function keputusan(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        // 1. Tambahkan 'Disetujui' di sini
        $request->validate([
            'status_pendanaan' => 'required|string|in:Disetujui,Ditolak,Direvisi,Review Selesai',
        ]);

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

    public function uploadLaporanKemajuan(Request $request, $id)
    {
        $request->validate([
            'laporan_kemajuan' => 'required|mimes:pdf|max:2048',
        ]);

        $proposal = Proposal::findOrFail($id);

        $file = $request->file('laporan_kemajuan');
        $filename = 'laporan_kemajuan_'.time().'.'.$file->getClientOriginalExtension();
        $file->storeAs('laporan', $filename, 'public');

        $proposal->update(['laporan_kemajuan' => $filename]);

        return back()->with('success', 'Laporan Kemajuan berhasil diunggah.');
    }

    public function uploadLaporanAkhir(Request $request, $id)
    {
        $request->validate([
            'laporan_akhir' => 'required|mimes:pdf|max:2048',
        ]);

        $proposal = Proposal::findOrFail($id);

        $file = $request->file('laporan_akhir');
        $filename = 'laporan_akhir_'.time().'.'.$file->getClientOriginalExtension();
        $file->storeAs('laporan', $filename, 'public');

        $proposal->update(['laporan_akhir' => $filename]);

        return back()->with('success', 'Laporan Akhir berhasil diunggah.');
    }

    // ===============================
    // ✅ FIX ROUTE MONITORING
    // ===============================
    public function dikirim(Request $request)
    {
        $tahun = $request->get('tahun', date('Y'));
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
