<?php

/**
 * Keputusan audit keamanan 28 Nov 2026 — area PERIODE + AKUN + TENGGAT REVIEW.
 *
 * Butir 12: BOLEH banyak periode aktif; updatePeriod mengupdate periode
 *   yang dimaksud (period_id), bukan selalu yang pertama.
 * Butir 14: ADMIN TIDAK BOLEH hapus akun sendiri; user non-admin tetap bisa.
 * Penjaga tenggat review: isiReview & submitReview menolak setelah tenggat.
 *
 * Catatan: PHP tidak tersedia di VM ini, jadi file ini BELUM dijalankan.
 */

use App\Models\Fakultas;
use App\Models\HibahPeriod;
use App\Models\Proposal;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Laravel\Jetstream\Http\Livewire\DeleteUserForm;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function kaaBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

function kaaBuatPeriode(string $nama, string $mulai, string $akhir): HibahPeriod
{
    return HibahPeriod::create([
        'nama' => $nama,
        'start_date' => $mulai,
        'end_date' => $akhir,
    ]);
}

function kaaBuatSkema(array $attrs = []): Skema
{
    return Skema::create(array_merge([
        'kategori' => 'Pembinaan',
        'plafon' => 25000000,
        'kuota' => 2,
        'deskripsi' => 'Skema pembinaan.',
        'syarat' => 'Dosen aktif.',
    ], $attrs));
}

function kaaBuatProposalUntukReview(User $pengaju, Skema $skema, array $attrs = []): Proposal
{
    $fakultas = Fakultas::first() ?? Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    return Proposal::create(array_merge([
        'user_id' => $pengaju->id,
        'judul' => 'Proposal untuk direview',
        'nama_ketua' => $pengaju->name,
        'fakultas_prodi' => $fakultas->id,
        'skema_id' => $skema->id,
        'status' => 'Sedang Direview',
        'biaya' => 1000000,
        'review_deadline' => now()->addDays(7),
    ], $attrs));
}

function kaaPayloadReview(array $attrs = []): array
{
    return array_merge([
        'nilai_1' => 4,
        'nilai_2' => 4,
        'nilai_3' => 4,
        'nilai_4' => 4,
        'nilai_5' => 4,
        'nilai_6' => 4,
        'nilai_7' => 4,
    ], $attrs);
}

/* ------------------------------------------------------------------ */
/* Butir 12 — banyak periode aktif, updatePeriod tepat sasaran          */
/* ------------------------------------------------------------------ */

test('butir 12: updatePeriod dengan period_id mengupdate periode yang dimaksud, bukan yang pertama', function () {
    $admin = kaaBuatUser('admin');
    $pertama = kaaBuatPeriode('Periode 2025', '2025-02-01', '2025-04-30');
    $kedua = kaaBuatPeriode('Periode 2026', '2026-02-01', '2026-04-30');

    $response = $this->actingAs($admin)->post(route('monitoring.kalender.periode'), [
        'period_id' => $kedua->id,
        'start_date' => '2026-03-01',
        'end_date' => '2026-05-31',
    ]);

    $response->assertRedirect(route('monitoring.kalender'));
    $response->assertSessionHas('success');

    // Periode kedua berubah ...
    expect($kedua->fresh()->start_date->format('Y-m-d'))->toBe('2026-03-01');
    expect($kedua->fresh()->end_date->format('Y-m-d'))->toBe('2026-05-31');
    // ... periode pertama tidak tersentuh.
    expect($pertama->fresh()->start_date->format('Y-m-d'))->toBe('2025-02-01');
    expect($pertama->fresh()->end_date->format('Y-m-d'))->toBe('2025-04-30');
});

test('butir 12: dua periode aktif bisa hidup berdampingan (tanpa validasi satu-periode-aktif)', function () {
    $admin = kaaBuatUser('admin');

    // Dua periode dengan tanggal tumpang tindih: tidak ada penolakan.
    kaaBuatPeriode('Periode A', '2026-02-01', '2026-04-30');
    kaaBuatPeriode('Periode B', '2026-03-01', '2026-05-31');

    expect(HibahPeriod::count())->toBe(2);

    // Halaman kalender tetap terbuka dan menampilkan pemilih periode.
    $this->actingAs($admin)
        ->get(route('monitoring.kalender'))
        ->assertOk()
        ->assertSee('Periode A')
        ->assertSee('Periode B');
});

test('butir 12: updatePeriod tanpa period_id mempertahankan perilaku lama (update periode pertama)', function () {
    $admin = kaaBuatUser('admin');
    $pertama = kaaBuatPeriode('Periode 2025', '2025-02-01', '2025-04-30');
    $kedua = kaaBuatPeriode('Periode 2026', '2026-02-01', '2026-04-30');

    $this->actingAs($admin)->post(route('monitoring.kalender.periode'), [
        'start_date' => '2025-02-15',
        'end_date' => '2025-05-15',
    ])->assertRedirect(route('monitoring.kalender'));

    expect($pertama->fresh()->start_date->format('Y-m-d'))->toBe('2025-02-15');
    expect($kedua->fresh()->start_date->format('Y-m-d'))->toBe('2026-02-01');
});

test('butir 12: updatePeriod menolak period_id yang tidak ada', function () {
    $admin = kaaBuatUser('admin');
    $periode = kaaBuatPeriode('Periode 2025', '2025-02-01', '2025-04-30');

    $this->actingAs($admin)->post(route('monitoring.kalender.periode'), [
        'period_id' => 999999,
        'start_date' => '2025-02-15',
        'end_date' => '2025-05-15',
    ])->assertSessionHasErrors('period_id');

    expect($periode->fresh()->start_date->format('Y-m-d'))->toBe('2025-02-01');
});

test('butir 12: updatePeriod tanpa periode sama sekali membuat periode baru', function () {
    $admin = kaaBuatUser('admin');

    $this->actingAs($admin)->post(route('monitoring.kalender.periode'), [
        'start_date' => '2026-02-01',
        'end_date' => '2026-04-30',
    ])->assertRedirect(route('monitoring.kalender'));

    expect(HibahPeriod::count())->toBe(1);
    expect(HibahPeriod::first()->start_date->format('Y-m-d'))->toBe('2026-02-01');
});

test('butir 12: non-admin tidak bisa mengubah periode lewat kalender', function () {
    $pengaju = kaaBuatUser('pengaju');
    $periode = kaaBuatPeriode('Periode 2025', '2025-02-01', '2025-04-30');

    $this->actingAs($pengaju)->post(route('monitoring.kalender.periode'), [
        'period_id' => $periode->id,
        'start_date' => '2025-02-15',
        'end_date' => '2025-05-15',
    ])->assertForbidden();

    expect($periode->fresh()->start_date->format('Y-m-d'))->toBe('2025-02-01');
});

/* ------------------------------------------------------------------ */
/* Butir 14 — admin tidak boleh hapus akun sendiri                      */
/* ------------------------------------------------------------------ */

test('butir 14: admin menghapus akun sendiri ditolak dan akun tetap ada', function () {
    $this->actingAs($admin = kaaBuatUser('admin'));

    Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser')
        ->assertHasErrors(['password']);

    expect($admin->fresh())->not->toBeNull();
})->skip(function () {
    return ! Features::hasAccountDeletionFeatures();
}, 'Account deletion is not enabled.');

test('butir 14: user non-admin tetap bisa menghapus akunnya sendiri', function () {
    $this->actingAs($user = kaaBuatUser('pengaju'));

    Livewire::test(DeleteUserForm::class)
        ->set('password', 'password')
        ->call('deleteUser');

    expect($user->fresh())->toBeNull();
})->skip(function () {
    return ! Features::hasAccountDeletionFeatures();
}, 'Account deletion is not enabled.');

/* ------------------------------------------------------------------ */
/* Penjaga tenggat review (ReviewerController baris 212-214 & 252-254) */
/* ------------------------------------------------------------------ */

test('tenggat review: isi-review setelah tenggat ditolak', function () {
    $pengaju = kaaBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kaaBuatUser('reviewer', ['name' => 'Sinta']);
    $proposal = kaaBuatProposalUntukReview($pengaju, kaaBuatSkema(), [
        'review_deadline' => now()->subDay(),
    ]);
    $proposal->reviewers()->sync([$reviewer->id]);

    $this->actingAs($reviewer)
        ->get(route('reviewer.isi-review', $proposal->id))
        ->assertRedirect()
        ->assertSessionHas('error', 'Tenggat penilaian proposal ini sudah lewat.');
});

test('tenggat review: submit setelah tenggat ditolak dan review tidak tersimpan', function () {
    $pengaju = kaaBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kaaBuatUser('reviewer', ['name' => 'Sinta']);
    $proposal = kaaBuatProposalUntukReview($pengaju, kaaBuatSkema(), [
        'review_deadline' => now()->subHour(),
    ]);
    $proposal->reviewers()->sync([$reviewer->id]);

    $this->actingAs($reviewer)
        ->post(route('review.simpan', $proposal->id), kaaPayloadReview())
        ->assertRedirect()
        ->assertSessionHas('error', 'Tenggat penilaian proposal ini sudah lewat.');

    $this->assertDatabaseMissing('reviews', [
        'proposal_id' => $proposal->id,
        'reviewer_id' => $reviewer->id,
    ]);
    expect($proposal->fresh()->status)->toBe('Sedang Direview');
});

test('tenggat review: sebelum tenggat, form isi-review terbuka dan menampilkan tenggat', function () {
    $pengaju = kaaBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kaaBuatUser('reviewer', ['name' => 'Sinta']);
    $proposal = kaaBuatProposalUntukReview($pengaju, kaaBuatSkema(), [
        'review_deadline' => now()->addDays(3),
    ]);
    $proposal->reviewers()->sync([$reviewer->id]);

    $this->actingAs($reviewer)
        ->get(route('reviewer.isi-review', $proposal->id))
        ->assertOk()
        ->assertSee('Tenggat Waktu');
});

test('tenggat review: tenggat kurang dari 24 jam menampilkan peringatan mendesak', function () {
    $pengaju = kaaBuatUser('pengaju', ['name' => 'Dr. Andi']);
    $reviewer = kaaBuatUser('reviewer', ['name' => 'Sinta']);
    $proposal = kaaBuatProposalUntukReview($pengaju, kaaBuatSkema(), [
        'review_deadline' => now()->addHours(5),
    ]);
    $proposal->reviewers()->sync([$reviewer->id]);

    $this->actingAs($reviewer)
        ->get(route('reviewer.isi-review', $proposal->id))
        ->assertOk()
        ->assertSee('kurang dari 24 jam lagi');
});
