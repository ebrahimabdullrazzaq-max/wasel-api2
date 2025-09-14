<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Models\StoreCategory;

class ProductController extends Controller
{
    /**
     * Get all products (Admin only)
     */
    public function index(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $products = Product::with('store', 'category')->get();

        return response()->json($products);
    }

    /**
     * Create a new product
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'store_id' => 'required|exists:stores,id',
            'store_category_id' => 'required|exists:store_categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            // Handle image upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
            }

            // ✅ Get the store category
            $storeCategory = StoreCategory::findOrFail($request->store_category_id);

            // ✅ Extract global category_id from store_category
            $categoryId = $storeCategory->category_id;

            if (!$categoryId) {
                return response()->json([
                    'error' => 'Invalid category association',
                    'message' => 'This store category is not linked to a global category.'
                ], 400);
            }

            // ✅ Create product with category_id
            $product = Product::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'category_id' => $categoryId,           // ✅ Critical: not null
                'store_id' => $request->store_id,
                'store_category_id' => $request->store_category_id,
                'image' => $imagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => $this->formatProduct($product)
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Product creation failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to create product',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update existing product
     */
    public function update(Request $request, $id)
    {
        try {
            $product = Product::findOrFail($id);
            $request->mergeIfMissing(['_method' => 'PATCH']);
  $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
            'store_id' => 'required|exists:stores,id',
            'store_category_id' => 'required|exists:store_categories,id',
            'image' => 'nullable|image|max:2048',
        ]);
        


            // ✅ Get store category to derive category_id
            $storeCategory = StoreCategory::findOrFail($request->store_category_id);
            $categoryId = $storeCategory->category_id;

            if (!$categoryId) {
                return response()->json([
                    'error' => 'Invalid category association',
                    'message' => 'This store category is not linked to a global category.'
                ], 400);
            }

            // Handle image update
            if ($request->hasFile('image')) {
                if ($product->image && Storage::disk('public')->exists($product->image)) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->image = $request->file('image')->store('products', 'public');
            }

            // ✅ Update all fields including category_id
            $product->fill([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'stock' => $request->stock,
                'store_id' => $request->store_id,
                'store_category_id' => $request->store_category_id,
                'category_id' => $categoryId, // ✅ Correct: from store_category
            ]);

            $product->save();

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $this->formatProduct($product),
            ]);

        } catch (\Exception $e) {
            \Log::error('Product update failed: ' . $e->getMessage());
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a product
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Delete image if exists
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a single product
     */
    public function show($id)
    {
        try {
            $product = Product::with('category')->findOrFail($id);
            return response()->json([
                'success' => true,
                'product' => $this->formatProduct($product)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get products by store (and optional category)
     */
    public function getByStore(Request $request, $storeId)
    {
        $query = Product::where('store_id', $storeId);

        if ($request->has('category_id')) {
            $categoryId = $request->input('category_id');
            if ($categoryId !== null) {
                $query->where('store_category_id', (int)$categoryId);
            }
        }

        $products = $query->get();

        return response()->json([
            'success' => true,
            'products' => $products->map(function ($product) {
                return $this->formatProduct($product);
            })
        ]);
    }

    /**
     * Format product for API response
     */
    private function formatProduct($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'category_id' => $product->category_id,
            'category' => $product->category?->name,
            'store_id' => $product->store_id,
            'store_category_id' => $product->store_category_id,
            'image_url' => $product->image ? asset('storage/' . $product->image) : null,
            'image' => $product->image ? asset('storage/' . $product->image) : null,
            'created_at' => $product->created_at?->toDateTimeString(),
            'updated_at' => $product->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * Get store-specific categories
     */
    public function getStoreCategories(Request $request, $storeId)
    {
        $categories = StoreCategory::where('store_id', $storeId)
            ->where('is_active', true)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'image' => $category->image ? asset('storage/' . $category->image) : null,
                    'is_active' => $category->is_active,
                ];
            });

        return response()->json([
            'success' => true,
            'categories' => $categories
        ]);
    }
}