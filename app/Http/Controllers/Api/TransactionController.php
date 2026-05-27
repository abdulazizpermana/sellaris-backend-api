<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function __construct(private TransactionService $transactionService) {}

    public function store(StoreTransactionRequest $request)
    {
        $transaction = $this->transactionService->store(
            $request->validated(),
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat! 💰',
            'data'    => new TransactionResource($transaction->load('product')),
        ], 201);
    }

    public function dailyReport(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $report = $this->transactionService->getDailyReport($request->user()->id, $date);

        return response()->json([
            'success' => true,
            'data'    => [
                'date'               => $report['date'],
                'total_transactions' => $report['total_transactions'],
                'total_revenue'      => $report['total_revenue'],
                'total_items_sold'   => $report['total_items_sold'],
                'transactions'       => TransactionResource::collection($report['transactions']),
            ],
        ]);
    }

    public function history(Request $request)
    {
        $transactions = Transaction::with('product')
            ->where('transactions.user_id', $request->user()->id)
            ->orderByDesc('transaction_date')
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data'    => TransactionResource::collection($transactions),
            'meta'    => [
                'total'        => $transactions->total(),
                'current_page' => $transactions->currentPage(),
                'last_page'    => $transactions->lastPage(),
                'per_page'     => $transactions->perPage(),
            ],
        ]);
    }

    public function monthlyReport(Request $request)
    {
        $validated = $request->validate([
            'year'  => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $period = Carbon::createFromDate(
            $validated['year'],
            $validated['month'],
            1
        );

        $baseQuery = Transaction::query()
            ->where('transactions.user_id', $request->user()->id)
            ->whereYear('transactions.transaction_date', $period->year)
            ->whereMonth('transactions.transaction_date', $period->month);

        $transactions = (clone $baseQuery)->get();

        $dailyChart = (clone $baseQuery)
            ->selectRaw('DATE(transactions.transaction_date) as date')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('SUM(transactions.total_price) as total_revenue')
            ->selectRaw('SUM(transactions.quantity) as total_items_sold')
            ->groupBy(DB::raw('DATE(transactions.transaction_date)'))
            ->orderBy('date')
            ->get()
            ->map(fn($item) => [
                'date'               => $item->date,
                'total_transactions' => (int) $item->total_transactions,
                'total_revenue'      => (float) $item->total_revenue,
                'total_items_sold'   => (int) $item->total_items_sold,
            ])
            ->values();

        $topProducts = (clone $baseQuery)
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->select('transactions.product_id', 'products.product_name')
            ->selectRaw('SUM(transactions.quantity) as total_sold')
            ->selectRaw('SUM(transactions.total_price) as total_revenue')
            ->groupBy('transactions.product_id', 'products.product_name')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get()
            ->map(fn($item) => [
                'product_id'    => (int) $item->product_id,
                'product_name'  => $item->product_name,
                'total_sold'    => (int) $item->total_sold,
                'total_revenue' => (float) $item->total_revenue,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => [
                'year'               => $period->year,
                'month'              => $period->month,
                'total_revenue'      => (float) $transactions->sum('total_price'),
                'total_transactions' => $transactions->count(),
                'total_items_sold'   => (int) $transactions->sum('quantity'),
                'daily_chart'        => $dailyChart,
                'top_products'       => $topProducts,
            ],
        ]);
    }
}
