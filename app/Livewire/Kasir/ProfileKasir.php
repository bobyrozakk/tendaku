<?php

namespace App\Livewire\Kasir;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.profil-kasir')]
class ProfileKasir extends Component
{
    public string $name = '';

    public string $email = '';

    public ?string $phone = '';

    public ?string $address = '';

    public bool $infoSaved = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->address = $user->address ?? '';
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->infoSaved = true;
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function render()
    {
        $user = Auth::user()->load('vendor');

        return view('livewire.kasir.profile-kasir', [
            'user' => $user,
            'vendor' => $user->vendor,
        ]);
    }
}
