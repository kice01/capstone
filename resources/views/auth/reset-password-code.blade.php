<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ session('status') ?? 'Enter the 8-digit code sent to your registered email, then choose a new password.' }}
    </div>

    <form method="POST" action="{{ route('password.reset.code.store') }}">
        @csrf

        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-4">
            <x-input-label for="code" :value="__('8-digit reset code')" />
            <x-text-input id="code" class="mt-1 block w-full" type="text" name="code" inputmode="numeric" pattern="[0-9]{8}" maxlength="8" required autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="mb-4">
            <x-input-label for="password" :value="__('New password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mb-4">
            <x-input-label for="password_confirmation" :value="__('Confirm new password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full justify-center">
            {{ __('Reset password') }}
        </x-primary-button>
    </form>
</x-guest-layout>
