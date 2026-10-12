<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daily Cash & GCash Closing</h2>
                <p class="text-sm text-gray-500 mt-1">Compare recorded payments with the amounts counted at the end of the day.</p>
            </div>
            <a href="{{ route('reports.sales') }}" class="bg-gray-700 text-white px-4 py-2 rounded">Sales Report</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-3 rounded mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-red-100 text-red-700 p-3 rounded mb-4">{{ $errors->first() }}</div>
            @endif

            <form method="GET" action="{{ route('reports.daily-close') }}" class="bg-white shadow rounded-lg p-5 mb-5 flex flex-wrap items-end gap-3">
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700">Business date</label>
                    <input id="date" type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}" class="mt-1 rounded-md border-gray-300 shadow-sm">
                </div>
                <button class="bg-gray-800 text-white px-4 py-2 rounded">View date</button>
            </form>

            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-1">{{ \Carbon\Carbon::parse($date)->format('F d, Y') }}</h3>
                <p class="text-sm text-gray-500 mb-5">Expected totals are based on completed POS sales recorded that day.</p>

                @if($closing)
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-300">
                            <thead class="bg-gray-100"><tr><th class="border p-3 text-left">Payment</th><th class="border p-3 text-right">Expected</th><th class="border p-3 text-right">Counted</th><th class="border p-3 text-right">Difference</th></tr></thead>
                            <tbody>
                                @foreach(['cash' => 'Cash', 'gcash' => 'GCash'] as $key => $label)
                                    @php($actual = (float) $closing->{'actual_'.$key})
                                    @php($expectedDrawer = $key === 'cash' ? (float) $closing->expected_cash + (float) $closing->opening_float - (float) $closing->cash_expenses : (float) $closing->expected_gcash)
                                    <tr><td class="border p-3">{{ $key === 'cash' ? 'Cash drawer' : $label }}</td><td class="border p-3 text-right">₱{{ number_format($expectedDrawer, 2) }}</td><td class="border p-3 text-right">₱{{ number_format($actual, 2) }}</td><td class="border p-3 text-right {{ $actual - $expectedDrawer == 0 ? 'text-green-700' : 'text-red-700 font-semibold' }}">₱{{ number_format($actual - $expectedDrawer, 2) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-sm text-gray-600">Cash sales ₱{{ number_format($closing->expected_cash, 2) }} + opening float ₱{{ number_format($closing->opening_float, 2) }} − expenses ₱{{ number_format($closing->cash_expenses, 2) }}.</p>
                    <div class="mt-4 text-sm text-gray-600">Closed by {{ $closing->user->name }} at {{ $closing->closed_at->format('M d, Y h:i A') }}</div>
                    @if($closing->notes)<p class="mt-2 text-sm">Notes: {{ $closing->notes }}</p>@endif
                    <p class="mt-4 text-sm text-gray-500">This closing is locked. Contact the system owner if a correction is required.</p>
                @elseif($date > now()->toDateString())
                    <p class="text-gray-600">Choose today or an earlier date to close.</p>
                @else
                    <form method="POST" action="{{ route('reports.daily-close.store') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="close_date" value="{{ $date }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-sm font-medium">Cash Sales Expected</label><input readonly value="₱{{ number_format($expected['cash'], 2) }}" class="mt-1 w-full rounded-md border-gray-300 bg-gray-100"></div>
                            <div><label class="block text-sm font-medium">Expected GCash</label><input readonly value="₱{{ number_format($expected['gcash'], 2) }}" class="mt-1 w-full rounded-md border-gray-300 bg-gray-100"></div>
                            <div><label for="opening_float" class="block text-sm font-medium">Opening Cash Float</label><input id="opening_float" type="number" name="opening_float" min="0" step="0.01" value="{{ old('opening_float', '0.00') }}" class="mt-1 w-full rounded-md border-gray-300"></div>
                            <div><label for="cash_expenses" class="block text-sm font-medium">Cash Expenses / Paid Outs</label><input id="cash_expenses" type="number" name="cash_expenses" min="0" step="0.01" value="{{ old('cash_expenses', '0.00') }}" class="mt-1 w-full rounded-md border-gray-300"></div>
                            <div><label for="actual_cash" class="block text-sm font-medium">Counted Cash</label><input id="actual_cash" type="number" name="actual_cash" min="0" step="0.01" value="{{ old('actual_cash', number_format($expected['cash'], 2, '.', '')) }}" required class="mt-1 w-full rounded-md border-gray-300"></div>
                            <div><label for="actual_gcash" class="block text-sm font-medium">GCash Balance / Total</label><input id="actual_gcash" type="number" name="actual_gcash" min="0" step="0.01" value="{{ old('actual_gcash', number_format($expected['gcash'], 2, '.', '')) }}" required class="mt-1 w-full rounded-md border-gray-300"></div>
                        </div>
                        <div><label for="notes" class="block text-sm font-medium">Notes / Variance explanation</label><textarea id="notes" name="notes" maxlength="1000" rows="3" class="mt-1 w-full rounded-md border-gray-300">{{ old('notes') }}</textarea></div>
                        <button onclick="return confirm('Save and lock this daily closing?')" class="bg-green-700 hover:bg-green-800 text-white px-4 py-2 rounded">Save Daily Closing</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
