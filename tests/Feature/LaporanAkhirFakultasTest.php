<?php

use App\Models\Fakultas;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f4eUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function f4eSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function f4eProposal(User $user, Skema $skema, Fakultas $fakultas, array $attrs = []): Proposal
{
    return Proposal::create(array_merge([
        'user_id' => $user->id,
        'judul' => 'Proposal laporan akhir',
        'nama_ketua' => $user->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Dikirim',
        'biaya' => 1000000,
    ], $attrs));
}

test('dasbor dekan menampilkan laporan akhir fakultasnya beserta tautan unduh', function () {
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $dekan = f4eUser('dekan', ['name' => 'Dekan TI', 'fakultas_id' => $fakultasA->id]);
    $pengaju = f4eUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f4eSkema();
    $proposal = f4eProposal($pengaju, $skema, $fakultasA, [
        'judul' => 'Laporan akhir AI',
        'file_laporan_akhir' => 'laporan_akhir/laporan-ai.pdf',
    ]);

    $response = $this->actingAs($dekan)->get(route('dashboard.dekan'));

    $response->assertOk();
    $response->assertSee('Laporan Akhir Fakultas');
    $response->assertSee('Laporan akhir AI');
    $response->assertSee(route('laporan.akhir.download', $proposal->id));
});

test('dasbor dekan tidak menampilkan laporan akhir dari fakultas lain', function () {
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $fakultasB = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    $dekan = f4eUser('dekan', ['name' => 'Dekan TI', 'fakultas_id' => $fakultasA->id]);
    $pengaju = f4eUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f4eSkema();
    $proposalB = f4eProposal($pengaju, $skema, $fakultasB, [
        'judul' => 'Laporan akhir Kedokteran',
        'file_laporan_akhir' => 'laporan_akhir/laporan-kedokteran.pdf',
    ]);

    $response = $this->actingAs($dekan)->get(route('dashboard.dekan'));

    $response->assertOk();
    $response->assertDontSee('Laporan akhir Kedokteran');
    $response->assertDontSee(route('laporan.akhir.download', $proposalB->id));
    $response->assertSee('Belum ada laporan akhir di fakultas Anda.');
});

test('proposal tanpa file laporan akhir tampil di daftar proposal tapi tidak di seksi Laporan Akhir', function () {
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $dekan = f4eUser('dekan', ['name' => 'Dekan TI', 'fakultas_id' => $fakultasA->id]);
    $pengaju = f4eUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f4eSkema();
    $proposal = f4eProposal($pengaju, $skema, $fakultasA, [
        'judul' => 'Proposal tanpa laporan',
    ]);

    $response = $this->actingAs($dekan)->get(route('dashboard.dekan'));

    $response->assertOk();
    $response->assertSee('Proposal tanpa laporan');
    $response->assertSee(route('proposal.tinjau', $proposal->id));
    $response->assertDontSee(route('laporan.akhir.download', $proposal->id));
    $response->assertSee('Belum ada laporan akhir di fakultas Anda.');
});

test('dekan tanpa fakultas_id tetap melihat semua laporan akhir', function () {
    $fakultasA = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $fakultasB = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    $dekan = f4eUser('dekan', ['name' => 'Dekan']);
    $pengaju = f4eUser('pengaju', ['name' => 'Dr. Andi']);
    $skema = f4eSkema();
    $proposalA = f4eProposal($pengaju, $skema, $fakultasA, [
        'judul' => 'Laporan akhir AI',
        'file_laporan_akhir' => 'laporan_akhir/laporan-ai.pdf',
    ]);
    $proposalB = f4eProposal($pengaju, $skema, $fakultasB, [
        'judul' => 'Laporan akhir Kedokteran',
        'file_laporan_akhir' => 'laporan_akhir/laporan-kedokteran.pdf',
    ]);

    $response = $this->actingAs($dekan)->get(route('dashboard.dekan'));

    $response->assertOk();
    $response->assertSee('Laporan akhir AI');
    $response->assertSee('Laporan akhir Kedokteran');
    $response->assertSee(route('laporan.akhir.download', $proposalA->id));
    $response->assertSee(route('laporan.akhir.download', $proposalB->id));
});
