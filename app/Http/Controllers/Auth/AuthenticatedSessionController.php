<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if ($user->two_factor_enabled) {
            $user->generateTwoFactorCode();
            Session::put('two_factor_user_id', $user->id);
            Auth::guard('web')->logout();

            return redirect()->route('two-factor.show');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function showTwoFactor(): View
    {
        $user = User::find(Session::get('two_factor_user_id'));

        $lockExpiresAt = $user?->two_factor_lock_expires_at?->getTimestamp();
        $limit = $user?->two_factor_lock_count > 0 ? 2 : 3;
        $attemptsLeft = $user ? max(0, $limit - $user->two_factor_attempts) : null;

        return view('auth.two-factor', [
            'attemptsLeft' => $attemptsLeft,
            'isLocked' => $user?->isTwoFactorLocked() ?? false,
            'lockExpiresAt' => $lockExpiresAt,
        ]);
    }

    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user = User::find(Session::get('two_factor_user_id'));

        if (! $user) {
            return back()->withErrors(['code' => 'The verification code is invalid or expired.']);
        }

        if ($user->isTwoFactorLocked()) {
            return back()->withErrors(['code' => 'Too many attempts. Try again in ' . $user->twoFactorLockRemainingMinutes() . ' minutes.'])
                ->with([
                    'attemptsLeft' => 0,
                    'isLocked' => true,
                    'lockExpiresAt' => $user->two_factor_lock_expires_at?->getTimestamp(),
                ]);
        }

        if (! $user->isTwoFactorCodeValid((string) $request->code)) {
            $user->incrementTwoFactorAttempts();

            if ($user->isTwoFactorLocked()) {
                return back()->withErrors(['code' => 'Too many attempts. Your account is locked for ' . $user->twoFactorLockRemainingMinutes() . ' minutes.'])
                    ->with([
                        'attemptsLeft' => 0,
                        'isLocked' => true,
                        'lockExpiresAt' => $user->two_factor_lock_expires_at?->getTimestamp(),
                    ]);
            }

            $limit = $user->two_factor_lock_count > 0 ? 2 : 3;
            $remaining = max(0, $limit - $user->two_factor_attempts);

            return back()->withErrors(['code' => "The verification code is invalid. {$remaining} attempt(s) left."])
                ->with([
                    'attemptsLeft' => $remaining,
                    'isLocked' => false,
                    'lockExpiresAt' => null,
                ]);
        }

        $user->forceFill([
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        $user->resetTwoFactorLocks();

        Auth::login($user);
        $request->session()->regenerate();
        Session::forget('two_factor_user_id');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
