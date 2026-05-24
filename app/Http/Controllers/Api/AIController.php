<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAIRequest;
use App\Http\Resources\AiContentResource;
use App\Models\AiContent;
use App\Models\Product;
use App\Services\AiContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AIController extends Controller
{
    public function __construct(private AiContentService $aiService) {}

    public function generate(GenerateAIRequest $request): JsonResponse
    {
        $product = Product::where('id', $request->product_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $aiContent = $this->aiService->generateContent($product, $request->type);

        return response()->json([
            'success' => true,
            'type'    => $request->type,
            'data'    => [
                'content' => $aiContent->generated_content,
            ],
        ]);
    }

    public function generateByFeature(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'feature'    => ['required', Rule::in(array_keys($this->featureMap()))],
        ]);

        $product = $this->findOwnedProduct($request, $validated['product_id']);

        if (! $product) {
            return $this->productNotFoundResponse();
        }

        $generatedContents = collect($this->featureMap()[$validated['feature']])
            ->mapWithKeys(function (string $type) use ($product) {
                $content = $this->aiService->generateContent($product, $type);

                return [$type => $content->generated_content];
            });

        return response()->json([
            'success' => true,
            'feature' => $validated['feature'],
            'product' => $this->formatProductSummary($product),
            'data'    => $generatedContents,
        ]);
    }

    public function history(Request $request, int $product_id): JsonResponse
    {
        $product = $this->findOwnedProduct($request, $product_id);

        if (! $product) {
            return $this->productNotFoundResponse();
        }

        $latestContents = AiContent::query()
            ->where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->groupBy('type')
            ->map(fn($items) => new AiContentResource($items->first()));

        return response()->json([
            'success' => true,
            'product' => $this->formatProductSummary($product),
            'data'    => $latestContents,
        ]);
    }

    private function featureMap(): array
    {
        return [
            'instagram'   => ['caption', 'hashtag'],
            'marketplace' => ['marketplace', 'translate'],
            'promo'       => ['promo', 'caption'],
            'smart_reply' => ['smart_reply'],
        ];
    }

    private function findOwnedProduct(Request $request, int $productId): ?Product
    {
        return Product::query()
            ->where('id', $productId)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function productNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Produk tidak ditemukan atau bukan milik user.',
        ], 404);
    }

    private function formatProductSummary(Product $product): array
    {
        return [
            'id'              => $product->id,
            'product_name'    => $product->product_name,
            'price_formatted' => 'Rp ' . number_format($product->price, 0, ',', '.'),
        ];
    }
}
