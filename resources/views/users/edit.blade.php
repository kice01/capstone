<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit User
            </h2>

            <a href="{{ route('users.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded">
                Back
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

            @if ($errors->any())
                <div class="bg-red-100 text-red-700 p-4 rounded mb-4">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block mb-2">Full Name</label>
                        <input type="text" name="name" class="border rounded w-full p-2" value="{{ old('name', $user->name) }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Email</label>
                        <input type="email" name="email" class="border rounded w-full p-2" value="{{ old('email', $user->email) }}" required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Role</label>
                        <select name="role" class="border rounded w-full p-2" required>
                            <option value="owner" {{ old('role', $user->role) === 'owner' ? 'selected' : '' }}>Owner</option>
                            <option value="cashier" {{ old('role', $user->role) === 'cashier' ? 'selected' : '' }}>Cashier</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="border rounded w-full p-2">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="border rounded w-full p-2">
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">
                        Update User
                    </button>

                    <a href="{{ route('users.index') }}" class="ml-3 text-gray-600">
                        Cancel
                    </a>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
