<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Expiry Tracking
            </h2>
            @if(auth()->user()->role === 'owner')
                <a href="{{ route('reports.expiry') }}"
                    class="bg-blue-600 hover:bg-indigo-700 text-white px-4 py-2 rounded  shadow-sm border border-indigo-700 flex items-center gap-2">
                    📊 View Report
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            {{-- Summary Chart --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    Expiry Status Overview
                </h3>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
                    <div class="lg:col-span-1 flex justify-center">
                        <div class="w-full max-w-xs" style="height: 280px; position: relative;">
                            <canvas id="expiryPieChart"></canvas>
                        </div>
                    </div>
                    <div class="lg:col-span-2 grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-red-700">{{ $expired->count() }}</p>
                            <p class="text-sm text-red-600">Expired</p>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-yellow-700">{{ $nearExpiry->count() }}</p>
                            <p class="text-sm text-yellow-600">Near Expiry</p>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-green-700">{{ $fresh->count() }}</p>
                            <p class="text-sm text-green-600">Fresh</p>
                        </div>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-gray-700">{{ $noExpiry->count() }}</p>
                            <p class="text-sm text-gray-600">No Date Set</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Expired --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-red-700 mb-3">
                    Expired Batches ({{ $expired->count() }})
                </h3>
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-red-100">
                        <tr>
                            <th class="border p-2">Batch No.</th>
                            <th class="border p-2">Product</th>
                            <th class="border p-2">Category</th>
                            <th class="border p-2">Quantity</th>
                            <th class="border p-2">Expiry Date</th>
                            <th class="border p-2">Days</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expired as $batch)
                            <tr class="bg-red-50">
                                <td class="border p-2">#{{ $batch->id }}</td>
                                <td class="border p-2">{{ $batch->product->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->product->category->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->quantity }}</td>
                                <td class="border p-2">{{ $batch->expiration_date }}</td>
                                <td class="border p-2 text-red-700 font-semibold">
{{ (int) \Carbon\Carbon::parse($batch->expiration_date)->diffInDays(now()) }} days ago                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">No expired batches.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Near Expiry --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-yellow-700 mb-3">
                    Near Expiry — within 15 days ({{ $nearExpiry->count() }})
                </h3>
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-yellow-100">
                        <tr>
                            <th class="border p-2">Batch No.</th>
                            <th class="border p-2">Product</th>
                            <th class="border p-2">Category</th>
                            <th class="border p-2">Quantity</th>
                            <th class="border p-2">Expiry Date</th>
                            <th class="border p-2">Days Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nearExpiry as $batch)
                            <tr class="bg-yellow-50">
                                <td class="border p-2">#{{ $batch->id }}</td>
                                <td class="border p-2">{{ $batch->product->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->product->category->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->quantity }}</td>
                                <td class="border p-2">{{ $batch->expiration_date }}</td>
                                <td class="border p-2 text-yellow-700 font-semibold">
{{ (int) now()->diffInDays(\Carbon\Carbon::parse($batch->expiration_date)) }} days left                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">No batches near expiry.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Fresh --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-green-700 mb-3">
                    Fresh Batches ({{ $fresh->count() }})
                </h3>
                <table class="w-full border-collapse border border-gray-300">
                    <thead class="bg-green-100">
                        <tr>
                            <th class="border p-2">Batch No.</th>
                            <th class="border p-2">Product</th>
                            <th class="border p-2">Category</th>
                            <th class="border p-2">Quantity</th>
                            <th class="border p-2">Expiry Date</th>
                            <th class="border p-2">Days Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($fresh as $batch)
                            <tr>
                                <td class="border p-2">#{{ $batch->id }}</td>
                                <td class="border p-2">{{ $batch->product->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->product->category->name ?? 'N/A' }}</td>
                                <td class="border p-2">{{ $batch->quantity }}</td>
                                <td class="border p-2">{{ $batch->expiration_date }}</td>
                                <td class="border p-2 text-green-700">
                                    {{ (int) now()->diffInDays(\Carbon\Carbon::parse($batch->expiration_date)) }} days left
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center p-4">No fresh batches with expiry dates.</td>
                            </tr>
                        @endforelse                    </tbody>
                </table>
            </div>

            {{-- No expiry date set --}}
            @if($noExpiry->count() > 0)
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-700 mb-3">
                        No Expiry Date Set ({{ $noExpiry->count() }})
                    </h3>
                    <table class="w-full border-collapse border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-2">Batch No.</th>
                                <th class="border p-2">Product</th>
                                <th class="border p-2">Category</th>
                                <th class="border p-2">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($noExpiry as $batch)
                                <tr>
                                    <td class="border p-2">#{{ $batch->id }}</td>
                                    <td class="border p-2">{{ $batch->product->name ?? 'N/A' }}</td>
                                    <td class="border p-2">{{ $batch->product->category->name ?? 'N/A' }}</td>
                                    <td class="border p-2">{{ $batch->quantity }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('expiryPieChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: ['Expired', 'Near Expiry', 'Fresh', 'No Date Set'],
                        datasets: [{
                            data: [
                                {{ $expired->count() }},
                                {{ $nearExpiry->count() }},
                                {{ $fresh->count() }},
                                {{ $noExpiry->count() }}
                            ],
                            backgroundColor: [
                                '#ef4444',
                                '#eab308',
                                '#22c55e',
                                '#9ca3af'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }                });
            }
        });
    </script>
    @endpush
</x-app-layout>
