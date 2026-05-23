<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function store(array $data, int $userId): Transaction
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = Product::where('id', $data['product_id'])
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($product->stock < $data['quantity']) {
                throw new \Exception("Stok tidak cukup. Stok tersedia: {$product->stock}");
            }

            $totalPrice = $product->price * $data['quantity'];

            $transaction = Transaction::create([
                'user_id'          => $userId,
                'product_id'       => $data['product_id'],
                'quantity'         => $data['quantity'],
                'total_price'      => $totalPrice,
                'notes'            => $data['notes'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
            ]);

            // Kurangi stok otomatis
            $product->decrement('stock', $data['quantity']);

            return $transaction->load('product');
        });
    }

    public function getDailyReport(int $userId, string $date): array
    {
        $transactions = Transaction::with('product')
            ->where('user_id', $userId)
            ->whereDate('transaction_date', $date)
            ->get();

        return [
            'date'              => $date,
            'total_transactions' => $transactions->count(),
            'total_revenue'     => $transactions->sum('total_price'),
            'total_items_sold'  => $transactions->sum('quantity'),
            'transactions'      => $transactions,
        ];
    }
}
