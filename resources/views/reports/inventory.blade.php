<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Inventory Report
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">← Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-lg p-6 shadow">
                    <p class="text-indigo-100 text-sm font-medium mb-1">Total Stock Units</p>
                    <p class="text-3xl font-bold">{{ number_format($totalStockUnits) }}</p>
                </div>
                <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-lg p-6 shadow">
                    <p class="text-teal-100 text-sm font-medium mb-1">Stock Value at Purchase Cost</p>
                    <p class="text-3xl font-bold">₱{{ number_format($totalStockValue, 2) }}</p>
                    @if($stockUnitsWithoutCost > 0)
                        <p class="text-xs text-teal-100 mt-2">Excludes {{ number_format($stockUnitsWithoutCost) }} unit(s) without recorded cost.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Stock Levels by Category --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Stock Levels by Category</h3>
                    @if($stockByCategory->count() > 0)
                        <div style="height: 280px; position: relative;">
                            <canvas id="stockByCategoryChart"></canvas>
                        </div>
                    @else
                        <p class="text-gray-400 text-sm text-center py-16">No product data yet.</p>
                    @endif
                </div>

                {{-- Stock Value by Category --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Stock Value by Category</h3>
                    @if($stockValueByCategory->count() > 0)
                        <div style="height: 280px; position: relative;">
                            <canvas id="stockValueChart"></canvas>
                        </div>
                    @else
                        <p class="text-gray-400 text-sm text-center py-16">No product data yet.</p>
                    @endif
                </div>

            </div>

            {{-- Inventory Movement --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Inventory Movement (Last 30 Days)</h3>
                <div style="height: 280px; position: relative;">
                    <canvas id="movementChart"></canvas>
                </div>
            </div>

            {{-- Low Stock List --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Low Stock Products (&lt;20) — {{ $lowStockProducts->count() }}</h3>
                @if($lowStockProducts->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2">Category</th>
                                <th class="py-2 text-right">Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($lowStockProducts as $product)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $product->display_name }}</td>
                                    <td class="py-2">{{ $product->category->name ?? 'N/A' }}</td>
                                    <td class="py-2 text-right">
                                        <span class="inline-block bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded">
                                            {{ $product->quantity }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-400 text-sm text-center py-8">All products are well-stocked.</p>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const stockCatCtx = document.getElementById('stockByCategoryChart');
            if (stockCatCtx) {
                new Chart(stockCatCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($stockByCategory->pluck('category')),
                        datasets: [{
                            label: 'Units in Stock',
                            data: @json($stockByCategory->pluck('total_qty')),
                            backgroundColor: '#6366f1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

            const stockValCtx = document.getElementById('stockValueChart');
            if (stockValCtx) {
                new Chart(stockValCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($stockValueByCategory->pluck('category')),
                        datasets: [{
                            data: @json($stockValueByCategory->pluck('total_value')),
                            backgroundColor: ['#14b8a6', '#3b82f6', '#eab308', '#ef4444', '#a855f7', '#f97316', '#06b6d4'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            }

            const moveCtx = document.getElementById('movementChart');
            if (moveCtx) {
                new Chart(moveCtx, {
                    type: 'line',
                    data: {
                        labels: @json($movementLabels),
                        datasets: [
                            {
                                label: 'Stock In',
                                data: @json($stockInData),
                                borderColor: '#22c55e',
                                backgroundColor: 'rgba(34,197,94,0.1)',
                                tension: 0.3,
                                fill: true
                            },
                            {
                                label: 'Stock Out',
                                data: @json($stockOutData),
                                borderColor: '#ef4444',
                                backgroundColor: 'rgba(239,68,68,0.1)',
                                tension: 0.3,
                                fill: true
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            }

        });
    </script>
    @endpush
</x-app-layout>
