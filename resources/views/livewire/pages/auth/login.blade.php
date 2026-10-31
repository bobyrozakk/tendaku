<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\AuthRedirectService;
use App\Services\Booking\CartService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(AuthRedirectService $redirectService, CartService $cartService): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();

        if ($user) {
            $cartService->syncOnLogin($user);
        }

        // Jika email belum diverifikasi, arahkan ke halaman verifikasi email (resend email)
        if (! $user->hasVerifiedEmail()) {
            session()->forget('url.intended');

            $this->redirect(
                route('verification.notice', absolute: false),
                navigate: false
            );

            return;
        }

        Session::flash('success', $redirectService->getWelcomeMessage($user));

        $intended = session('url.intended');

        if ($intended && (
            str_contains($intended, 'verify-email') ||
            $intended === route('verification.notice') ||
            $intended === route('verification.notice', absolute: false)
        )) {
            session()->forget('url.intended');
        }

        $this->redirectIntended(
            default: $redirectService->getDestination($user, absolute: false),
            navigate: false
        );
    }
};
?>

<div>
    <x-slot:heading>Masuk ke Tendaku</x-slot:heading>

    <x-slot:subheading>
        Lanjutkan sewa perlengkapan campingmu.
    </x-slot:subheading>

    @include('livewire.pages.auth.partials.login-form')
</div>
