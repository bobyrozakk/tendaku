<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate(true);

        Session::regenerate();

        $user = auth()->user();
        $welcomeMessage = "Selamat datang Superadmin {$user->name}!";
        Session::flash('status', $welcomeMessage);
        Session::flash('success', $welcomeMessage);

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

