<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::query()->with('category');

        if ($search = $request->string('search')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('sku', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $allowedSorts = ['name', 'price', 'stock_quantity', 'created_at'];
        $sort = $request->string('sort', 'created_at')->toString();
        $order = $request->string('order', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }
        $query->orderBy($sort, $order);

        $perPage = min($request->integer('per_page', 15), 100);

        return ProductResource::collection($query->paginate($perPage));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image_path' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
        }

        $product = Product::create($data)->load('category');

        return (new ProductResource($product))->response()->setStatusCode(201);
    }

    public function show(Product $product): ProductResource
    {
        $product->load('category');

        return new ProductResource($product);
    }

    public function update(Request $request, Product $product): ProductResource
    {
        $data = $request->validate([
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,' . $product->id],
            'sku' => ['sometimes', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image_path' => ['nullable', 'string', 'max:500'],
        ]);

        $product->update($data);
        $product->load('category');

        return new ProductResource($product);
    }

    public function uploadImage(Request $request, Product $product): ProductResource
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'], // 5MB
        ]);

        // Delete old image if exists
        if ($product->image_path && Storage::disk('garage')->exists($product->image_path)) {
            Storage::disk('garage')->delete($product->image_path);
        }

        $path = $request->file('image')->storePublicly('products', 'garage');

        $product->update(['image_path' => $path]);
        $product->load('category');

        return new ProductResource($product);
    }
    
    public function showImage(Product $product): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\JsonResponse
    {
        if (! $product->image_path) {
            return response()->json(['message' => 'No image.'], 404);
        }

        if (! Storage::disk('garage')->exists($product->image_path)) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        $stream = Storage::disk('garage')->readStream($product->image_path);

        $mime = Storage::disk('garage')->mimeType($product->image_path) ?: 'application/octet-stream';

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
    
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted.']);
    }
}