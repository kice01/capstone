<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Mail\TwoFactorCodeMail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;

#[Fillable(['name', 'email', 'password', 'role', 'is_active', 'two_factor_enabled', 'two_factor_code', 'two_factor_expires_at'])]
#[Hidden(['password', 'remember_token', 'two_factor_code'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'two_factor_enabled' => 'boolean',
            'two_factor_expires_at' => 'datetime',
            'two_factor_attempts' => 'int',
            'two_factor_lock_expires_at' => 'datetime',
            'two_factor_lock_count' => 'int',
        ];
    }

    public function generateTwoFactorCode(): void
    {
        $this->forceFill([
            'two_factor_code' => (string) random_int(100000, 999999),
            'two_factor_expires_at' => now()->addMinutes(5),
        ])->save();

        Mail::to($this->email)->send(new TwoFactorCodeMail($this));
    }

    public function incrementTwoFactorAttempts(): void
    {
        $this->forceFill([
            'two_factor_attempts' => $this->two_factor_attempts + 1,
        ])->save();

        $limit = $this->two_factor_lock_count > 0 ? 2 : 3;

        if ($this->two_factor_attempts >= $limit) {
            $nextLockCount = $this->two_factor_lock_count + 1;
            $this->forceFill([
                'two_factor_attempts' => 0,
                'two_factor_lock_count' => $nextLockCount,
                'two_factor_lock_expires_at' => now()->addMinutes(5 * $nextLockCount),
            ])->save();
        }
    }

    public function resetTwoFactorLocks(): void
    {
        $this->forceFill([
            'two_factor_attempts' => 0,
            'two_factor_lock_expires_at' => null,
            'two_factor_lock_count' => 0,
        ])->save();
    }

    public function isTwoFactorLocked(): bool
    {
        return $this->two_factor_lock_expires_at?->isFuture() ?? false;
    }

    public function twoFactorLockRemainingMinutes(): int
    {
        if (! $this->two_factor_lock_expires_at) {
            return 0;
        }

        return now()->diffInMinutes($this->two_factor_lock_expires_at);
    }

    public function isTwoFactorCodeValid(string $code): bool
    {
        return $this->two_factor_enabled
            && $this->two_factor_code === $code
            && $this->two_factor_expires_at?->isFuture();
    }
}
