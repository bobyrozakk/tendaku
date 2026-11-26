<x-layouts.customer :full-width="true">
    <x-slot name="title">Tendaku</x-slot>

    @push('styles')
        @vite('resources/css/pages/landing/landing.css')
    @endpush

    @include('landing.sections.hero')

    @include('landing.sections.categories')

    @include('landing.sections.bestsellers')

    @include('landing.sections.customer-features')

    @include('landing.sections.why-tendaku')

    @include('landing.sections.ktp-guarantee')

    @include('landing.sections.vendor-benefits')

    @include('landing.sections.testimonials')

    @include('landing.sections.final-cta')

    @push('scripts')
        @vite('resources/js/pages/landing/landing.js')
    @endpush

</x-layouts.customer>
