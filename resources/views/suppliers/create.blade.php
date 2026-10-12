<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Supplier
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

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

                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label class="block mb-2">Supplier Name</label>
                        <input type="text"
                               name="name"
                               class="border rounded w-full p-2"
                               required>
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Contact Person</label>
                        <input type="text"
                               name="contact_person"
                               class="border rounded w-full p-2">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Phone</label>
                        <input type="text"
                               name="phone"
                               class="border rounded w-full p-2">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Email</label>
                        <input type="email"
                               name="email"
                               class="border rounded w-full p-2">
                    </div>

                    <div class="mb-4">
                        <label class="block mb-2">Address</label>
                        <textarea
                            name="address"
                            class="border rounded w-full p-2"
                            rows="3"></textarea>
                    </div>

                    <button
                        type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                        Save Supplier
                    </button>

                    <a href="{{ route('suppliers.index') }}"
                       class="ml-3 text-gray-600">
                        Cancel
                    </a>

                </form>

            </div>

        </div>
    </div>
</x-app-layout>
