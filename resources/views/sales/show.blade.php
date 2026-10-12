<style>
    @media print {
        nav, x-slot, .no-print, header, aside { display: none !important; }
        body * { visibility: hidden; }
        #receipt, #receipt * { visibility: visible; }
        #receipt {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }
    }
</style>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sale #{{ $sale->id }}
            </h2>
            <a href="{{ route('sales.index') }}"
               class="bg-gray-600 text-white px-4 py-2 rounded no-print">
                Back
            </a>
        </div>
    </x-slot>
    <div class="py-6">
        <div class="max-w-md mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6 font-mono text-sm" id="receipt">

                @if($sale->status === 'voided')
                    <div class="border-2 border-red-600 text-red-700 text-center font-bold text-2xl p-2 mb-3">VOIDED</div>
                    <p class="text-center text-xs mb-3">Reason: {{ $sale->void_reason }}<br>Voided by {{ $sale->voidedBy->name ?? 'former user' }} at {{ $sale->voided_at?->format('M d, Y h:i A') }}</p>
                @endif

                @php
                    $receiptNo = 'OR-' . date('Y', strtotime($sale->sale_date)) . '-' . str_pad($sale->id, 5, '0', STR_PAD_LEFT);
                    $transactionNo = 'TXN-' . str_pad($sale->id, 9, '0', STR_PAD_LEFT);
                    $vatRate = 0.12;
                    $subtotal = $sale->total_amount / (1 + $vatRate);
                    $vatAmount = $sale->total_amount - $subtotal;
                @endphp

                {{-- Store Header --}}
                <div class="text-center mb-4">
                    <p class="font-bold text-base">VIVIENAS GROCERY STORE</p>
                    <p class="text-xs text-gray-600">Dagohoy, Carmen, Bohol</p>
                    <p class="text-xs text-gray-600">Tel. No. (038) 555-0142</p>
                    <p class="text-xs text-gray-600">TIN: 123-456-789-000</p>
                    <p class="text-xs border inline-block px-2 mt-1">VAT REGISTERED</p>
                </div>

                <hr class="border-dashed my-3">

                <p class="text-center font-semibold mb-3">OFFICIAL RECEIPT / SALES INVOICE</p>

                {{-- Sale Info --}}
                <div class="space-y-1 mb-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Receipt No.</span>
                        <span>{{ $receiptNo }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Transaction No.</span>
                        <span>{{ $transactionNo }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Cashier</span>
                    <span>{{ $sale->user->name ?? 'Legacy record' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Customer</span>
                        <span>{{ $sale->customer_name ?? 'Walk-in' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Date</span>
                        <span>{{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Time</span>
                        <span>{{ $sale->created_at->format('h:i A') }}</span>
                    </div>
                </div>

                <hr class="border-dashed my-3">

                {{-- Items --}}
                <table class="w-full text-xs mb-3">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left pb-1">Qty Item</th>
                            <th class="text-right pb-1">Price</th>
                            <th class="text-right pb-1">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="py-1">{{ $item->quantity }} {{ $item->product->display_name ?? 'Deleted product' }}</td>
                                <td class="text-right py-1">{{ number_format($item->price, 2) }}</td>
                                <td class="text-right py-1">{{ number_format($item->price * $item->quantity, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <hr class="border-dashed my-3">

                {{-- Totals --}}
                <div class="space-y-1 mb-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">Subtotal</span>
                        <span>{{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">VAT (12%)</span>
                        <span>{{ number_format($vatAmount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Discount</span>
                        <span>0.00</span>
                    </div>
                </div>

                <hr class="my-2">

                <div class="flex justify-between font-bold text-base mb-2">
                    <span>TOTAL AMOUNT</span>
                    <span>₱ {{ number_format($sale->total_amount, 2) }}</span>
                </div>

                <div class="space-y-1 mb-3">
                    <div class="flex justify-between">
                        <span class="text-gray-500">{{ $sale->payment_method === 'GCash' ? 'GCash Received' : 'Cash Tendered' }}</span>
                        <span>{{ number_format($sale->cash_tendered, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Change</span>
                        <span>{{ number_format($sale->change_amount, 2) }}</span>
                    </div>
                </div>

                <hr class="border-dashed my-3">

                <div class="flex justify-between mb-4">
                    <span class="text-gray-500">Payment Method</span>
                    <span>{{ strtoupper($sale->payment_method) }}</span>
                </div>

                {{-- Barcode --}}
                <div class="flex justify-center mb-2">
                    <svg id="receipt-barcode"></svg>
                </div>

                <p class="text-center text-xs">Thank you for shopping!</p>
                <p class="text-center text-xs">Please come again.</p>

                <div class="mt-6 text-right no-print">
                    <a href="{{ route('sales.create') }}" class="bg-green-600 text-white px-4 py-2 rounded mr-2">
                        + New Sale
                    </a>
                    <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded">
                        🖨️ Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.JsBarcode) {
                window.JsBarcode('#receipt-barcode', @json($receiptNo), {
                    format: 'CODE128',
                    width: 2,
                    height: 40,
                    displayValue: false,
                    margin: 0
                });
            }
        });
    </script>
</x-app-layout>
