<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAIRequest;
use App\Models\Product;
use App\Services\AiContentService;

class AIController extends Controller
{
    public function __construct(private AiContentService $aiService) {}

    public function generate(GenerateAIRequest $request)
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
}
