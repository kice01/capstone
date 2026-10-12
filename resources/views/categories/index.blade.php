<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Categories
            </h2>

            <a href="{{ route('categories.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded transition">
                + Add Category
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
                <div role="alert" class="bg-red-100 text-red-700 p-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white shadow rounded-lg p-6">

                <form method="GET" action="{{ route('categories.index') }}" class="mb-4 flex gap-2"
                      data-search-suggestions data-suggestions-url="{{ route('categories.suggestions') }}">
                    <div class="relative w-full md:w-1/2">
                    <input type="search" name="search" value="{{ $search }}" placeholder="Search categories..."
                           autocomplete="off" aria-autocomplete="list"
                           class="border border-gray-300 rounded px-3 py-2 w-full">
                    <div data-suggestions-menu class="hidden absolute z-50 left-0 right-0 top-full mt-1 max-h-72 overflow-y-auto rounded border border-gray-200 bg-white shadow-lg"></div>
                    </div>
                    <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Search</button>
                    @if($search)
                        <a href="{{ route('categories.index') }}" class="bg-gray-200 px-4 py-2 rounded">Clear</a>
                    @endif
                </form>

                <div class="overflow-x-auto"><table class="w-full min-w-[650px] border-collapse border border-gray-300">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">No.</th>
                            <th class="border p-2">Category Name</th>
                            <th class="border p-2">Description</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($categories as $category)
                            <tr>
                                <td class="border p-2">{{ $loop->iteration }}</td>
                                <td class="border p-2">{{ $category->name }}</td>
                                <td class="border p-2">{{ $category->description }}</td>
                                <td class="border p-2">

                                    <a href="{{ route('categories.edit', $category) }}"
                                       class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded transition">
                                        Edit
                                    </a>

                                    <form action="{{ route('categories.destroy', $category) }}"
                                          method="POST"
                                          class="inline">
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('Delete this category?')"
                                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded transition">
                                            Delete
                                        </button>
                                    </form>

                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4" class="text-center p-4">
                                    No categories found.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
