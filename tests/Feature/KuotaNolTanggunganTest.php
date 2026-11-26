<?php

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f6kBuatPengaju(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'pengaju'], $attrs));
}

function f6kBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        // Kuota 0 = batas belum ditetapkan, cek kuota dilewati.
        'kuota' => 0,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f6kBuatPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function f6kBuatFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function f6kPayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Pengembangan Sistem E-Hibah',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 5000000, 'keterangan' => ''],
            ['nama_komponen' => 'ATK', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

function f6kBuatProposalDisetujui(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal disetujui lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => f6kBuatFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Disetujui',
        'biaya' => 1000000,
    ], $attrs));
}

function f6kBuatProposalDikirim(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal dikirim lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => f6kBuatFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

test('skema kuota 0, anggota bertanggungan: submit ditolak', function () {
    Storage::fake('public');
    $ketua = f6kBuatPengaju(['name' => 'Dr. Andi']);
    $anggota = f6kBuatPengaju(['name' => 'Budi Santoso']);
    $skema = f6kBuatSkema();
    $fakultas = f6kBuatFakultas();
    f6kBuatPeriodeAktif();

    // Anggota punya proposal Disetujui tanpa laporan akhir = bertanggungan.
    f6kBuatProposalDisetujui($anggota, $skema, ['judul' => 'Proposal disetujui Budi']);

    $response = $this->actingAs($ketua)
        ->post(route('proposal.store'), f6kPayloadSubmit($skema, $fakultas, [
            'anggota' => ['Budi Santoso'],
        ]));

    $response->assertSessionHas('error', 'Anggota Budi Santoso masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat diikutsertakan.');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('skema kuota 0, anggota bersih: submit lolos dan kuota tetap dilewati', function () {
    Storage::fake('public');
    $ketua = f6kBuatPengaju(['name' => 'Dr. Andi']);
    $anggota = f6kBuatPengaju(['name' => 'Budi Santoso']);
    $skema = f6kBuatSkema();
    $fakultas = f6kBuatFakultas();
    f6kBuatPeriodeAktif();

    // Anggota sudah punya 1 keikutsertaan pada skema ini; dengan kuota 0
    // cek kuota dilewati, jadi ini tidak boleh memblokir submit.
    f6kBuatProposalDikirim($anggota, $skema, ['judul' => 'Proposal dikirim Budi']);

    $response = $this->actingAs($ketua)
        ->post(route('proposal.store'), f6kPayloadSubmit($skema, $fakultas, [
            'anggota' => ['Budi Santoso'],
        ]));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('success');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(2);
});

test('skema kuota 0, pengaju sendiri bertanggungan: tetap ditolak', function () {
    Storage::fake('public');
    $user = f6kBuatPengaju();
    $skema = f6kBuatSkema();
    $fakultas = f6kBuatFakultas();
    f6kBuatPeriodeAktif();

    f6kBuatProposalDisetujui($user, $skema);

    $response = $this->actingAs($user)
        ->post(route('proposal.store'), f6kPayloadSubmit($skema, $fakultas));

    $response->assertSessionHas('error', 'Anda masih memiliki tanggungan laporan (laporan akhir belum diunggah atau temuan Monev masih terbuka) sehingga tidak dapat mengajukan proposal baru. Silakan selesaikan kewajiban pelaporan Anda atau hubungi LPPM.');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(0);
});

test('skema kuota normal, anggota sudah habis kuota: tetap ditolak', function () {
    Storage::fake('public');
    $ketua = f6kBuatPengaju(['name' => 'Dr. Andi']);
    $anggota = f6kBuatPengaju(['name' => 'Budi Santoso']);
    $skema = f6kBuatSkema(['kuota' => 2]);
    $fakultas = f6kBuatFakultas();
    f6kBuatPeriodeAktif();

    f6kBuatProposalDikirim($anggota, $skema, ['judul' => 'Proposal dikirim Budi 1']);
    f6kBuatProposalDikirim($anggota, $skema, ['judul' => 'Proposal dikirim Budi 2']);

    $response = $this->actingAs($ketua)
        ->post(route('proposal.store'), f6kPayloadSubmit($skema, $fakultas, [
            'anggota' => ['Budi Santoso'],
        ]));

    $response->assertSessionHas('error', 'Anggota Budi Santoso sudah mencapai kuota skema Pembinaan (2 keikutsertaan).');
    expect(Proposal::where('status', 'Dikirim')->count())->toBe(2);
});
