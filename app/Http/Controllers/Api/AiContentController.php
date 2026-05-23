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

            throw ValidationException::withMessages([
                'ai' => 'Gagal membuat konten AI. Silakan coba lagi beberapa saat.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Konten AI berhasil digenerate! 🤖✨',
            'data'    => new AiContentResource($aiContent),
        ]);
    }
}
