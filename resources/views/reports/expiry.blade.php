<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Expiry Report
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">← Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Top row: Status breakdown + summary cards --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Expiry Status Breakdown --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Expiry Status Breakdown</h3>
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div style="height: 220px; width: 220px; position: relative;" class="shrink-0">
                            <canvas id="statusChart"></canvas>
                        </div>
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="bg-green-50 border border-green-200 rounded px-3 py-2 text-center">
                                <p class="text-xl font-bold text-green-700">{{ $safeCount }}</p>
                                <p class="text-xs text-green-600">Safe</p>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded px-3 py-2 text-center">
                                <p class="text-xl font-bold text-yellow-700">{{ $nearExpiryCount }}</p>
                                <p class="text-xs text-yellow-600">Near Expiry</p>
                            </div>
                            <div class="bg-red-50 border border-red-200 rounded px-3 py-2 text-center">
                                <p class="text-xl font-bold text-red-700">{{ $expiredCount }}</p>
                                <p class="text-xs text-red-600">Expired</p>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded px-3 py-2 text-center">
                                <p class="text-xl font-bold text-gray-700">{{ $noExpiryCount }}</p>
                                <p class="text-xs text-gray-600">No Expiry Set</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Expiry by Category --}}
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Expiry Risk by Category</h3>
                    @if($expiryByCategory->count() > 0)
                        <div style="height: 260px; position: relative;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    @else
                        <p class="text-gray-400 text-sm text-center py-16">No expiring/expired products.</p>
                    @endif
                </div>

            </div>

            {{-- Expiring Soon --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Expiring Soon (Next 15 Days) — {{ $expiringSoon->count() }}</h3>
                @if($expiringSoon->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2">Category</th>
                                <th class="py-2 text-right">Quantity</th>
                                <th class="py-2 text-right">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiringSoon as $product)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $product->display_name }}</td>
                                    <td class="py-2">{{ $product->category->name ?? 'N/A' }}</td>
                                    <td class="py-2 text-right">{{ $product->quantity }}</td>
                                    <td class="py-2 text-right">
                                        <span class="inline-block bg-yellow-100 text-yellow-700 font-semibold px-2 py-0.5 rounded">
                                            {{ \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-400 text-sm text-center py-8">No products expiring soon.</p>
                @endif
            </div>

            {{-- Expired Products --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Expired Products — {{ $expiredProducts->count() }}</h3>
                @if($expiredProducts->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2">Category</th>
                                <th class="py-2 text-right">Quantity</th>
                                <th class="py-2 text-right">Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($expiredProducts as $product)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $product->display_name }}</td>
                                    <td class="py-2">{{ $product->category->name ?? 'N/A' }}</td>
                                    <td class="py-2 text-right">{{ $product->quantity }}</td>
                                    <td class="py-2 text-right">
                                        <span class="inline-block bg-red-100 text-red-700 font-semibold px-2 py-0.5 rounded">
                                            {{ \Carbon\Carbon::parse($product->expiry_date)->format('M d, Y') }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-400 text-sm text-center py-8">No expired products.</p>
                @endif
            </div>

        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            const statusCtx = document.getElementById('statusChart');
            if (statusCtx) {
                new Chart(statusCtx, {
                    type: 'pie',
                    data: {
                        labels: ['Safe', 'Near Expiry', 'Expired', 'No Expiry Set'],
                        datasets: [{
                            data: [{{ $safeCount }}, {{ $nearExpiryCount }}, {{ $expiredCount }}, {{ $noExpiryCount }}],
                            backgroundColor: ['#22c55e', '#eab308', '#ef4444', '#9ca3af'],
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

            const catCtx = document.getElementById('categoryChart');
            if (catCtx) {
                new Chart(catCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($expiryByCategory->pluck('category')),
                        datasets: [
                            {
                                label: 'Near Expiry',
                                data: @json($expiryByCategory->pluck('near_expiry')),
                                backgroundColor: '#eab308'
                            },
                            {
                                label: 'Expired',
                                data: @json($expiryByCategory->pluck('expired')),
                                backgroundColor: '#ef4444'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                        scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } } }
                    }
                });
            }

        });
    </script>
    @endpush
</x-app-layout>
