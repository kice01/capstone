<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-gray-800">Two-factor verification</h1>
        <p class="mt-2 text-sm text-gray-600">Enter the 6-digit code sent to your email.</p>
    </div>

    @if(!empty($isLocked) && $isLocked)
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-3 text-center text-sm text-red-700">
            Too many attempts. Try again in <span id="tf-countdown">--:--</span>.
        </div>
    @endif

    @if(isset($attemptsLeft) && $attemptsLeft > 0 && empty($isLocked))
        <div class="mb-4 text-sm text-gray-600 text-center">You have {{ $attemptsLeft }} attempt(s) left.</div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <label for="code" class="block text-sm font-semibold text-gray-700 mb-1.5">Verification code</label>
        <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code"
               maxlength="6" pattern="[0-9]{6}" required autofocus
               class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-center text-xl tracking-[0.5em] focus:outline-none focus:ring-2"
               style="--tw-ring-color: #1F6D45;">
        <x-input-error :messages="$errors->get('code')" class="mt-2" />

        <button type="submit" class="mt-6 w-full rounded-lg py-3 text-sm font-semibold text-white" style="background: #1F6D45;">
            Verify and continue
        </button>
    </form>

    @if(!empty($isLocked) && $isLocked && !empty($lockExpiresAt))
        <script>
            (function(){
                const expiresAt = {{ $lockExpiresAt }} * 1000;
                const el = document.getElementById('tf-countdown');

                function pad(n){ return n.toString().padStart(2,'0'); }

                function update(){
                    const diff = Math.max(0, expiresAt - Date.now());
                    const minutes = Math.floor(diff / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    el.textContent = pad(minutes) + ':' + pad(seconds);
                    if (diff <= 0) clearInterval(timer);
                }

                update();
                const timer = setInterval(update, 1000);
            })();
        </script>
    @endif
</x-guest-layout>