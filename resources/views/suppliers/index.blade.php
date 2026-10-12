<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Suppliers
            </h2>

            <a href="{{ route('suppliers.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                + Add Supplier
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

            <div class="bg-white shadow rounded-lg p-6">

                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">ID</th>
                            <th class="border p-2">Supplier</th>
                            <th class="border p-2">Contact Person</th>
                            <th class="border p-2">Phone</th>
                            <th class="border p-2">Email</th>
                            <th class="border p-2">Address</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($suppliers as $supplier)
                            <tr>
                                <td class="border p-2">{{ $supplier->id }}</td>
                                <td class="border p-2">{{ $supplier->name }}</td>
                                <td class="border p-2">{{ $supplier->contact_person }}</td>
                                <td class="border p-2">{{ $supplier->phone }}</td>
                                <td class="border p-2">{{ $supplier->email }}</td>
                                <td class="border p-2">{{ $supplier->address }}</td>

                                <td class="border p-2">

                                    <a href="{{ route('suppliers.edit', $supplier) }}"
                                       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded transition">
                                        Edit
                                    </a>

                                    <form action="{{ route('suppliers.destroy', $supplier) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this supplier?')"
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center p-4">
                                    No suppliers found.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>
