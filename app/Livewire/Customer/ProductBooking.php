<?php

namespace App\Livewire\Customer;

use App\Models\MasterItem;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductBooking extends Component
{
    /** Model produk yang di-mount dari halaman detail */
    public MasterItem $product;

    /** Tanggal pengambilan barang (default: besok) */
    public string $pickup_date = '';

    /** Tanggal pengembalian barang (default: lusa) */
    public string $return_date = '';

    /** Kuantitas unit yang disewa (default: 1) */
    public int $quantity = 1;

    /**
     * Inisialisasi properti saat komponen dimuat.
     */
    public function mount(MasterItem $product): void
    {
        $this->product = $product;
        $this->quantity = 1;

        // Nilai default: besok dan lusa
        $this->pickup_date = Carbon::tomorrow()->format('Y-m-d');
        $this->return_date = Carbon::today()->addDays(2)->format('Y-m-d');
    }

    /**
     * Ketika pickup_date berubah, sesuaikan return_date bila perlu.
     */
    public function updatedPickupDate(): void
    {
        if ($this->return_date && $this->return_date < $this->pickup_date) {
            $this->return_date = $this->pickup_date;
        }
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
     * Computed: hitung total hari sewa inklusif (pickup s/d return).
     */
    #[Computed]
    public function totalDays(): int
    {
        if (empty($this->pickup_date) || empty($this->return_date)) {
            return 0;
        }

        $pickup = Carbon::parse($this->pickup_date)->startOfDay();
        $return = Carbon::parse($this->return_date)->startOfDay();

        if ($return->lt($pickup)) {
            return 0;
        }

        return (int) $pickup->diffInDays($return) + 1;
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
            'return_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:pickup_date',
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
            'return_date.required' => 'Tanggal selesai sewa wajib diisi.',
            'return_date.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai sewa.',
            'quantity.required' => 'Jumlah unit sewa wajib diisi.',
            'quantity.min' => 'Jumlah unit sewa minimal 1 unit.',
        ];
    }

    /**
     * Kirim data pemesanan ke checkout dengan master_item_id, qty, dan tanggal.
     */
    public function proceedToBooking(): mixed
    {
        $this->validate();

        if ($this->totalDays <= 0) {
            $this->addError('return_date', 'Durasi sewa harus minimal 1 hari.');

            return null;
        }

        // Validasi ketersediaan stok fisik di server
        if ($this->quantity > $this->availableStock) {
            $this->addError('quantity', "Stok tidak mencukupi. Hanya tersedia {$this->availableStock} unit untuk tanggal yang dipilih.");

            return null;
        }

        Session::put('booking_cart', [
            'master_item_id' => $this->product->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image_url,
            'daily_rate' => (int) $this->product->daily_rate,
            'quantity' => $this->quantity,
            'qty' => $this->quantity,
            'pickup_date' => $this->pickup_date,
            'return_date' => $this->return_date,
            'total_days' => $this->totalDays,
            'total_price' => $this->totalPrice,
            'vendor_id' => $this->product->vendor_id,
            'category_name' => $this->product->category?->name,
        ]);

        return redirect()->route('checkout.index');
    }

    public function render(): View
    {
        return view('livewire.customer.product-booking');
    }
}
