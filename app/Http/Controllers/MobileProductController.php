<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;

class MobileProductController extends Controller
{
    /**
     * Get all products with pagination and filters
     * GET /api/products/list
     * Query params: page, per_page, category_id, search, sort_by, sort_order
     */
    public function list(Request $request)
    {
        $query = Product::query();
        
        // Filter by category
        if ($request->has('category_id') && $request->category_id != '') {
            $query->where('category', $request->category_id);
        }
        
        // Search by name or description
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
        
        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $perPage = $request->get('per_page', 20);
        $products = $query->paginate($perPage);
        
        // Format response
        $formatted = $products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'feature_photo' => $product->feature_photo,
                'category_id' => $product->category,
                'category_name' => $product->category_text,
                'stock_quantity' => $product->stock_quantity ?? 0,
                'in_stock' => ($product->stock_quantity ?? 0) > 0,
                'created_at' => $product->created_at,
            ];
        });
        
        return response()->json([
            'code' => 1,
            'message' => 'Products retrieved successfully',
            'data' => [
                'products' => $formatted,
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]
        ]);
    }
    
    /**
     * Get single product details
     * GET /api/products/detail/{id}
     */
    public function detail($id)
    {
        $product = Product::find($id);
        
        if (!$product) {
            return response()->json([
                'code' => 0,
                'message' => 'Product not found'
            ], 404);
        }
        
        // Get related products (same category)
        $relatedProducts = Product::where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->limit(5)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => $p->price,
                    'feature_photo' => $p->feature_photo,
                ];
            });
        
        return response()->json([
            'code' => 1,
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'price' => $product->price,
                'feature_photo' => $product->feature_photo,
                'category_id' => $product->category,
                'category_name' => $product->category_text,
                'stock_quantity' => $product->stock_quantity ?? 0,
                'in_stock' => ($product->stock_quantity ?? 0) > 0,
                'specifications' => $product->specifications ?? '',
                'images' => $this->getProductImages($product),
                'created_at' => $product->created_at,
                'related_products' => $relatedProducts,
            ]
        ]);
    }
    
    /**
     * Get product categories
     * GET /api/products/categories
     */
    public function categories()
    {
        $categories = ProductCategory::orderBy('name')->get();
        
        $formatted = $categories->map(function($category) {
            $productCount = Product::where('category', $category->id)->count();
            
            return [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description ?? '',
                'product_count' => $productCount,
            ];
        });
        
        return response()->json([
            'code' => 1,
            'data' => $formatted
        ]);
    }
    
    /**
     * Helper: Get product images
     */
    private function getProductImages($product)
    {
        $images = [];
        
        // Add feature photo first
        if ($product->feature_photo) {
            $images[] = $product->feature_photo;
        }
        
        // Add additional photos if available
        if ($product->photos) {
            $photos = json_decode($product->photos, true);
            if (is_array($photos)) {
                $images = array_merge($images, $photos);
            }
        }
        
        return $images;
    }
}
