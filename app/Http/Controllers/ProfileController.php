<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Sale;
use App\Models\DailyClosing;
use App\Models\User;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === 'owner' && $user->is_active
            && User::where('role', 'owner')->where('is_active', true)->count() <= 1) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'The last active owner account cannot be deleted. Ask another owner to take over first.',
            ], 'userDeletion');
        }

        if (Sale::where('user_id', $user->id)->orWhere('voided_by', $user->id)->exists()
            || Inventory::where('user_id', $user->id)->exists()
            || DailyClosing::where('closed_by', $user->id)->exists()) {
            return Redirect::route('profile.edit')->withErrors([
                'password' => 'This account has recorded transactions and cannot be deleted. Ask an owner to deactivate it instead.',
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
