<?php

namespace App\Http\Controllers;

use App\Models\DailyClosing;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class DailyClosingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['date' => ['nullable', 'date']]);
        $date = $filters['date'] ?? now()->toDateString();
        $closing = DailyClosing::with('user')->whereDate('close_date', $date)->first();
        $expected = $closing ? [
            'cash' => (float) $closing->expected_cash,
            'gcash' => (float) $closing->expected_gcash,
        ] : $this->expectedPayments($date);

        return view('reports.daily-close', compact('date', 'closing', 'expected'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'close_date' => ['required', 'date', 'before_or_equal:today'],
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'actual_gcash' => ['required', 'numeric', 'min:0'],
            'opening_float' => ['nullable', 'numeric', 'min:0'],
            'cash_expenses' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $closing = DB::transaction(function () use ($data) {
                if (DailyClosing::whereDate('close_date', $data['close_date'])->exists()) {
                    throw ValidationException::withMessages(['close_date' => 'This day has already been closed.']);
                }

                $expected = $this->expectedPayments($data['close_date']);
                return DailyClosing::create([
                    'close_date' => $data['close_date'],
                    'closed_by' => auth()->id(),
                    'expected_cash' => $expected['cash'],
                    'expected_gcash' => $expected['gcash'],
                    'opening_float' => $data['opening_float'] ?? 0,
                    'cash_expenses' => $data['cash_expenses'] ?? 0,
                    'actual_cash' => $data['actual_cash'],
                    'actual_gcash' => $data['actual_gcash'],
                    'notes' => $data['notes'] ?? null,
                    'closed_at' => now(),
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['close_date' => 'This day has already been closed.']);
        }

        return redirect()->route('reports.daily-close', ['date' => $closing->close_date->toDateString()])
            ->with('success', 'Daily closing saved. The record is locked for audit purposes.');
    }

    private function expectedPayments(string $date): array
    {
        $totals = Sale::where('status', 'completed')
            ->whereDate('created_at', $date)
            ->selectRaw("SUM(CASE WHEN payment_method = 'Cash' THEN total_amount ELSE 0 END) as cash_total")
            ->selectRaw("SUM(CASE WHEN payment_method = 'GCash' THEN total_amount ELSE 0 END) as gcash_total")
            ->first();

        return [
            'cash' => (float) ($totals->cash_total ?? 0),
            'gcash' => (float) ($totals->gcash_total ?? 0),
        ];
    }
}
