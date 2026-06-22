<?php

namespace App\Http\Requests\Product;

use App\Enums\ProductSort;
use App\Enums\StockStatus;
use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProductFilterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('viewAny', Product::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxPriceRules = ['nullable', 'numeric', 'min:0'];
        $legacyMaxPriceRules = ['nullable', 'numeric', 'min:0'];
        $maxQuantityRules = ['nullable', 'integer', 'min:0'];
        $dateToRules = ['nullable', 'date_format:Y-m-d'];

        if ($this->filled('min_price')) {
            $maxPriceRules[] = 'gte:min_price';
        }

        if ($this->filled('price_min')) {
            $legacyMaxPriceRules[] = 'gte:price_min';
        }

        if ($this->filled('min_quantity')) {
            $maxQuantityRules[] = 'gte:min_quantity';
        }

        if ($this->filled('date_from')) {
            $dateToRules[] = 'after_or_equal:date_from';
        }

        return [
            'search' => ['nullable', 'string', 'max:100'],
            'q' => ['nullable', 'string', 'max:100'],
            'stock_status' => ['nullable', 'string', Rule::in(array_map(fn (StockStatus $status): string => $status->value, StockStatus::cases()))],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => $maxPriceRules,
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => $legacyMaxPriceRules,
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'max_quantity' => $maxQuantityRules,
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => $dateToRules,
            'date_available' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'string', Rule::in(ProductSort::values())],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stock_status.in' => __('The selected stock status is invalid.'),
            'max_price.gte' => __('The max price must be greater than or equal to the min price.'),
            'price_max.gte' => __('The max price must be greater than or equal to the min price.'),
            'max_quantity.gte' => __('The max quantity must be greater than or equal to the min quantity.'),
            'date_from.date_format' => __('The available from field must be a valid date in YYYY-MM-DD format.'),
            'date_to.date_format' => __('The available to field must be a valid date in YYYY-MM-DD format.'),
            'date_to.after_or_equal' => __('The available to field must be on or after the available from field.'),
            'date_available.date_format' => __('The date available field must be a valid date in YYYY-MM-DD format.'),
            'sort.in' => __('The selected product sort option is invalid.'),
        ];
    }

    /**
     * Get normalized product filters for the service layer.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $validated = $this->validated();
        $dateAvailable = $validated['date_available'] ?? null;

        return [
            'search' => $validated['search'] ?? $validated['q'] ?? null,
            'stock_status' => $validated['stock_status'] ?? null,
            'min_price' => $validated['min_price'] ?? $validated['price_min'] ?? null,
            'max_price' => $validated['max_price'] ?? $validated['price_max'] ?? null,
            'min_quantity' => $validated['min_quantity'] ?? null,
            'max_quantity' => $validated['max_quantity'] ?? null,
            'date_from' => $validated['date_from'] ?? $dateAvailable,
            'date_to' => $validated['date_to'] ?? $dateAvailable,
            'sort' => $validated['sort'] ?? 'latest',
        ];
    }
}
