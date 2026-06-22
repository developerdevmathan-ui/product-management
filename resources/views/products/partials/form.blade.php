@csrf

@php
    $quantityValue = old('quantity', $product->quantity ?? 0);
    $displayStockStatus = \App\Enums\StockStatus::fromQuantity((int) ($quantityValue === '' ? 0 : $quantityValue));
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="sku" :value="__('SKU')" />
        <x-text-input id="sku" name="sku" type="text" maxlength="10" pattern="PRD-[0-9]{6}" class="block mt-1 w-full" :value="old('sku', $product->sku)" placeholder="PRD-000001" autofocus />
        <x-input-error :messages="$errors->get('sku')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="title" :value="__('Title')" />
        <x-text-input id="title" name="title" type="text" minlength="3" maxlength="255" class="block mt-1 w-full" :value="old('title', $product->title)" required />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>
</div>

<div class="mt-4">
    <x-input-label for="description" :value="__('Description')" />
    <textarea id="description" name="description" rows="10" required data-rich-text-editor
        class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $product->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
    <div>
        <x-input-label for="price" :value="__('Price')" />
        <x-text-input id="price" name="price" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" class="block mt-1 w-full" :value="old('price', $product->price)" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="quantity" :value="__('Quantity')" />
        <x-text-input id="quantity" name="quantity" type="number" min="0" max="4294967295" step="1" inputmode="numeric" class="block mt-1 w-full" :value="$quantityValue" required />
        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
    <div>
        <x-input-label for="stock_status_display" :value="__('Stock Status')" />
        <x-text-input id="stock_status_display" type="text" class="block mt-1 w-full bg-gray-100" :value="$displayStockStatus->label()" disabled />
        <x-input-error :messages="$errors->get('stock_status')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_available" :value="__('Date Available')" />
        <x-text-input id="date_available" name="date_available" type="date" class="block mt-1 w-full" :value="old('date_available', optional($product->date_available)->format('Y-m-d'))" required />
        <x-input-error :messages="$errors->get('date_available')" class="mt-2" />
    </div>
</div>

<div class="flex items-center justify-end gap-3 mt-6">
    <a href="{{ route('products.index') }}"
        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        {{ __('Cancel') }}
    </a>

    <x-primary-button>
        {{ $submitLabel }}
    </x-primary-button>
</div>
