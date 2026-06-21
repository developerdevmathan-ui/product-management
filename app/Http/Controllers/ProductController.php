<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
    ) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'gte:price_min'],
            'date_available' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return view('products.index', [
            'products' => $this->products->paginate($filters),
            'filters' => $filters,
            'search' => $filters['q'] ?? '',
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', [
            'product' => new Product,
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        Gate::authorize('create', Product::class);

        $product = $this->products->create($request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-created');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): View
    {
        Gate::authorize('view', $product);

        return view('products.show', [
            'product' => $product,
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('products.edit', [
            'product' => $product,
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product = $this->products->update($product, $request->validated());

        return redirect()
            ->route('products.show', $product)
            ->with('status', 'product-updated');
    }

    /**
     * Remove the specified product.
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $this->products->delete($product);

        return redirect()
            ->route('products.index')
            ->with('status', 'product-deleted');
    }
}
