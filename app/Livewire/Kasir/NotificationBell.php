<?php

namespace App\Livewire\Kasir;

use App\Models\NotificationLog;
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
        $lostUnitNotifs = collect();
        $lostUnitCount = 0;

        if ($vendorId) {
            // Notif pesanan pending (kasir & owner)
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

            // Notif barang hilang — hanya tampil untuk vendor_owner
            if ($user?->isVendorOwner()) {
                $lostUnitNotifs = NotificationLog::withoutGlobalScope('vendor')
                    ->where('vendor_id', $vendorId)
                    ->where('type', 'lost_unit')
                    ->where('status', 'sent')
                    ->with('rental')
                    ->latest()
                    ->take(5)
                    ->get();

                $lostUnitCount = NotificationLog::withoutGlobalScope('vendor')
                    ->where('vendor_id', $vendorId)
                    ->where('type', 'lost_unit')
                    ->where('status', 'sent')
                    ->count();
            }
        }

        return view('livewire.kasir.notification-bell', [
            'pendingRentals' => $pendingRentals,
            'pendingCount' => $pendingCount,
            'lostUnitNotifs' => $lostUnitNotifs,
            'lostUnitCount' => $lostUnitCount,
        ]);
    }
}
