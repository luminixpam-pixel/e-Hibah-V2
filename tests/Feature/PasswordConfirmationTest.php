<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;

uses(RefreshDatabase::class);

test('confirm password screen can be rendered', function () {
    $user = Features::hasTeamFeatures()
                    ? User::factory()->withPersonalTeam()->create()
                    : User::factory()->create();

    $response = $this->actingAs($user)->get('/user/confirm-password');

    $response->assertStatus(200);
});

test('password can be confirmed', function () {
    // Konfirmasi password Fortify memakai Auth::validate() yang di
    // aplikasi ini lewat provider LDAP — tanpa server LDAP di test,
    // validasi selalu gagal. Pakai provider Eloquent lokal untuk
    // test ini saja (password lokalnya sama, karena sinkron).
    config()->set('auth.providers.users.driver', 'eloquent');
    config()->set('auth.providers.users.model', User::class);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/user/confirm-password', [
        'password' => 'password',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('password is not confirmed with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/user/confirm-password', [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors();
});
