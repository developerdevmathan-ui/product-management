<?php

namespace App\Http\Requests\Product;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;

class ProductStoreRequest extends ProductFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Product::class);
    }
}
