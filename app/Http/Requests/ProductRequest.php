<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Services\RichTextSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        if ($product instanceof Product) {
            return Gate::allows('update', $product);
        }

        return Gate::allows('create', Product::class);
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->filled('title') ? trim((string) $this->input('title')) : $this->input('title'),
            'description' => $this->has('description')
                ? app(RichTextSanitizer::class)->clean((string) $this->input('description'))
                : $this->input('description'),
            'price' => $this->filled('price') ? trim((string) $this->input('price')) : $this->input('price'),
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
            'title' => ['bail', 'required', 'string', 'min:3', 'max:255'],
            'description' => ['bail', 'required', 'string'],
            'price' => ['bail', 'required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999999.99'],
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
            'title',
            'description',
            'price',
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
