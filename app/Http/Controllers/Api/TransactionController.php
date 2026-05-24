<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Services\TransactionService;
use Illuminate\Http\Request;

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
        $transactions = \App\Models\Transaction::with('product')
            ->where('user_id', $request->user()->id)
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
}
