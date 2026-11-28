<?php

// Batasan masa hibah untuk buat/kirim proposal (keputusan user 28 Nov 2026):
// create/store/submit ditolak bila tidak ada periode hibah aktif.
// Helper berprefix bmp* agar tidak bentrok dengan test lain.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function bmpUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function bmpSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function bmpFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function bmpPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function bmpPeriodeKedaluarsa(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2025',
        'start_date' => now()->subMonths(2),
        'end_date' => now()->subDay(),
    ]);
}

function bmpProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji batasan masa pengajuan',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => bmpFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Draft',
        'biaya' => 1000000,
        'file_path' => 'proposal_files/draft.pdf',
    ], $attrs));
}

function bmpPayloadSubmit(Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Proposal uji batasan masa pengajuan',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => bmpFakultas()->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

// --- Di luar periode: tidak ada periode aktif sama sekali ---

test('bmp: simpan draft di luar masa hibah ditolak dan tidak ada proposal tersimpan', function () {
    Storage::fake('public');
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bmpSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), bmpPayloadSubmit($skema, ['aksi' => 'draft']));

    $response->assertSessionHas('error', 'Masa pengajuan proposal telah berakhir.');
    $this->assertDatabaseCount('proposals', 0);
});

test('bmp: submit di luar masa hibah ditolak dan tidak ada proposal tersimpan', function () {
    Storage::fake('public');
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bmpSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), bmpPayloadSubmit($skema));

    $response->assertSessionHas('error', 'Masa pengajuan proposal telah berakhir.');
    $this->assertDatabaseCount('proposals', 0);
});

test('bmp: kirim draft dari daftar di luar masa hibah ditolak, status tetap Draft', function () {
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);
    $proposal = bmpProposal($pengaju, bmpSkema());

    $response = $this->actingAs($pengaju)->post(route('proposal.kirim', $proposal->id));

    $response->assertSessionHas('error', 'Masa pengajuan proposal telah berakhir.');
    expect($proposal->fresh()->status)->toBe('Draft');
    expect($proposal->fresh()->no_pengajuan)->toBeNull();
});

test('bmp: form buat proposal di luar masa hibah ditolak', function () {
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->get(route('proposal.create'));

    $response->assertRedirect(route('proposal.index'));
    $response->assertSessionHas('error', 'Masa pengajuan proposal telah berakhir.');
});

// --- Periode sudah kedaluarsa ---

test('bmp: submit saat periode sudah kedaluarsa ditolak dan tidak ada proposal tersimpan', function () {
    Storage::fake('public');
    bmpPeriodeKedaluarsa();
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bmpSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), bmpPayloadSubmit($skema));

    $response->assertSessionHas('error', 'Masa pengajuan proposal telah berakhir.');
    $this->assertDatabaseCount('proposals', 0);
});

// --- Di dalam periode aktif: pengajuan lolos ---

test('bmp: submit di dalam masa hibah lolos dan proposal tersimpan sebagai Dikirim', function () {
    Storage::fake('public');
    bmpPeriodeAktif();
    $pengaju = bmpUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = bmpSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), bmpPayloadSubmit($skema));

    $response->assertSessionHas('success');
    $proposal = Proposal::firstOrFail();
    expect($proposal->status)->toBe('Dikirim')
        ->and($proposal->periode_id)->toBe(HibahPeriod::firstOrFail()->id);
});
