<?php

// Keputusan audit keamanan 28 Nov 2026, butir 10, 11, 15
// (area Proposal + Monitoring + RAB).
// Helper berprefix ka* agar tidak bentrok dengan test lain.

use App\Helpers\ParseJumlah;
use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\ProposalMember;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function kaUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function kaSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function kaFakultas(): Fakultas
{
    return Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
}

function kaPeriodeAktif(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);
}

function kaPeriodeKedaluarsa(): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => 'Periode 2025',
        'start_date' => now()->subMonths(2),
        'end_date' => now()->subDay(),
    ]);
}

function kaProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal uji keputusan audit',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => kaFakultas()->id,
        'skema_id' => $skema->id,
        'status' => 'Draft',
        'biaya' => 1000000,
        'file_path' => 'proposal_files/lama.pdf',
    ], $attrs));
}

function kaPayloadSubmit(Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'submit',
        'judul' => 'Proposal RAB uji',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => kaFakultas()->id,
        'skema_id' => $skema->id,
        'file' => UploadedFile::fake()->create('proposal.pdf', 100, 'application/pdf'),
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

function kaPayloadUpdate(Proposal $proposal, Skema $skema, array $override = []): array
{
    return array_merge([
        'aksi' => 'draft',
        'judul' => 'Proposal uji keputusan audit',
        'nama_ketua' => 'Dr. Andi',
        'fakultas_prodi' => $proposal->fakultas_prodi,
        'skema_id' => $skema->id,
        'rab' => [
            ['nama_komponen' => 'Honorarium', 'jumlah' => 1000000, 'keterangan' => ''],
        ],
        'anggota' => [],
    ], $override);
}

// --- Butir 10: halaman daftar monitoring dibatasi per role ---

test('ka10: pengaju ditolak 403 di halaman antrean staf (perlu direview, sedang direview, daftar review)', function () {
    $pengaju = kaUser('pengaju');

    $this->actingAs($pengaju)->get(route('monitoring.proposalPerluDireview'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('monitoring.proposalSedangDireview'))->assertForbidden();
    $this->actingAs($pengaju)->get(route('monitoring.daftarReview'))->assertForbidden();
});

test('ka10: staf lolos di semua halaman daftar monitoring', function () {
    $staf = [
        kaUser('admin'),
        kaUser('verifikator'),
        kaUser('lppm'),
        kaUser('dekan'),
        kaUser('kepala_lppm'),
    ];

    $rute = [
        'monitoring.proposalPerluDireview',
        'monitoring.proposalSedangDireview',
        'monitoring.reviewSelesai',
        'monitoring.proposalDisetujui',
        'monitoring.proposalDitolak',
        'monitoring.proposalDirevisi',
        'monitoring.hasilRevisi',
        'monitoring.daftarReview',
    ];

    foreach ($staf as $user) {
        foreach ($rute as $route) {
            $this->actingAs($user)->get(route($route))->assertOk();
        }
    }
});

test('ka10: pengaju tetap bisa membuka halaman proposal disetujui tapi hanya melihat miliknya sendiri', function () {
    $andi = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $citra = kaUser('pengaju', ['name' => 'Dr. Citra']);
    $skema = kaSkema();
    $milikAndi = kaProposal($andi, $skema, ['judul' => 'Proposal disetujui Andi', 'status' => 'Disetujui']);
    $milikCitra = kaProposal($citra, $skema, ['judul' => 'Proposal disetujui Citra', 'status' => 'Disetujui']);

    $response = $this->actingAs($andi)->get(route('monitoring.proposalDisetujui'));

    $response->assertOk();
    $response->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($milikAndi->id)
        && ! $items->pluck('id')->contains($milikCitra->id));
    $response->assertSee('Proposal disetujui Andi');
    $response->assertDontSee('Proposal disetujui Citra');
});

test('ka10: anggota tim yang diterima ikut melihat proposal disetujui itu di daftarnya', function () {
    $andi = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $budi = kaUser('pengaju', ['name' => 'Budi Santoso']);
    $skema = kaSkema();
    $proposal = kaProposal($andi, $skema, ['judul' => 'Proposal disetujui tim', 'status' => 'Disetujui']);

    ProposalMember::create([
        'proposal_id' => $proposal->id,
        'user_id' => $budi->id,
        'peran' => 'Anggota',
        'status_konfirmasi' => 'Diterima',
    ]);

    $this->actingAs($budi)->get(route('monitoring.proposalDisetujui'))
        ->assertOk()
        ->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($proposal->id));
});

test('ka10: pengaju hanya melihat proposal ditolak dan review selesai miliknya sendiri', function () {
    $andi = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $citra = kaUser('pengaju', ['name' => 'Dr. Citra']);
    $skema = kaSkema();
    $ditolakAndi = kaProposal($andi, $skema, ['judul' => 'Proposal ditolak Andi', 'status' => 'Ditolak']);
    $ditolakCitra = kaProposal($citra, $skema, ['judul' => 'Proposal ditolak Citra', 'status' => 'Ditolak']);
    $selesaiAndi = kaProposal($andi, $skema, ['judul' => 'Proposal selesai Andi', 'status' => 'Review Selesai']);
    $selesaiCitra = kaProposal($citra, $skema, ['judul' => 'Proposal selesai Citra', 'status' => 'Review Selesai']);

    $this->actingAs($andi)->get(route('monitoring.proposalDitolak'))
        ->assertOk()
        ->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($ditolakAndi->id)
            && ! $items->pluck('id')->contains($ditolakCitra->id));

    $this->actingAs($andi)->get(route('monitoring.reviewSelesai'))
        ->assertOk()
        ->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($selesaiAndi->id)
            && ! $items->pluck('id')->contains($selesaiCitra->id));
});

test('ka10: reviewer hanya melihat proposal yang ditugaskan kepadanya', function () {
    $reviewer = kaUser('reviewer', ['name' => 'Sinta']);
    $ketua = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $ditugaskan = kaProposal($ketua, $skema, ['judul' => 'Proposal review Sinta', 'status' => 'Sedang Direview']);
    $lain = kaProposal($ketua, $skema, ['judul' => 'Proposal review orang lain', 'status' => 'Sedang Direview']);
    $ditugaskan->reviewers()->sync([$reviewer->id]);

    $this->actingAs($reviewer)->get(route('monitoring.proposalSedangDireview'))
        ->assertOk()
        ->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($ditugaskan->id)
            && ! $items->pluck('id')->contains($lain->id));

    $disetujui = kaProposal($ketua, $skema, ['judul' => 'Proposal disetujui Sinta', 'status' => 'Disetujui']);
    $disetujui->reviewers()->sync([$reviewer->id]);
    $disetujuiLain = kaProposal($ketua, $skema, ['judul' => 'Proposal disetujui lain', 'status' => 'Disetujui']);

    $this->actingAs($reviewer)->get(route('monitoring.proposalDisetujui'))
        ->assertOk()
        ->assertViewHas('proposals', fn ($items) => $items->pluck('id')->contains($disetujui->id)
            && ! $items->pluck('id')->contains($disetujuiLain->id));
});

// --- Butir 11: update() disamakan dengan edit() soal masa hibah ---

test('ka11: update di luar masa hibah ditolak (tidak ada periode aktif)', function () {
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $proposal = kaProposal($pengaju, $skema, ['status' => 'Draft', 'judul' => 'Judul lama']);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->put(route('proposal.update', $proposal->id), kaPayloadUpdate($proposal, $skema, [
            'judul' => 'Judul diubah di luar masa hibah',
        ]));

    $response->assertSessionHas('error', 'Masa pengeditan proposal telah berakhir.');
    expect($proposal->fresh()->judul)->toBe('Judul lama');
});

test('ka11: update di luar masa hibah ditolak (periode sudah kedaluarsa)', function () {
    kaPeriodeKedaluarsa();
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $proposal = kaProposal($pengaju, $skema, ['status' => 'Draft', 'judul' => 'Judul lama']);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->put(route('proposal.update', $proposal->id), kaPayloadUpdate($proposal, $skema, [
            'judul' => 'Judul diubah setelah periode berakhir',
        ]));

    $response->assertSessionHas('error', 'Masa pengeditan proposal telah berakhir.');
    expect($proposal->fresh()->judul)->toBe('Judul lama');
});

test('ka11: update di dalam masa hibah lolos', function () {
    kaPeriodeAktif();
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $proposal = kaProposal($pengaju, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), kaPayloadUpdate($proposal, $skema, [
            'judul' => 'Judul revisi di masa hibah',
        ]));

    $response->assertSessionHas('success');
    expect($proposal->fresh()->judul)->toBe('Judul revisi di masa hibah');
});

test('ka11: halaman edit di luar masa hibah tetap ditolak dengan pesan yang sama', function () {
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $proposal = kaProposal($pengaju, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($pengaju)
        ->from(route('proposal.index'))
        ->get(route('proposal.edit', $proposal->id));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Masa pengeditan proposal telah berakhir.');
});

// --- Butir 15: parsing jumlah RAB ---

test('ka15: ParseJumlah menangani notasi ilmiah, format Indonesia, dan awalan Rp', function () {
    expect(ParseJumlah::parse('1e5'))->toBe(100000.0)
        ->and(ParseJumlah::parse('1.000.000'))->toBe(1000000.0)
        ->and(ParseJumlah::parse('Rp 1.500.000'))->toBe(1500000.0)
        ->and(ParseJumlah::parse('Rp1.500.000'))->toBe(1500000.0)
        ->and(ParseJumlah::parse('1500000'))->toBe(1500000.0)
        ->and(ParseJumlah::parse('1.500.000,50'))->toBe(1500000.5)
        ->and(ParseJumlah::parse(1500000))->toBe(1500000.0)
        ->and(ParseJumlah::parse(0))->toBe(0.0);
});

test('ka15: ParseJumlah menolak format yang tidak jelas', function () {
    expect(ParseJumlah::parse('1,5 juta'))->toBeNull()
        ->and(ParseJumlah::parse('satu juta'))->toBeNull()
        ->and(ParseJumlah::parse('1,500'))->toBeNull()
        ->and(ParseJumlah::parse(''))->toBeNull()
        ->and(ParseJumlah::parse(null))->toBeNull()
        ->and(ParseJumlah::parse([]))->toBeNull();
});

test('ka15: submit dengan jumlah 1e5 menyimpan 100000 di item RAB dan biaya proposal', function () {
    Storage::fake('public');
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), kaPayloadSubmit($skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => '1e5', 'keterangan' => ''],
            ],
        ]));

    $response->assertSessionHas('success');
    $proposal = Proposal::firstOrFail();
    expect((int) $proposal->biaya)->toBe(100000)
        ->and((float) $proposal->rabItems()->first()->jumlah)->toBe(100000.0);
});

test('ka15: submit dengan jumlah 1.000.000 dan Rp 1.500.000 menyimpan nilai penuhnya', function () {
    Storage::fake('public');
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();

    $this->actingAs($pengaju)
        ->post(route('proposal.store'), kaPayloadSubmit($skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => '1.000.000', 'keterangan' => ''],
            ],
        ]))->assertSessionHas('success');
    expect((int) Proposal::firstOrFail()->biaya)->toBe(1000000);

    $pengaju2 = kaUser('pengaju', ['name' => 'Dr. Citra']);
    $this->actingAs($pengaju2)
        ->post(route('proposal.store'), kaPayloadSubmit($skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => 'Rp 1.500.000', 'keterangan' => ''],
            ],
        ]))->assertSessionHas('success');
    expect((int) Proposal::where('user_id', $pengaju2->id)->firstOrFail()->biaya)->toBe(1500000);
});

test('ka15: submit dengan jumlah 1,5 juta ditolak validasi', function () {
    Storage::fake('public');
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), kaPayloadSubmit($skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => '1,5 juta', 'keterangan' => ''],
            ],
        ]));

    $response->assertSessionHasErrors(['rab.0.jumlah' => 'Jumlah RAB harus berupa angka.']);
    $this->assertDatabaseCount('proposals', 0);
});

test('ka15: plafon ditegakkan dengan nilai hasil parsing — 3e7 ditolak sebagai Rp 30.000.000', function () {
    Storage::fake('public');
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), kaPayloadSubmit($skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => '3e7', 'keterangan' => ''],
            ],
        ]));

    // Sebelum perbaikan, '3e7' dihitung sebagai 37 dan lolos plafon.
    $response->assertSessionHas('error', 'Total RAB Rp 30.000.000 melebihi plafon skema Pembinaan (Rp 25.000.000).');
    $this->assertDatabaseCount('proposals', 0);
});

test('ka15: update draft dengan jumlah 1.000.000 menyimpan nilai penuhnya', function () {
    kaPeriodeAktif();
    $pengaju = kaUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = kaSkema();
    $proposal = kaProposal($pengaju, $skema, ['status' => 'Draft']);

    $response = $this->actingAs($pengaju)
        ->put(route('proposal.update', $proposal->id), kaPayloadUpdate($proposal, $skema, [
            'rab' => [
                ['nama_komponen' => 'Honorarium', 'jumlah' => '1.000.000', 'keterangan' => ''],
            ],
        ]));

    $response->assertSessionHas('success');
    expect((int) $proposal->fresh()->biaya)->toBe(1000000)
        ->and((float) $proposal->fresh()->rabItems()->first()->jumlah)->toBe(1000000.0);
});
