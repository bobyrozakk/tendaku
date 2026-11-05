<?php

use App\Livewire\Forms\LoginForm;
use App\Services\Auth\AuthRedirectService;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(AuthRedirectService $redirectService): void
    {
        $this->validate();

        $this->form->authenticate(true);

        Session::regenerate();

        $user = auth()->user();
        Session::flash('success', $redirectService->getWelcomeMessage($user));

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <x-slot:heading>Super Admin Login</x-slot:heading>
    <x-slot:subheading>Akses panel super admin Tendaku.</x-slot:subheading>

    @include('livewire.pages.auth.partials.login-form', [
        'showForgotPassword' => false,
        'showGoogleLogin' => false,
        'showRegisterLink' => false,
    ])
</div>
