<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    public function paginateForUser(int $userId, int $perPage = 10, ?string $search = null): LengthAwarePaginator
    {
        $query = Product::where('user_id', $userId)
            ->with('aiContent')
            ->latest();

        if (!empty($search)) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('product_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('target_market', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function createProduct(array $data, int $userId): Product
    {
        $data['user_id'] = $userId;

        return Product::create($data);
    }

    public function updateProduct(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    public function deleteProduct(Product $product): void
    {
        $product->delete();
    }
}
