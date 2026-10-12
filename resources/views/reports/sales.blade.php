<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sales Report
            </h2>
            <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:underline">← Back to Dashboard</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <p class="text-sm text-gray-500">Gross Profit (transactions with saved purchase cost)</p>
                <p class="text-3xl font-bold text-green-700">₱{{ number_format($grossProfit, 2) }}</p>
                @if($untrackedCostItems > 0)
                    <p class="text-sm text-amber-700 mt-2">Purchase cost is missing for {{ $untrackedCostItems }} sold item line(s); these lines are excluded from gross profit.</p>
                @endif
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Sales by Category --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales by Category</h3>
                @if($salesByCategory->count() > 0)
                    <div style="height: 280px; position: relative;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                @else
                    <p class="text-gray-400 text-sm text-center py-16">No sales data yet.</p>
                @endif
            </div>

            {{-- Sales per Month --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Sales per Month (Last 6 Months)</h3>
                <div style="height: 280px; position: relative;">
                    <canvas id="monthChart"></canvas>
                </div>
            </div>

            {{-- Payment Method --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment Method Breakdown</h3>
                @if($paymentBreakdown->count() > 0)
                    <div style="height: 280px; position: relative;">
                        <canvas id="paymentChart"></canvas>
                    </div>
                @else
                    <p class="text-gray-400 text-sm text-center py-16">No sales data yet.</p>
                @endif
            </div>

            {{-- Top Products --}}
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Products (by Revenue)</h3>
                @if($topProducts->count() > 0)
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2">Product</th>
                                <th class="py-2 text-right">Units Sold</th>
                                <th class="py-2 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $item)
                                <tr class="border-b last:border-0">
                                    <td class="py-2">{{ $item->product->display_name ?? 'N/A' }}</td>
                                    <td class="py-2 text-right">{{ $item->total_qty }}</td>
                                    <td class="py-2 text-right font-semibold">₱{{ number_format($item->total_revenue, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-gray-400 text-sm text-center py-16">No sales data yet.</p>
                @endif
            </div>

            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {

            // Sales by Category
            const catCtx = document.getElementById('categoryChart');
            if (catCtx) {
                new Chart(catCtx, {
                    type: 'doughnut',
                    data: {
                        labels: @json($salesByCategory->pluck('category')),
                        datasets: [{
                            data: @json($salesByCategory->pluck('total')),
                            backgroundColor: ['#3b82f6', '#22c55e', '#eab308', '#ef4444', '#a855f7', '#06b6d4', '#f97316'],
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

            // Sales per Month
            const monthCtx = document.getElementById('monthChart');
            if (monthCtx) {
                new Chart(monthCtx, {
                    type: 'bar',
                    data: {
                        labels: @json($monthLabels),
                        datasets: [{
                            label: 'Sales (₱)',
                            data: @json($monthData),
                            backgroundColor: '#2563eb'
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

            // Payment Method
            const payCtx = document.getElementById('paymentChart');
            if (payCtx) {
                new Chart(payCtx, {
                    type: 'pie',
                    data: {
                        labels: @json($paymentBreakdown->pluck('payment_method')),
                        datasets: [{
                            data: @json($paymentBreakdown->pluck('total')),
                            backgroundColor: ['#22c55e', '#3b82f6', '#eab308', '#ef4444'],
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

        });
    </script>
    @endpush
</x-app-layout>
