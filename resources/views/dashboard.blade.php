<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ExTrack Dashboard
        </h2>
    </x-slot>
    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Welcome, {{ auth()->user()->name }}</h1>
                <p class="text-gray-500">Role: <span class="font-semibold capitalize">{{ auth()->user()->role }}</span></p>
                @if (session('status'))
                    <p class="mt-3 rounded bg-green-50 px-3 py-2 text-sm text-green-700">{{ session('status') }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
@if(auth()->user()->role === 'owner')
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
                    <a href="{{ route('products.index') }}" class="bg-blue-100 rounded-lg p-4 text-center hover:bg-blue-200 transition">
                        <div class="text-xl">📦</div>
                        <div class="text-sm font-semibold text-gray-700 mt-1">Products</div>
                    </a>
                    <a href="{{ route('categories.index') }}" class="bg-green-100 rounded-lg p-4 text-center hover:bg-green-200 transition">
                        <div class="text-xl">🏷</div>
                        <div class="text-sm font-semibold text-gray-700 mt-1">Categories</div>
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="bg-yellow-100 rounded-lg p-4 text-center hover:bg-yellow-200 transition">
                        <div class="text-xl">🚚</div>
                        <div class="text-sm font-semibold text-gray-700 mt-1">Suppliers</div>
                    </a>
                    <a href="{{ route('users.index') }}" class="bg-indigo-100 rounded-lg p-4 text-center hover:bg-indigo-200 transition">
                        <div class="text-xl">👥</div>
                        <div class="text-sm font-semibold text-gray-700 mt-1">User Management</div>
                    </a>
                </div>
            @endif
                {{-- 1. Sales Trend --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Sales Trend (Last 7 Days)</h3>
                    <div style="height: 260px; position: relative;">
                        <canvas id="salesTrendChart"></canvas>
                    </div>
                </div>

                {{-- 2. Top 5 Best-Selling Products --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Top 5 Best-Selling Products</h3>
                    @if($topProducts->count() > 0)
                        <div style="height: 260px; position: relative;">
                            <canvas id="topProductsChart"></canvas>
                        </div>
                    @else
                        <p class="text-gray-400 text-sm text-center py-16">No sales data yet.</p>
                    @endif
                </div>

                {{-- 3. Expiry Status --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Expiry Status</h3>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div style="height: 220px; width: 220px; position: relative;" class="shrink-0">
                            <canvas id="expiryStatusChart"></canvas>
                        </div>
                        <div class="grid grid-cols-3 sm:grid-cols-1 gap-2 w-full">
                            <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded px-3 py-2">
                                <span class="text-sm text-green-700 font-medium">Safe</span>
                                <span class="text-lg font-bold text-green-700">{{ $safeCount }}</span>
                            </div>
                            <div class="flex items-center justify-between bg-yellow-50 border border-yellow-200 rounded px-3 py-2">
                                <span class="text-sm text-yellow-700 font-medium">Near Expiry</span>
                                <span class="text-lg font-bold text-yellow-700">{{ $nearExpiryCount }}</span>
                            </div>
                            <div class="flex items-center justify-between bg-red-50 border border-red-200 rounded px-3 py-2">
                                <span class="text-sm text-red-700 font-medium">Expired</span>
                                <span class="text-lg font-bold text-red-700">{{ $expiredCount }}</span>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('expiry.index') }}" class="inline-block mt-4 text-sm text-blue-600 hover:underline">View full expiry report →</a>
                </div>

                {{-- 4. Low Stock / Inventory Alert --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Low Stock / Inventory Alert ({{ $lowStockCount }})</h3>
                    @if($lowStockProducts->count() > 0)
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b">
                                    <th class="py-2">Product</th>
                                    <th class="py-2 text-right">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($lowStockProducts as $product)
                                    <tr class="border-b last:border-0">
                                        <td class="py-2">{{ $product->display_name }}</td>
                                        <td class="py-2 text-right">
                                            <span class="inline-block bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded">
                                                {{ $product->quantity }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <a href="{{ route('inventories.index') }}" class="inline-block mt-4 text-sm text-blue-600 hover:underline">View full inventory →</a>
                    @else
                        <p class="text-gray-400 text-sm text-center py-16">All products are well-stocked.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // 1. Sales Trend
            const trendCtx = document.getElementById('salesTrendChart');
            if (trendCtx) {
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: @json($salesTrendLabels),
                        datasets: [{
                            label: 'Sales (₱)',
                            data: @json($salesTrendData),
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,0.1)',
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: '#2563eb'
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

            // 2. Top 5 Best-Selling Products
            const topCtx = document.getElementById('topProductsChart');
            if (topCtx) {
                new Chart(topCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($topProducts->map(fn($item) => $item->product->display_name ?? 'N/A')),
                        datasets: [{
                            label: 'Units Sold',
                            data: @json($topProducts->pluck('total_qty')),
                            backgroundColor: '#3b82f6'
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            }

            // 3. Expiry Status
            const expiryCtx = document.getElementById('expiryStatusChart');
            if (expiryCtx) {
                new Chart(expiryCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Safe', 'Near Expiry', 'Expired'],
                        datasets: [{
                            data: [{{ $safeCount }}, {{ $nearExpiryCount }}, {{ $expiredCount }}],
                            backgroundColor: ['#22c55e', '#eab308', '#ef4444'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } }
                    }
                });
            }

        });
    </script>
    @endpush
</x-app-layout>
