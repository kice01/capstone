<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $failedAttemptsKey = $this->failedAttemptsKey();
            $failedAttempts = (int) Cache::get($failedAttemptsKey, 0) + 1;
            Cache::put($failedAttemptsKey, $failedAttempts, now()->addDay());
            $lockoutStage = (int) Cache::get($this->lockoutStageKey(), 0);
            $attemptLimit = $lockoutStage === 0 ? 5 : 2;

            if ($failedAttempts >= $attemptLimit) {
                $lockoutStage++;
                $lockoutMinutes = 5 * $lockoutStage;
                $lockoutUntil = now()->addMinutes($lockoutMinutes);
                Cache::put($this->lockoutKey(), $lockoutUntil->timestamp, $lockoutUntil);
                Cache::put($this->lockoutStageKey(), $lockoutStage, now()->addDay());
                Cache::forget($this->failedAttemptsKey());
                $this->session()->flash('login_lockout_until', $lockoutUntil->timestamp);
                $this->session()->flash('login_attempts_left', 0);

                throw ValidationException::withMessages([
                    'email' => trans('auth.throttle', [
                        'seconds' => $lockoutMinutes * 60,
                        'minutes' => $lockoutMinutes,
                    ]),
                ]);
            }

            $this->session()->flash('login_attempts_left', max(0, $attemptLimit - $failedAttempts));
            $this->session()->flash('login_lockout_after_minutes', 5 * ($lockoutStage + 1));

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if ($user && ! $user->is_active) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is waiting for owner approval.',
            ]);
        }

        Cache::forget($this->failedAttemptsKey());
        Cache::forget($this->lockoutKey());
        Cache::forget($this->lockoutStageKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $lockoutUntil = Cache::get($this->lockoutKey());

        if (! $lockoutUntil || $lockoutUntil <= now()->timestamp) {
            Cache::forget($this->lockoutKey());
            return;
        }

        event(new Lockout($this));

        $seconds = $lockoutUntil - now()->timestamp;
        $this->session()->flash('login_lockout_until', $lockoutUntil);
        $this->session()->flash('login_attempts_left', 0);

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    private function failedAttemptsKey(): string
    {
        return 'login-failures:'.$this->throttleKey();
    }

    private function lockoutKey(): string
    {
        return 'login-lockout:'.$this->throttleKey();
    }

    private function lockoutStageKey(): string
    {
        return 'login-lockout-stage:'.$this->throttleKey();
    }
}
