@if (auth()->user()?->isCustomer())
    <x-layouts.customer>
        <x-slot name="header">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="font-extrabold text-2xl md:text-3xl text-darkbrown-900 tracking-tight font-heading flex items-center gap-2">
                        <i data-lucide="user" class="w-7 h-7 text-darkbrown-800"></i>
                        <span>Akun Saya & Biodata Profil</span>
                    </h1>
                    <p class="text-sm text-darkbrown-600 mt-1">
                        Kelola biodata pribadi, data jaminan KTP, dan keamanan akun Tendaku Anda.
                    </p>
                </div>
                <div class="flex items-center gap-2 bg-avocado-50 border border-avocado-200 px-3.5 py-2 rounded-xl text-xs font-semibold text-avocado-900 shadow-sm self-start md:self-auto">
                    <i data-lucide="shield-check" class="w-4 h-4 text-avocado-600"></i>
                    <span>Customer Terdaftar</span>
                </div>
            </div>
        </x-slot>

        <div class="max-w-4xl mx-auto space-y-6 py-2">
            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <livewire:profile.update-profile-information-form />
            </div>

            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <livewire:profile.update-password-form />
            </div>

            <div class="card-tendaku p-6 sm:p-8 bg-white rounded-2xl shadow-tendaku border border-wheat-200">
                <livewire:profile.delete-user-form />
            </div>
        </div>
    </x-layouts.customer>
@else
    <x-app-layout>
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Profile') }}
            </h2>
        </x-slot>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>
            </div>
        </div>
    </x-app-layout>
@endif
