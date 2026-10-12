<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\DailyClosing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:owner,cashier',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:owner,cashier',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($user->role === 'owner' && $validated['role'] !== 'owner'
            && User::where('role', 'owner')->where('is_active', true)->count() <= 1) {
            return redirect()->route('users.edit', $user)
                ->withErrors(['role' => 'You cannot remove the last active owner account.']);
        }

        $user->role = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate your own account.');
        }

        if ($user->role === 'owner' && $user->is_active
            && User::where('role', 'owner')->where('is_active', true)->count() <= 1) {
            return redirect()->route('users.index')->with('error', 'You cannot deactivate the last active owner account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()->route('users.index')->with('success', "User {$status} successfully.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'You cannot delete your own account.');
        }

        if ($user->is_active) {
            return redirect()->route('users.index')->with('error', 'Deactivate this account before deleting it.');
        }

        if (Sale::where('user_id', $user->id)->orWhere('voided_by', $user->id)->exists()
            || Inventory::where('user_id', $user->id)->exists()
            || DailyClosing::where('closed_by', $user->id)->exists()) {
            return redirect()->route('users.index')
                ->with('error', 'This account has recorded transactions and cannot be deleted. Keep it deactivated to preserve the audit history.');
        }

        DB::transaction(function () use ($user) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
