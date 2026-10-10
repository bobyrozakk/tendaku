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

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h1 class="mb-4 text-xl font-semibold text-darkbrown-800">Super Admin Login</h1>

    @include('livewire.pages.auth.partials.login-form', [
        'showForgotPassword' => false,
        'showGoogleLogin' => false,
        'showRegisterLink' => false,
    ])
</div>
