<?php

namespace App\Livewire\Customer;

use App\Models\ItemUnit;
use App\Models\MasterItem;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\CartService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductBooking extends Component
{
    /** Rentang kalender yang dicek ketersediaannya (hari ini + N hari ke depan). */
    private const CALENDAR_AHEAD_DAYS = 60;

    /** Model produk yang di-mount dari halaman detail */
    public MasterItem $product;

    /** Tanggal pengambilan barang (default: besok) */
    public string $pickup_date = '';

    /** Jam pengambilan barang */
    public string $pickup_time = '09:00';

    /** Durasi sewa dalam hari */
    public int $duration_days = 1;

    /** Tanggal pengembalian barang */
    public string $return_date = '';

    /** Jam pengembalian barang */
    public string $return_time = '09:00';

    /** Kuantitas unit yang disewa */
    public int $quantity = 1;

    /** True jika produk ini sudah ada di keranjang */
    public bool $inCart = false;

    /**
     * Inisialisasi properti saat komponen dimuat.
     */
    public function mount(MasterItem $product): void
    {
        $this->product = $product;
        $this->quantity = 1;
        $this->duration_days = 1;

        $this->pickup_date = Carbon::tomorrow()->format('Y-m-d');
        $this->pickup_time = '09:00';

        $this->syncReturnFromDuration();

        // Cek produk pada keranjang multi-item maupun struktur lama.
        $cart = app(CartService::class)->get(Auth::user());

        $this->inCart = false;

        if (is_array($cart)) {
            $cartItems = $cart['items'] ?? [];

            foreach ($cartItems as $item) {
                if (
                    (int) ($item['master_item_id'] ?? 0)
                    === (int) $this->product->id
                ) {
                    $this->inCart = true;
                    break;
                }
            }

            // Kompatibilitas dengan struktur cart lama.
            if (
                ! $this->inCart
                && ! empty($cart['master_item_id'])
                && (int) $cart['master_item_id']
                    === (int) $this->product->id
            ) {
                $this->inCart = true;
            }
        }
    }

    /**
     * Hitung tanggal dan waktu pengembalian berdasarkan durasi.
     */
    public function syncReturnFromDuration(): void
    {
        if (empty($this->pickup_date)) {
            return;
        }

        $time = ! empty($this->pickup_time)
            ? $this->pickup_time
            : '09:00';

        $pickup = Carbon::parse("{$this->pickup_date} {$time}");

        $return = $pickup->copy()->addDays(
            max(1, $this->duration_days)
        );

        $this->return_date = $return->format('Y-m-d');
        $this->return_time = $return->format('H:i');

        $this->resetErrorBag(['return_date', 'return_time']);
    }

    public function updatedPickupDate(): void
    {
        $this->syncReturnFromDuration();
    }

    public function updatedPickupTime(): void
    {
        $this->syncReturnFromDuration();
    }

    public function updatedDurationDays(): void
    {
        if ($this->duration_days < 1) {
            $this->duration_days = 1;
        }

        $this->syncReturnFromDuration();
    }

    public function updatedReturnDate(): void
    {
        $this->validateReturnDateTime();
    }

    public function updatedReturnTime(): void
    {
        $this->validateReturnDateTime();
    }

    public function updatedQuantity(): void
    {
        if ($this->quantity < 1) {
            $this->quantity = 1;
        }
    }

    /**
     * Validasi waktu pengembalian.
     */
    public function validateReturnDateTime(): bool
    {
        if (empty($this->pickup_date) || empty($this->return_date)) {
            return false;
        }

        $pickupTime = ! empty($this->pickup_time)
            ? $this->pickup_time
            : '09:00';

        $returnTime = ! empty($this->return_time)
            ? $this->return_time
            : '09:00';

        $pickup = Carbon::parse("{$this->pickup_date} {$pickupTime}");
        $return = Carbon::parse("{$this->return_date} {$returnTime}");

        if ($return->lt($pickup)) {
            $this->addError(
                'return_date',
                'Waktu pengembalian tidak boleh sebelum waktu pengambilan.'
            );

            return false;
        }

        $maxReturn = $pickup->copy()->addDays(
            max(1, $this->duration_days)
        );

        if ($return->gt($maxReturn)) {
            $this->addError(
                'return_time',
                'Waktu pengembalian tidak boleh melampaui durasi yang dipilih (maksimal '
                . $maxReturn->translatedFormat('d M Y, H:i')
                . ' WIB).'
            );

            return false;
        }

        $this->resetErrorBag(['return_date', 'return_time']);

        return true;
    }

    /**
     * Hitung total hari sewa berdasarkan durasi aktual.
     */
    #[Computed]
    public function totalDays(): int
    {
        if (empty($this->pickup_date) || empty($this->return_date)) {
            return 0;
        }

        $pickupTime = ! empty($this->pickup_time)
            ? $this->pickup_time
            : '09:00';

        $returnTime = ! empty($this->return_time)
            ? $this->return_time
            : '09:00';

        $pickup = Carbon::parse("{$this->pickup_date} {$pickupTime}");
        $return = Carbon::parse("{$this->return_date} {$returnTime}");

        $hours = $pickup->floatDiffInHours($return, false);

        if ($hours < 0) {
            return 0;
        }

        return max(1, (int) ceil($hours / 24));
    }

    /**
     * Hitung estimasi total harga.
     */
    #[Computed]
    public function totalPrice(): int
    {
        return (int) (
            $this->product->daily_rate
            * $this->quantity
            * $this->totalDays
        );
    }

    /**
     * Hitung stok unit yang tersedia.
     */
    #[Computed]
    public function availableStock(): int
    {
        return ItemUnit::withoutGlobalScope('vendor')
            ->where('master_item_id', $this->product->id)
            ->where('status', 'available')
            ->count();
    }

    /**
     * Tanggal yang habis total untuk kalender.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function fullyBookedDates(): array
    {
        try {
            $service = app(AvailabilityService::class);

            return $service->bookedDates(
                $this->product->id,
                Carbon::today(),
                Carbon::today()->addDays(self::CALENDAR_AHEAD_DAYS)
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * True jika SEMUA tanggal di rentang kalender habis (mis. tidak ada unit
     * yang bisa disewa). Lebih jelas ditampilkan sebagai "Stok habis" daripada
     * "61 tanggal stok habis".
     */
    #[Computed]
    public function isOutOfStock(): bool
    {
        return count($this->fullyBookedDates) >= self::CALENDAR_AHEAD_DAYS + 1;
    }

    /**
     * Aturan validasi form.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return [
            'pickup_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'pickup_time' => [
                'required',
                'date_format:H:i',
            ],
            'duration_days' => [
                'required',
                'integer',
                'min:1',
            ],
            'return_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:pickup_date',
            ],
            'return_time' => [
                'required',
                'date_format:H:i',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Pesan validasi Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'pickup_date.required' => 'Tanggal mulai sewa wajib diisi.',
            'pickup_date.after_or_equal' => 'Tanggal mulai sewa tidak boleh sebelum hari ini.',
            'pickup_time.required' => 'Waktu pengambilan barang wajib diisi.',
            'duration_days.required' => 'Durasi sewa wajib dipilih.',
            'duration_days.min' => 'Durasi sewa minimal 1 hari (24 jam).',
            'return_date.required' => 'Tanggal selesai sewa wajib diisi.',
            'return_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai sewa.',
            'return_time.required' => 'Waktu pengembalian barang wajib diisi.',
            'quantity.required' => 'Jumlah unit sewa wajib diisi.',
            'quantity.min' => 'Jumlah unit sewa minimal 1 unit.',
        ];
    }

    /**
     * Masukkan produk ke keranjang sewa.
     */
    public function addToCart(): ?string
    {
        $this->validate();

        if (! $this->validateReturnDateTime()) {
            return null;
        }

        if ($this->totalDays <= 0) {
            $this->addError(
                'return_date',
                'Durasi sewa harus minimal 1 hari.'
            );

            return null;
        }

        // Validasi ketersediaan stok.
        if ($this->quantity > $this->availableStock) {
            $this->addError(
                'quantity',
                "Stok tidak mencukupi. Hanya tersedia {$this->availableStock} unit untuk tanggal yang dipilih."
            );

            return null;
        }

        $cartService = app(CartService::class);

        $cartData = [
            'master_item_id' => $this->product->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image_url,
            'daily_rate' => (int) $this->product->daily_rate,
            'quantity' => $this->quantity,
            'qty' => $this->quantity,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'return_date' => $this->return_date,
            'return_time' => $this->return_time,
            'duration_days' => $this->duration_days,
            'total_days' => $this->totalDays,
            'total_price' => $this->totalPrice,
            'vendor_id' => $this->product->vendor_id,
            'vendor_name' => $this->product->vendor?->name ?? 'Vendor',
            'category_name' => $this->product->category?->name,
        ];

        // Pengecekan vendor dan penambahan item ditangani CartService.
        $result = $cartService->addItem(
            $cartData,
            Auth::user()
        );

        // Tangani kondisi keranjang berasal dari vendor berbeda.
        if (($result['status'] ?? null) === 'diff_vendor') {
            $existingVendor = $result['existing_vendor']
                ?? 'toko sebelumnya';

            $this->dispatch('notify', [
                'type' => 'warning',
                'title' => 'Keranjang Beda Toko',
                'message' => "Keranjangmu masih berisi produk dari {$existingVendor}. Sewa harus dari 1 vendor yang sama. Selesaikan atau kosongkan keranjang terlebih dahulu.",
            ]);

            return null;
        }

        $this->inCart = true;

        $totalCount = $result['total_count'] ?? $this->quantity;

        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Ditambahkan ke Keranjang! 🛒',
            'message' => "{$this->product->name} ({$this->quantity} unit, {$this->totalDays} hari) berhasil masuk keranjang.",
        ]);

        $this->dispatch('cart-updated', count: $totalCount);

        return null;
    }

    /**
     * Alias kompatibilitas untuk proses booking lama.
     */
    public function proceedToBooking(): mixed
    {
        $this->addToCart();

        if ($this->getErrorBag()->isEmpty() && $this->inCart) {
            $this->redirectRoute('checkout.index');
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.customer.product-booking');
    }
}
