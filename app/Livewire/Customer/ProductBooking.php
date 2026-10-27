<?php

namespace App\Livewire\Customer;

use App\Models\MasterItem;
use App\Services\AvailabilityService;
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductBooking extends Component
{
    /** Model produk yang di-mount dari halaman detail */
    public MasterItem $product;

    /** Tanggal pengambilan barang (default: besok) */
    public string $pickup_date = '';

    /** Jam pengambilan barang (format HH:mm, default: 09:00) */
    public string $pickup_time = '09:00';

    /** Durasi sewa yang dipilih (hari, default: 1 hari = 24 jam penuh) */
    public int $duration_days = 1;

    /** Tanggal pengembalian barang */
    public string $return_date = '';

    /** Jam pengembalian barang (format HH:mm) */
    public string $return_time = '09:00';

    /** Kuantitas unit yang disewa (default: 1) */
    public int $quantity = 1;

    /**
     * Inisialisasi properti saat komponen dimuat.
     */
    public function mount(MasterItem $product): void
    {
        $this->product = $product;
        $this->quantity = 1;
        $this->duration_days = 1;

        // Nilai default: pengambilan besok pukul 09:00
        $this->pickup_date = Carbon::tomorrow()->format('Y-m-d');
        $this->pickup_time = '09:00';

        // Sinkronisasi batas pengembalian (24 jam per hari durasi)
        $this->syncReturnFromDuration();
    }

    /**
     * Hitung otomatis tanggal & waktu pengembalian berdasarkan durasi sewa (24 jam per hari).
     */
    public function syncReturnFromDuration(): void
    {
        if (empty($this->pickup_date)) {
            return;
        }

        $time = ! empty($this->pickup_time) ? $this->pickup_time : '09:00';
        $pickup = Carbon::parse("{$this->pickup_date} {$time}");
        $return = $pickup->copy()->addDays(max(1, $this->duration_days));

        $this->return_date = $return->format('Y-m-d');
        $this->return_time = $return->format('H:i');

        $this->resetErrorBag(['return_date', 'return_time']);
    }

    /**
     * Ketika pickup_date berubah, sesuaikan return_date sesuai durasi.
     */
    public function updatedPickupDate(): void
    {
        $this->syncReturnFromDuration();
    }

    /**
     * Ketika pickup_time berubah, sesuaikan return_time sesuai durasi.
     */
    public function updatedPickupTime(): void
    {
        $this->syncReturnFromDuration();
    }

    /**
     * Ketika durasi hari diubah, perbarui tanggal pengembalian.
     */
    public function updatedDurationDays(): void
    {
        if ($this->duration_days < 1) {
            $this->duration_days = 1;
        }
        $this->syncReturnFromDuration();
    }

    /**
     * Ketika tanggal pengembalian diubah manual oleh customer.
     */
    public function updatedReturnDate(): void
    {
        $this->validateReturnDateTime();
    }

    /**
     * Ketika waktu pengembalian diubah manual oleh customer.
     */
    public function updatedReturnTime(): void
    {
        $this->validateReturnDateTime();
    }

    /**
     * Pastikan quantity minimal 1.
     */
    public function updatedQuantity(): void
    {
        if ($this->quantity < 1) {
            $this->quantity = 1;
        }
    }

    /**
     * Validasi waktu pengembalian terhadap waktu pengambilan dan durasi sewa.
     */
    public function validateReturnDateTime(): bool
    {
        if (empty($this->pickup_date) || empty($this->return_date)) {
            return false;
        }

        $pickupTime = ! empty($this->pickup_time) ? $this->pickup_time : '09:00';
        $returnTime = ! empty($this->return_time) ? $this->return_time : '09:00';

        $pickup = Carbon::parse("{$this->pickup_date} {$pickupTime}");
        $return = Carbon::parse("{$this->return_date} {$returnTime}");

        if ($return->lt($pickup)) {
            $this->addError('return_date', 'Waktu pengembalian tidak boleh sebelum waktu pengambilan.');

            return false;
        }

        $maxReturn = $pickup->copy()->addDays(max(1, $this->duration_days));
        if ($return->gt($maxReturn)) {
            $this->addError('return_time', 'Waktu pengembalian tidak boleh melampaui durasi yang dipilih (maksimal '.$maxReturn->translatedFormat('d M Y, H:i').' WIB).');

            return false;
        }

        $this->resetErrorBag(['return_date', 'return_time']);

        return true;
    }

    /**
     * Computed: hitung total hari sewa berdasarkan durasi jam aktual (24 jam = 1 hari).
     */
    #[Computed]
    public function totalDays(): int
    {
        if (empty($this->pickup_date) || empty($this->return_date)) {
            return 0;
        }

        $pickupTime = ! empty($this->pickup_time) ? $this->pickup_time : '09:00';
        $returnTime = ! empty($this->return_time) ? $this->return_time : '09:00';

        $pickup = Carbon::parse("{$this->pickup_date} {$pickupTime}");
        $return = Carbon::parse("{$this->return_date} {$returnTime}");

        $hours = $pickup->floatDiffInHours($return, false);
        if ($hours < 0) {
            return 0;
        }

        // Durasi <= 24 jam dihitung 1 hari penuh; lebih dari 24 jam dibulatkan ke atas per 24 jam.
        return max(1, (int) ceil($hours / 24));
    }

    /**
     * Computed: hitung estimasi total harga sewa real-time (daily_rate × quantity × total_days).
     */
    #[Computed]
    public function totalPrice(): int
    {
        return (int) ($this->product->daily_rate * $this->quantity * $this->totalDays);
    }

    /**
     * Computed: cek sisa stok unit yang tersedia pada rentang tanggal sewa via AvailabilityService.
     */
    #[Computed]
    public function availableStock(): int
    {
        if ($this->totalDays <= 0) {
            return 0;
        }

        try {
            $service = app(AvailabilityService::class);

            return $service->checkAvailability(
                $this->product->id,
                $this->pickup_date,
                $this->return_date
            );
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Computed: tanggal yang habis total (stok = 0) untuk kalender picker.
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
                Carbon::today()->addDays(60)
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Validasi form sewa.
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
     * Pesan validasi dalam Bahasa Indonesia.
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
     * Validasi vendor agar 1 keranjang hanya dari 1 toko.
     * Setelah berhasil, tampilkan toast dan tidak redirect ke checkout.
     */
    public function addToCart(): ?string
    {
        $this->validate();

        if (! $this->validateReturnDateTime()) {
            return null;
        }

        if ($this->totalDays <= 0) {
            $this->addError('return_date', 'Durasi sewa harus minimal 1 hari.');

            return null;
        }

        // Validasi ketersediaan stok fisik di server
        if ($this->quantity > $this->availableStock) {
            $this->addError('quantity', "Stok tidak mencukupi. Hanya tersedia {$this->availableStock} unit untuk tanggal yang dipilih.");

            return null;
        }

        $cartService = app(CartService::class);
        $existingCart = $cartService->get(Auth::user());

        // Validasi: barang di keranjang harus dari vendor yang sama
        if (
            is_array($existingCart)
            && ! empty($existingCart['master_item_id'])
            && isset($existingCart['vendor_id'])
            && (int) $existingCart['vendor_id'] !== (int) $this->product->vendor_id
        ) {
            $existingVendor = $existingCart['vendor_name'] ?? 'toko sebelumnya';
            $this->dispatch('notify', [
                'type' => 'warning',
                'title' => 'Keranjang Beda Toko',
                'message' => "Keranjangmu masih berisi produk dari {$existingVendor}. Selesaikan atau kosongkan keranjang dahulu sebelum menambah dari toko berbeda.",
            ]);

            return null;
        }

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

        $cartService->set($cartData, Auth::user());

        // Emit toast sukses dan update badge keranjang
        $this->dispatch('notify', [
            'type' => 'success',
            'title' => 'Ditambahkan ke Keranjang! 🛒',
            'message' => "{$this->product->name} ({$this->quantity} unit, {$this->totalDays} hari) berhasil masuk keranjang.",
        ]);

        $this->dispatch('cart-updated');

        return null;
    }

    /**
     * Backward-compatible alias yang langsung redirect ke checkout.
     * Dipertahankan agar unit test yang memanggil proceedToBooking tidak rusak.
     *
     * @internal
     */
    public function proceedToBooking(): mixed
    {
        $this->addToCart();

        if ($this->getErrorBag()->isEmpty()) {
            return redirect()->route('checkout.index');
        }

        return null;
    }

    public function render(): View
    {
        return view('livewire.customer.product-booking');
    }
}
