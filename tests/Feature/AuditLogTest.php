<?php

use App\Models\ActivityLog;
use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function f6hUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f6hSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f6hProposal(User $user, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal lama',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

function f6hPayloadSubmit(Skema $skema, Fakultas $fakultas, array $override = []): array
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
        ],
        'anggota' => [],
    ], $override);
}

test('admin bisa membuka halaman audit log dan melihat aktivitas tercatat', function () {
    $admin = f6hUser('admin');
    ActivityLog::create([
        'user_id' => $admin->id,
        'activity' => 'Mengajukan proposal ID 1 (status: Dikirim)',
        'ip_address' => '127.0.0.1',
        'user_agent' => 'test',
    ]);

    $response = $this->actingAs($admin)->get(route('admin.audit-log.index'));

    $response->assertOk();
    $response->assertSee('Mengajukan proposal ID 1 (status: Dikirim)');
    $response->assertSee($admin->name);
    $response->assertSee('127.0.0.1');
});

test('pengaju tidak bisa membuka halaman audit log', function () {
    $pengaju = f6hUser('pengaju');

    $response = $this->actingAs($pengaju)->get(route('admin.audit-log.index'));

    $response->assertForbidden();
});

test('lppm tidak bisa membuka halaman audit log', function () {
    $lppm = f6hUser('lppm');

    $response = $this->actingAs($lppm)->get(route('admin.audit-log.index'));

    $response->assertForbidden();
});

test('submit proposal tercatat di audit log', function () {
    Storage::fake('public');
    $pengaju = f6hUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f6hSkema();
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    HibahPeriod::create([
        'nama' => 'Periode 2026',
        'start_date' => now()->subMonth(),
        'end_date' => now()->addMonth(),
    ]);

    $response = $this->actingAs($pengaju)
        ->post(route('proposal.store'), f6hPayloadSubmit($skema, $fakultas));

    $response->assertRedirect(route('proposal.index'));

    $proposal = Proposal::where('judul', 'Pengembangan Sistem E-Hibah')->firstOrFail();
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $pengaju->id,
        'activity' => "Mengajukan proposal ID {$proposal->id} (status: Dikirim)",
    ]);
});

test('submit review tercatat di audit log', function () {
    $pengaju = f6hUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = f6hUser('reviewer', ['name' => 'Sinta']);
    $skema = f6hSkema();
    $proposal = f6hProposal($pengaju, $skema, ['status' => 'Sedang Direview']);
    $proposal->reviewers()->sync([$reviewer->id]);

    // Semua nilai 4: skor murni 4 x 38 = 152; 152/190 x 500 = 400.
    $response = $this->actingAs($reviewer)->post(route('review.simpan', $proposal->id), [
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
        'catatan' => 'Bagus.',
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $reviewer->id,
        'activity' => "Reviewer mengirim review untuk proposal ID {$proposal->id} (skor: 400)",
    ]);
});

test('verifikasi tercatat di audit log', function () {
    $pengaju = f6hUser('pengaju', ['name' => 'Dr. Andi']);
    $verifikator = f6hUser('verifikator', ['name' => 'Veri']);
    $skema = f6hSkema();
    $proposal = f6hProposal($pengaju, $skema);

    $response = $this->actingAs($verifikator)
        ->post(route('verifikasi.store', $proposal->id), [
            'hasil' => 'Lolos',
            'catatan' => 'Berkas lengkap.',
        ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $verifikator->id,
        'activity' => 'Verifikator Veri meloloskan proposal "Proposal lama" (Lolos Administrasi)',
    ]);
});
