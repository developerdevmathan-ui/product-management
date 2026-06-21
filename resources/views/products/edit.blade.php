<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Edit Product') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('products.update', $product) }}">
                    @method('PUT')

                    @include('products.partials.form', [
                        'product' => $product,
                        'submitLabel' => __('Update Product'),
                    ])
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
