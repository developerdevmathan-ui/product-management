<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ $product->title }}
            </h2>

            <div class="flex items-center gap-3">
                <a href="{{ route('products.index') }}"
                    class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-gray-700 uppercase transition bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    {{ __('Back') }}
                </a>
                @can('update', $product)
                    <a href="{{ route('products.edit', $product) }}"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold tracking-widest text-white uppercase transition bg-gray-800 border border-transparent rounded-md hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        {{ __('Edit') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 font-medium text-sm text-green-600">
                    {{ __(str_replace('-', ' ', session('status'))) }}
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Price') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ number_format((float) $product->price, 2) }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Date Available') }}</dt>
                            <dd class="mt-1 text-lg font-semibold text-gray-900">{{ $product->date_available->toFormattedDateString() }}</dd>
                        </div>
                    </dl>

                    <div class="pt-6 mt-6 border-t border-gray-200">
                        <h3 class="text-sm font-medium text-gray-500">{{ __('Description') }}</h3>
                        <div class="mt-3 product-description">
                            {!! app(App\Services\RichTextSanitizer::class)->clean($product->description) !!}
                        </div>
                    </div>

                    @can('delete', $product)
                        <form method="POST" action="{{ route('products.destroy', $product) }}" class="pt-6 mt-6 border-t border-gray-200"
                            onsubmit="return confirm('{{ __('Delete this product?') }}')">
                            @csrf
                            @method('DELETE')

                            <x-danger-button>
                                {{ __('Delete Product') }}
                            </x-danger-button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
