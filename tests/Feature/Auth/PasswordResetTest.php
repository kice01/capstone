<?php

use App\Models\User;
use App\Mail\PasswordResetCodeMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password code can be requested by email', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect(route('password.reset.code'));

    Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo($user->email)
        && preg_match('/^\d{8}$/', $mail->code) === 1);
});

test('reset password screen can be rendered', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect(route('password.reset.code'));

    $this->get('/reset-password-code')
        ->assertOk()
        ->assertSee('8-digit reset code');
});

test('password can be reset with a valid email code', function () {
    Mail::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);
    $mail = Mail::sent(PasswordResetCodeMail::class)->first();
    expect($mail)->not->toBeNull();

    $this->post('/reset-password-code', [
            'email' => $user->email,
            'code' => $mail->code,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

    expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue();
});
