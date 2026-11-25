<?php

use App\Models\Fakultas;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('idForPenempatan memetakan nama fakultas persis', function () {
    $fakultas = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);

    expect(Fakultas::idForPenempatan('Fakultas Teknologi Informasi'))->toBe($fakultas->id);
});

test('idForPenempatan memetakan program studi ke fakultas', function () {
    $fti = Fakultas::create(['nama_fakultas' => 'Fakultas Teknologi Informasi']);
    $fk = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran']);
    $fkg = Fakultas::create(['nama_fakultas' => 'Fakultas Kedokteran Gigi']);

    expect(Fakultas::idForPenempatan('S1 - Teknik Informatika'))->toBe($fti->id);
    expect(Fakultas::idForPenempatan('S1 - Kedokteran'))->toBe($fk->id);
    expect(Fakultas::idForPenempatan('Profesi - Profesi Dokter Gigi'))->toBe($fkg->id);
    expect(Fakultas::idForPenempatan('S2 - Administrasi Rumah Sakit'))->toBeNull();
    expect(Fakultas::idForPenempatan(null))->toBeNull();
    expect(Fakultas::idForPenempatan(''))->toBeNull();
});
