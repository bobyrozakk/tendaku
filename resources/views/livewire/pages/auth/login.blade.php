<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $user = auth()->user();

        $destination = match (true) {
            $user->isVendorStaff() => route('vendor.dashboard', absolute: false),
            $user->isCustomer()    => route('home', absolute: false),
            default                => route('dashboard', absolute: false), // super_admin
        };

        $this->redirectIntended(default: $destination, navigate: true);
    }
}; ?>

<div>
    @include('livewire.pages.auth.partials.login-form')
</div>
