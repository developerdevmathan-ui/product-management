<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Products') }}
            </h2>

            @can('create', App\Models\Product::class)
                <a href="{{ route('products.create') }}"
                    class="inline-flex items-center justify-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    {{ __('Create Product') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 font-medium text-sm text-green-600">
                    {{ __(str_replace('-', ' ', session('status'))) }}
                </div>
            @endif

            <form method="GET" action="{{ route('products.index') }}" class="mb-6">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <div class="md:col-span-2">
                        <x-input-label for="search" :value="__('Search products')" />
                        <x-text-input id="search" name="search" type="search" maxlength="100" class="block w-full mt-1"
                            :value="$search" placeholder="Search by SKU, title, or description" />
                    </div>

                    <div>
                        <x-input-label for="stock_status" :value="__('Stock Status')" />
                        <select id="stock_status" name="stock_status"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('Any Status') }}</option>
                            @foreach ($stockStatuses as $status)
                                <option value="{{ $status->value }}" @selected(($filters['stock_status'] ?? '') === $status->value)>
                                    {{ $status->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="sort" :value="__('Sort')" />
                        <select id="sort" name="sort"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach ($sortOptions as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['sort'] ?? 'latest') === $value)>
                                    {{ __($label) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="min_price" :value="__('Min Price')" />
                        <x-text-input id="min_price" name="min_price" type="number" min="0" step="0.01" inputmode="decimal" class="block w-full mt-1"
                            :value="$filters['min_price'] ?? ''" />
                    </div>

                    <div>
                        <x-input-label for="max_price" :value="__('Max Price')" />
                        <x-text-input id="max_price" name="max_price" type="number" min="0" step="0.01" inputmode="decimal" class="block w-full mt-1"
                            :value="$filters['max_price'] ?? ''" />
                    </div>

                    <div>
                        <x-input-label for="min_quantity" :value="__('Min Quantity')" />
                        <x-text-input id="min_quantity" name="min_quantity" type="number" min="0" step="1" inputmode="numeric" class="block w-full mt-1"
                            :value="$filters['min_quantity'] ?? ''" />
                    </div>

                    <div>
                        <x-input-label for="max_quantity" :value="__('Max Quantity')" />
                        <x-text-input id="max_quantity" name="max_quantity" type="number" min="0" step="1" inputmode="numeric" class="block w-full mt-1"
                            :value="$filters['max_quantity'] ?? ''" />
                    </div>

                    <div>
                        <x-input-label for="date_from" :value="__('Available From')" />
                        <x-text-input id="date_from" name="date_from" type="date" class="block w-full mt-1"
                            :value="$filters['date_from'] ?? ''" />
                    </div>

                    <div>
                        <x-input-label for="date_to" :value="__('Available To')" />
                        <x-text-input id="date_to" name="date_to" type="date" class="block w-full mt-1"
                            :value="$filters['date_to'] ?? ''" />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-4">
                    @if ($errors->hasAny(['search', 'q', 'stock_status', 'min_price', 'max_price', 'price_min', 'price_max', 'min_quantity', 'max_quantity', 'date_from', 'date_to', 'date_available', 'sort']))
                        <div class="mr-auto text-sm text-red-600">
                            {{ __('Please check the product filters.') }}
                        </div>
                    @endif

                    <div class="flex items-end gap-3">
                        <x-primary-button>
                            {{ __('Apply') }}
                        </x-primary-button>

                        @if ($search !== '' || filled($filters['stock_status'] ?? null) || filled($filters['min_price'] ?? null) || filled($filters['max_price'] ?? null) || filled($filters['min_quantity'] ?? null) || filled($filters['max_quantity'] ?? null) || filled($filters['date_from'] ?? null) || filled($filters['date_to'] ?? null) || ($filters['sort'] ?? 'latest') !== 'latest')
                            <a href="{{ route('products.index') }}"
                                class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </div>
                </div>
            </form>

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('SKU') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('Title') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('Price') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('Quantity') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('Stock Status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    {{ __('Date Available') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">
                                    {{ __('Actions') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($products as $product)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $product->sku }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                        <a href="{{ route('products.show', $product) }}" class="hover:text-indigo-600">
                                            {{ $product->title }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ number_format((float) $product->price, 2) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ number_format($product->quantity) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $product->stock_status->label() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $product->date_available->toFormattedDateString() }}
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('products.show', $product) }}" class="text-gray-600 hover:text-gray-900">
                                                {{ __('View') }}
                                            </a>
                                            @can('update', $product)
                                                <a href="{{ route('products.edit', $product) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ __('Edit') }}
                                                </a>
                                            @endcan

                                            @can('delete', $product)
                                                <form method="POST" action="{{ route('products.destroy', $product) }}"
                                                    onsubmit="return confirm('{{ __('Delete this product?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">
                                                        {{ __('Delete') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-sm text-center text-gray-500">
                                        @if ($search !== '')
                                            {{ __('No products matched your search.') }}
                                        @else
                                            {{ __('No products have been created yet.') }}
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($products->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
