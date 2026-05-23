<?php

namespace App\Services;

use App\Models\AiContent;
use App\Models\Product;
use App\Models\Transaction;

class DashboardService
{
    public function getSummary(int $userId): array
    {
        $today = now()->toDateString();

        $salesTodayTransactions = Transaction::where('user_id', $userId)
            ->whereDate('transaction_date', $today)
            ->get();

        $salesToday = $salesTodayTransactions->sum('total_price');

        $totalTransactions = Transaction::where('user_id', $userId)->count();
        $totalProducts = Product::where('user_id', $userId)->count();
        $aiContentsGenerated = AiContent::where('user_id', $userId)->count();

        $bestSelling = Product::where('user_id', $userId)
            ->withSum('transactions', 'quantity')
            ->orderByDesc('transactions_sum_quantity')
            ->first();

        $lowStock = Product::where('user_id', $userId)
            ->where('stock', '<=', 5)
            ->where('is_active', true)
            ->orderBy('stock')
            ->get(['id', 'product_name', 'stock']);

        $latestAiContent = AiContent::where('user_id', $userId)
            ->latest()
            ->first();

        return [
            'sales_today' => [
                'total_revenue'     => $salesToday,
                'total_transactions' => $salesTodayTransactions->count(),
                'date'              => $today,
            ],
            'total_transactions'   => $totalTransactions,
            'total_products'       => $totalProducts,
            'ai_contents_generated' => $aiContentsGenerated,
            'low_stock_products'   => $lowStock,
            'best_selling_product' => $bestSelling ? [
                'id'            => $bestSelling->id,
                'product_name'  => $bestSelling->product_name,
                'total_sold'    => $bestSelling->transactions_sum_quantity ?? 0,
            ] : null,
            'latest_ai_content' => $latestAiContent ? [
                'type'    => $latestAiContent->type,
                'content' => $latestAiContent->generated_content,
            ] : null,
        ];
    }
}
