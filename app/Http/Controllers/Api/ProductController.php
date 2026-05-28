<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request)
    {
        $products = $this->productService->paginateForUser(
            $request->user()->id,
            (int) $request->get('per_page', 10),
            $request->get('search')
        );

        return response()->json([
            'success' => true,
            'data'    => ProductResource::collection($products),
            'meta'    => [
                'total'        => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_url'] = Storage::url(
                $request->file('image')->store('products', 'public')
            );
        }

        $product = $this->productService->createProduct($data, $request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan! ✅',
            'data'    => new ProductResource($product->load('aiContent')),
        ], 201);
    }

    public function show(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductResource($product->load('aiContent')),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_url'] = Storage::url(
                $request->file('image')->store('products', 'public')
            );
        }

        $product = $this->productService->updateProduct($product, $data);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil diupdate! ✅',
            'data'    => new ProductResource($product->load('aiContent')),
        ]);
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $this->productService->deleteProduct($product);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil dihapus.',
        ]);
    }

    public function image(string $filename): Response
    {
        $path = 'products/' . $filename;

        abort_unless(Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }
}
