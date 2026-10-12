<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

it('locks after three failed attempts and sets lock expiry', function () {
    $user = User::factory()->create([
        'email' => 'tf@example.test',
        'password' => Hash::make('password'),
        'two_factor_enabled' => true,
        'two_factor_code' => '123456',
        'two_factor_expires_at' => now()->addMinutes(5),
    ]);

    // Simulate session holding the two_factor_user_id
    session(['two_factor_user_id' => $user->id]);

    // Make 3 wrong attempts
    for ($i = 0; $i < 3; $i++) {
        $response = $this->post(route('two-factor.verify'), ['code' => '000000']);
    }

    $response->assertSessionHas('isLocked', true);

    $user->refresh();

    expect($user->two_factor_lock_count)->toBeGreaterThanOrEqual(1);
    expect($user->two_factor_lock_expires_at)->toBeTruthy();
});

it('prevents verification while locked', function () {
    $user = User::factory()->create([
        'email' => 'tf2@example.test',
        'password' => Hash::make('password'),
        'two_factor_enabled' => true,
        'two_factor_code' => '654321',
        'two_factor_expires_at' => now()->addMinutes(5),
        'two_factor_lock_expires_at' => now()->addMinutes(10),
        'two_factor_lock_count' => 1,
    ]);

    session(['two_factor_user_id' => $user->id]);

    $response = $this->post(route('two-factor.verify'), ['code' => '654321']);

    // Even with correct code, locked user should get an error and isLocked in session
    $response->assertSessionHas('isLocked', true);
    $response->assertSessionHasErrors();
});
