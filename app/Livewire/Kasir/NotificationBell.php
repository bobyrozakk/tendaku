<?php

namespace App\Livewire\Kasir;

use App\Models\Rental;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    public function render()
    {
        $user = Auth::user();
        $vendorId = $user?->vendor_id;

        $pendingRentals = collect();
        $pendingCount = 0;

        if ($vendorId) {
            $pendingRentals = Rental::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->where('status', 'pending')
                ->with(['customer'])
                ->latest('id')
                ->take(5)
                ->get();

            $pendingCount = Rental::withoutGlobalScope('vendor')
                ->where('vendor_id', $vendorId)
                ->where('status', 'pending')
                ->count();
        }

        return view('livewire.kasir.notification-bell', [
            'pendingRentals' => $pendingRentals,
            'pendingCount' => $pendingCount,
        ]);
    }
}
