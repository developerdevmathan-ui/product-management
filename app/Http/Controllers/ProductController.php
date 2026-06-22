<?php

namespace App\Http\Controllers;

use App\Enums\ProductSort;
use App\Enums\StockStatus;
use App\Http\Requests\Product\ProductFilterRequest;
use App\Http\Requests\Product\ProductStoreRequest;
use App\Http\Requests\Product\ProductUpdateRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
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
    public function index(ProductFilterRequest $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $filters = $request->filters();

        return view('products.index', [
            'products' => $this->products->paginate($filters),
            'filters' => $filters,
            'search' => $filters['search'] ?? '',
            'sortOptions' => ProductSort::options(),
            'stockStatuses' => StockStatus::cases(),
        ]);
    }

    /**
     * Show the form for creating a product.
     */
    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('products.create', [
            'product' => $this->products->make(),
            'stockStatuses' => StockStatus::cases(),
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(ProductStoreRequest $request): RedirectResponse
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
            'stockStatuses' => StockStatus::cases(),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(ProductUpdateRequest $request, Product $product): RedirectResponse
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
