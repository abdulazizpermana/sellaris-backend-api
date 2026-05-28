<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAIRequest;
use App\Http\Resources\AiContentResource;
use App\Models\Product;
use App\Services\AiContentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiContentController extends Controller
{
    public function __construct(private AiContentService $aiService) {}

    public function generate(GenerateAIRequest $request)
    {
        $product = Product::where('id', $request->product_id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $product) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk tidak ditemukan atau bukan milik akun ini.',
            ]);
        }

        try {
            $aiContent = $this->aiService->generateContent(
                $product,
                $request->input('type', 'caption')  // ← default 'caption' kalau tidak dikirim
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'type' => 'Jenis konten AI tidak didukung.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            if ($e->getMessage() === 'AI_LIMIT_EXCEEDED') {
                return response()->json([
                    'success' => false,
                    'type' => 'AI_LIMIT',
                    'message' => 'Limit generate AI hari ini sudah habis.',
                ], 429);
            }

            if ($e->getMessage() === 'AI_SERVICE_UNAVAILABLE') {
                return response()->json([
                    'success' => false,
                    'type' => 'AI_SERVICE_ERROR',
                    'message' => 'Layanan AI sedang gangguan.',
                ], 503);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada layanan AI.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Konten AI berhasil digenerate! 🤖✨',
            'data'    => new AiContentResource($aiContent),
        ]);
    }

    public function generateAll(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::where('id', $request->product_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $types   = ['caption', 'hashtag', 'marketplace', 'promo', 'translate', 'smart_reply'];
        $results = [];
        $errors = [];

        foreach ($types as $type) {
            try {
                $content = $this->aiService->generateContent($product, $type);
                $results[$type] = $content->generated_content;
            } catch (\Throwable $e) {
                $results[$type] = null;
                $errors[$type] = $e->getMessage() === 'AI_LIMIT_EXCEEDED'
                    ? 'AI_LIMIT_EXCEEDED'
                    : ($e->getMessage() === 'AI_SERVICE_UNAVAILABLE'
                        ? 'AI_SERVICE_UNAVAILABLE'
                        : 'AI_GENERATION_FAILED');
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Semua konten berhasil digenerate! 🤖',
            'data'    => $results,
            'errors'  => $errors,
        ]);
    }
}
