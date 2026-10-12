<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function createWithCode(): View
    {
        return view('auth.reset-password-code');
    }

    public function storeWithCode(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:8'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $email = mb_strtolower($request->string('email')->trim()->toString());
        $attemptKey = 'password-reset-code:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many code attempts. Please wait one minute and try again.',
            ]);
        }

        $broker = config('auth.defaults.passwords');
        $table = config("auth.passwords.{$broker}.table");
        $reset = DB::table($table)->whereRaw('LOWER(email) = ?', [$email])->first();
        $expires = (int) config("auth.passwords.{$broker}.expire", 30);
        $expired = ! $reset || \Illuminate\Support\Carbon::parse($reset->created_at)->addMinutes($expires)->isPast();

        if ($expired || ! Hash::check($request->input('code'), $reset->token)) {
            RateLimiter::hit($attemptKey, 60);

            throw ValidationException::withMessages([
                'code' => 'The reset code is invalid or expired.',
            ]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            RateLimiter::hit($attemptKey, 60);

            throw ValidationException::withMessages([
                'code' => 'The reset code is invalid or expired.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
            'two_factor_code' => null,
            'two_factor_expires_at' => null,
        ])->save();

        DB::table($table)->where('email', $user->email)->delete();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))
                ->where('user_id', $user->getKey())
                ->delete();
        }

        RateLimiter::clear($attemptKey);
        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in with your new password.');
    }

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                    'two_factor_code' => null,
                    'two_factor_expires_at' => null,
                ])->save();

                if (config('session.driver') === 'database') {
                    DB::table(config('session.table'))
                        ->where('user_id', $user->getKey())
                        ->delete();
                }

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
