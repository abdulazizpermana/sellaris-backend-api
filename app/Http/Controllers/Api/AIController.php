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
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AIController extends Controller
{
    public function __construct(private AiContentService $aiService) {}

    // ─── Single Generate ──────────────────────────────────────
    public function generate(GenerateAIRequest $request): JsonResponse
    {
        $product = Product::where('id', $request->product_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // ← Tambah default 'caption' kalau type tidak dikirim
        $type = $request->input('type', 'caption');

        try {
            $aiContent = $this->aiService->generateContent($product, $type);

            return response()->json([
                'success' => true,
                'type'    => $type,
                'data'    => [
                    'content' => $aiContent->generated_content,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('generate error', [
                'error'      => $e->getMessage(),
                'product_id' => $product->id,
                'type'       => $type,
            ]);

            // Cek apakah rate limit
            if ($this->isRateLimit($e->getMessage())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gemini AI limit harian tercapai. Coba lagi besok.',
                    'errors'  => ['ai' => 'AI_LIMIT_EXCEEDED'],
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate konten AI. Coba lagi.',
                'errors'  => ['ai' => $e->getMessage()],
            ], 500);
        }
    }

    // ─── Generate All ─────────────────────────────────────────
    public function generateAll(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
        ]);

        $product = $this->findOwnedProduct($request, $request->product_id);

        if (! $product) {
            return $this->productNotFoundResponse();
        }

        $types   = ['caption', 'hashtag', 'marketplace', 'promo', 'translate', 'smart_reply'];
        $results = [];
        $errors  = [];
        $limitCount = 0;

        foreach ($types as $type) {
            try {
                $content        = $this->aiService->generateContent($product, $type);
                $results[$type] = $content->generated_content;
                sleep(1); // delay antar request
            } catch (\Throwable $e) {
                Log::error("generateAll error for type: $type", [
                    'error'   => $e->getMessage(),
                    'product' => $product->id,
                ]);

                if ($this->isRateLimit($e->getMessage())) {
                    $errors[$type] = 'AI_LIMIT_EXCEEDED';
                    $limitCount++;
                } else {
                    $errors[$type] = $e->getMessage();
                }

                $results[$type] = null;
            }
        }

        // Kalau semua gagal karena rate limit → return 429
        if ($limitCount === count($types)) {
            return response()->json([
                'success' => false,
                'message' => 'Gemini AI limit harian tercapai. Coba lagi besok.',
                'errors'  => ['ai' => 'AI_LIMIT_EXCEEDED'],
            ], 429);
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua konten berhasil digenerate! 🤖✨',
            'product' => $this->formatProductSummary($product),
            'data'    => $results,
            'errors'  => $errors,
        ]);
    }

    // ─── Generate By Feature ──────────────────────────────────
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

        try {
            $generatedContents = [];

            foreach ($this->featureMap()[$validated['feature']] as $type) {
                $content = $this->aiService->generateContent($product, $type);
                $generatedContents[$type] = $content->generated_content;
            }

            return response()->json([
                'success' => true,
                'feature' => $validated['feature'],
                'product' => $this->formatProductSummary($product),
                'data'    => $generatedContents,
            ]);
        } catch (\Throwable $e) {
            Log::error('generateByFeature error', [
                'error'      => $e->getMessage(),
                'product_id' => $product->id,
                'feature'    => $validated['feature'],
            ]);

            if ($this->isRateLimit($e->getMessage())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gemini AI limit harian tercapai. Coba lagi besok.',
                    'errors'  => ['ai' => 'AI_LIMIT_EXCEEDED'],
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal generate konten AI.',
                'errors'  => ['ai' => $e->getMessage()],
            ], 500);
        }
    }

    // ─── History ──────────────────────────────────────────────
    public function history(Request $request, int $product_id): JsonResponse
    {
        $product = $this->findOwnedProduct($request, $product_id);

        if (! $product) {
            return $this->productNotFoundResponse();
        }

        $latestContents = AiContent::query()
            ->where('product_id', $product->id)
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('type')
            ->map(fn($items) => AiContentResource::make($items->first()))
            ->toArray();

        return response()->json([
            'success' => true,
            'product' => $this->formatProductSummary($product),
            'data'    => $latestContents,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────
    private function isRateLimit(string $message): bool
    {
        $msg = strtolower($message);
        return str_contains($msg, '429')
            || str_contains($msg, 'quota')
            || str_contains($msg, 'resource_exhausted')
            || str_contains($msg, 'resource exhausted')
            || str_contains($msg, 'rate limit')
            || str_contains($msg, 'too many requests');
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
