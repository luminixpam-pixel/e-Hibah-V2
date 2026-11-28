<?php

// Periode aktif yang tumpang tindih (28 Nov 2026): activePeriod() harus
// memilih secara deterministik — start_date terbaru, lalu end_date
// terbaru, lalu id terbesar. Helper berprefix pat* agar tidak bentrok
// dengan test lain.

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function patUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function patSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function patFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function patPeriode(string $nama, $mulai, $akhir): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => $nama,
        'start_date' => $mulai,
        'end_date' => $akhir,
    ]);
}

function patPayloadSubmit(Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Proposal uji periode tumpang tindih',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => patFakultas()->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

test('dua periode aktif yang tumpang tindih: proposal menempel ke periode dengan start_date terbaru', function () {
    Storage::fake('public');
    $lama = patPeriode('Periode Lama', now()->subMonths(2), now()->addMonth());
    $baru = patPeriode('Periode Baru', now()->subMonth(), now()->addMonths(2));
    $pengaju = patUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = patSkema();

    $this->actingAs($pengaju)
        ->post(route('proposal.store'), patPayloadSubmit($skema))
        ->assertSessionHas('success');

    $proposal = Proposal::firstOrFail();
    expect($proposal->periode_id)->toBe($baru->id)
        ->and($proposal->periode_id)->not->toBe($lama->id);
});

test('start_date sama: periode dengan end_date terbaru yang dipilih', function () {
    Storage::fake('public');
    $pendek = patPeriode('Periode Pendek', now()->subMonth(), now()->addMonth());
    $panjang = patPeriode('Periode Panjang', now()->subMonth(), now()->addMonths(3));
    $pengaju = patUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = patSkema();

    $this->actingAs($pengaju)
        ->post(route('proposal.store'), patPayloadSubmit($skema))
        ->assertSessionHas('success');

    expect(Proposal::firstOrFail()->periode_id)->toBe($panjang->id)
        ->and(Proposal::firstOrFail()->periode_id)->not->toBe($pendek->id);
});

test('tanggal sama persis: periode yang dibuat belakangan (id terbesar) yang dipilih', function () {
    Storage::fake('public');
    $pertama = patPeriode('Periode Pertama', now()->subMonth(), now()->addMonth());
    $kedua = patPeriode('Periode Kedua', now()->subMonth(), now()->addMonth());
    $pengaju = patUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = patSkema();

    $this->actingAs($pengaju)
        ->post(route('proposal.store'), patPayloadSubmit($skema))
        ->assertSessionHas('success');

    expect(Proposal::firstOrFail()->periode_id)->toBe($kedua->id)
        ->and(Proposal::firstOrFail()->periode_id)->not->toBe($pertama->id);
});
