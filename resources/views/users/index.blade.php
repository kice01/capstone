<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                User Management
            </h2>

            <a href="{{ route('users.create') }}"
               class="bg-blue-600 text-white px-4 py-2 rounded">
                + Add User
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Name</th>
                            <th class="border p-2">Email</th>
                            <th class="border p-2">Role</th>
                            <th class="border p-2">Status</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td class="border p-2">{{ $user->id }}</td>
                                <td class="border p-2">{{ $user->name }}</td>
                                <td class="border p-2">{{ $user->email }}</td>
                                <td class="border p-2 capitalize">{{ $user->role }}</td>
                                <td class="border p-2">
                                    @if($user->is_active)
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm">Active</span>
                                    @else
                                        <span class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-sm">Inactive</span>
                                    @endif
                                </td>
                                <td class="border p-2">
                                    <a href="{{ route('users.edit', $user) }}"
                                       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded transition duration-200">
                                        Edit
                                    </a>

                                    @if($user->id !== auth()->id())
                                        <form action="{{ route('users.toggleActive', $user) }}"
                                              method="POST"
                                              class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button
                                                type="submit"
                                                onclick="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this user?')"
                                                class="{{ $user->is_active ? 'bg-red-600' : 'bg-blue-600' }} text-white px-3 py-1 rounded">
                                                {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        @if(!$user->is_active)
                                            <form action="{{ route('users.destroy', $user) }}"
                                                  method="POST"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('Permanently delete this user? This cannot be undone.')"
                                                    class="bg-red-700 hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 text-white font-semibold px-3 py-1 rounded">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">
                                    No users found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
