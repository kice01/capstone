<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = mb_strtolower($request->string('email')->trim()->toString());
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        $table = config('auth.passwords.'.config('auth.defaults.passwords').'.table');

        if ($user) {
            $existing = DB::table($table)->where('email', $user->email)->first();
            $throttleSeconds = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.throttle', 60);
            $recentlySent = $existing
                && Carbon::parse($existing->created_at)->greaterThan(now()->subSeconds($throttleSeconds));

            if (! $recentlySent) {
                $code = (string) random_int(10000000, 99999999);

                DB::table($table)->updateOrInsert(
                    ['email' => $user->email],
                    ['token' => Hash::make($code), 'created_at' => now()]
                );

                try {
                    Mail::to($user->email)->send(new PasswordResetCodeMail($user->name, $code));
                } catch (TransportExceptionInterface $exception) {
                    DB::table($table)->where('email', $user->email)->delete();
                    report($exception);
                }
            }
        }

        return redirect()->route('password.reset.code')->with(
            'status',
            'If an account with that email exists, a reset code will be sent to its registered email address.'
        )->withInput(['email' => $request->input('email')]);
    }
}
