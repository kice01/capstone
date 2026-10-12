<x-app-layout>
<x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inventory
            </h2>
            <div class="flex gap-2">
                @if(auth()->user()->role === 'owner')
                    <a href="{{ route('reports.inventory') }}"
                        class="bg-blue-600 hover:bg-indigo-700 text-white px-4 py-2 rounded  shadow-sm border border-indigo-700 flex items-center gap-2">
                        📊 View Report
                    </a>
                    <a href="{{ route('inventories.create') }}"
                       class="bg-blue-600 text-white px-4 py-2 rounded">
                        + Add Inventory
                    </a>
                @endif
            </div>        </div>
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
                            <th class="border p-2">Product</th>
                            <th class="border p-2">Supplier</th>
                            <th class="border p-2">Stock In</th>
                            <th class="border p-2">Stock Out</th>
                            <th class="border p-2">Unit Cost</th>
                            <th class="border p-2">Current Stock</th>
                            <th class="border p-2">Expiry Date</th>
                            <th class="border p-2">Batch Details</th>
                            <th class="border p-2">Reason</th>
                            <th class="border p-2">Recorded By</th>
                            <th class="border p-2">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($inventories as $inventory)
                            <tr>
                                <td class="border p-2">{{ $inventory->id }}</td>
                                <td class="border p-2">{{ $inventory->product->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $inventory->supplier->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $inventory->stock_in }}</td>
                                <td class="border p-2">{{ $inventory->stock_out }}</td>
                                <td class="border p-2">{{ $inventory->unit_cost !== null ? '₱'.number_format($inventory->unit_cost, 2) : '—' }}</td>
                                <td class="border p-2">{{ $inventory->current_stock }}</td>
                                <td class="border p-2">{{ $inventory->expiry_date }}</td>
                                <td class="border p-2 text-sm">
                                    @forelse($inventory->batchAllocations as $allocation)
                                        <div>Batch #{{ $allocation->batch_id }}: {{ $allocation->quantity }}</div>
                                    @empty
                                        @if($inventory->batch_id)
                                            Batch #{{ $inventory->batch_id }}
                                        @else
                                            —
                                        @endif
                                    @endforelse
                                </td>
                                <td class="border p-2">{{ $inventory->reason ?? 'Legacy movement' }}</td>
                                <td class="border p-2">{{ $inventory->user->name ?? 'System / legacy' }}</td>
                                <td class="border p-2"><span class="text-gray-500 text-sm">Append-only record</span></td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="12" class="text-center p-4">
                                    No inventory records found.
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>
</x-app-layout>
