<?php

namespace App\Http\Requests\Product;

use App\Enums\StockStatus;
use App\Services\RichTextSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class ProductFormRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => $this->filled('sku') ? Str::upper(trim((string) $this->input('sku'))) : $this->input('sku'),
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : $this->input('title'),
            'description' => $this->has('description')
                ? app(RichTextSanitizer::class)->clean((string) $this->input('description'))
                : $this->input('description'),
            'price' => $this->filled('price') ? trim((string) $this->input('price')) : $this->input('price'),
            'quantity' => $this->filled('quantity') ? trim((string) $this->input('quantity')) : $this->input('quantity'),
            'date_available' => $this->filled('date_available') ? trim((string) $this->input('date_available')) : $this->input('date_available'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'bail',
                'nullable',
                'string',
                'regex:/^PRD-\d{6}$/',
            ],
            'title' => ['bail', 'required', 'string', 'min:3', 'max:255'],
            'description' => ['bail', 'required', 'string'],
            'price' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:99999999.99'],
            'quantity' => ['bail', 'required', 'integer', 'min:0', 'max:4294967295'],
            'stock_status' => ['sometimes', 'nullable', Rule::enum(StockStatus::class)],
            'date_available' => ['bail', 'required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('description')) {
                return;
            }

            if ($this->richTextIsEmpty((string) $this->input('description'))) {
                $validator->errors()->add('description', __('The description field must contain readable content.'));
            }
        });
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'date_available' => __('date available'),
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
            'sku.regex' => __('The SKU field must use the PRD-000001 format.'),
            'price.min' => __('The price field must be greater than 0.'),
            'quantity.min' => __('The quantity field must be at least 0.'),
            'date_available.date_format' => __('The date available field must be a valid date in YYYY-MM-DD format.'),
        ];
    }

    /**
     * Get the validated product payload.
     *
     * @param  array<int, string>|string|null  $key
     * @param  mixed  $default
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        return Arr::only(parent::validated($key, $default), [
            'sku',
            'title',
            'description',
            'price',
            'quantity',
            'date_available',
        ]);
    }

    /**
     * Determine whether sanitized rich text contains meaningful content.
     */
    private function richTextIsEmpty(string $html): bool
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00a0}|\s+/u', '', $text) ?? '';

        return $text === '';
    }
}
