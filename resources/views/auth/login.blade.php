<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @php
        $throttleSeconds = max(0, (int) session('login_lockout_until', 0) - now()->timestamp);
    @endphp

    @if ($throttleSeconds > 0)
        <div id="login-countdown" data-seconds="{{ $throttleSeconds }}"
             class="mb-4 rounded-lg bg-red-50 p-3 text-center text-sm font-semibold text-red-700">
            Please wait <span id="login-countdown-value"></span> before trying again.
        </div>
    @elseif (session()->has('login_attempts_left'))
        <div class="mb-4 rounded-lg bg-amber-50 p-3 text-center text-sm font-semibold text-amber-800">
            {{ session('login_attempts_left') }} login attempt(s) left before a {{ session('login_lockout_after_minutes', 5) }}-minute lockout.
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div class="mb-4">
            <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </span>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                       class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #1F6D45;"
                       placeholder="admin">
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
            <div class="relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="10" width="16" height="10" rx="2"/>
                        <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                    </svg>
                </span>
                <input id="password" type="password" name="password" required autocomplete="current-password"
                       class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:border-transparent"
                       style="--tw-ring-color: #1F6D45;"
                       placeholder="••••••••">
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember + Forgot -->
        <div class="flex items-center justify-between mb-6">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-gray-300 focus:ring-2"
                       style="accent-color: #1F6D45;">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium hover:underline" style="color: #1F6D45;" href="{{ route('password.request') }}">
                    {{ __('Forgot Password?') }}
                </a>
            @endif
        </div>

        <!-- Submit -->
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 text-white font-semibold py-3 rounded-lg text-sm transition"
                style="background: #1F6D45;"
                onmouseover="this.style.background='#175636'" onmouseout="this.style.background='#1F6D45'">
            {{ __('Sign In') }}
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
        </button>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600">
        No account yet?
        <a href="{{ route('register') }}" class="font-medium underline" style="color: #1F6D45;">Register here</a>
    </p>

    @if ($throttleSeconds > 0)
        <script>
            (() => {
                const countdown = document.getElementById('login-countdown');
                const value = document.getElementById('login-countdown-value');
                let seconds = Number(countdown.dataset.seconds);

                const timer = window.setInterval(() => {
                    seconds -= 1;
                    const minutes = Math.floor(Math.max(0, seconds) / 60);
                    const remainingSeconds = Math.max(0, seconds) % 60;
                    value.textContent = `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;

                    if (seconds <= 0) {
                        window.clearInterval(timer);
                        countdown.textContent = 'You can try logging in again.';
                    }
                }, 1000);
                value.textContent = `${String(Math.floor(seconds / 60)).padStart(2, '0')}:${String(seconds % 60).padStart(2, '0')}`;
            })();
        </script>
    @endif
</x-guest-layout>
