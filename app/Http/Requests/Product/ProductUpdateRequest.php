<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;

class ProductUpdateRequest extends ProductFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        return $product instanceof Product && Gate::allows('update', $product);
    }
}
