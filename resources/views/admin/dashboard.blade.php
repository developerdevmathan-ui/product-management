<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Administrators') }}</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $adminCount }}</div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500">{{ __('Standard Users') }}</div>
                        <div class="mt-2 text-3xl font-semibold text-gray-900">{{ $userCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
