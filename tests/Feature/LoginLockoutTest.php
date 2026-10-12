<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_fifth_failed_password_attempt_starts_a_five_minute_lockout(): void
    {
        $user = User::factory()->create([
            'email' => 'lockout@example.com',
            'password' => 'correct-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $key = strtolower($user->email).'|127.0.0.1';
        $this->assertGreaterThanOrEqual(295, Cache::get('login-lockout:'.$key) - now()->timestamp);

        Cache::forget('login-lockout:'.$key);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertNull(Cache::get('login-lockout:'.$key));

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGreaterThanOrEqual(595, Cache::get('login-lockout:'.$key) - now()->timestamp);
    }
}
