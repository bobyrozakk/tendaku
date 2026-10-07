<?php

namespace App\Livewire\Customer;

use App\Models\MasterItem;
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

    /**
     * Inisialisasi properti saat komponen dimuat.
     * Menerima model MasterItem dari halaman detail produk.
     */
    public function mount(MasterItem $product): void
    {
        $this->product = $product;

        // Nilai default: besok dan lusa
        $this->pickup_date = Carbon::tomorrow()->format('Y-m-d');
        $this->return_date = Carbon::today()->addDays(2)->format('Y-m-d');
    }

    /**
     * Reset pagination saat tanggal pickup berubah:
     * pastikan return_date selalu >= pickup_date.
     */
    public function updatedPickupDate(): void
    {
        if ($this->return_date && $this->return_date < $this->pickup_date) {
            $this->return_date = $this->pickup_date;
        }
    }

    /**
     * Computed: hitung total hari sewa.
     * Inklusive hari pickup dan hari return (minimum 1 hari).
     * Mengembalikan 0 jika tanggal tidak valid.
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

        // +1 karena hitungan inklusif (hari pickup dihitung juga)
        return $pickup->diffInDays($return) + 1;
    }

    /**
     * Computed: hitung total harga sewa (daily_rate × total_days).
     */
    #[Computed]
    public function totalPrice(): int
    {
        return (int) ($this->product->daily_rate * $this->totalDays);
    }

    /**
     * Validasi form tanggal sewa.
     *
     * @return array<string, string>
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
        ];
    }

    /**
     * Simpan data booking ke session (booking_cart) dan redirect ke checkout.
     */
    public function proceedToBooking(): mixed
    {
        $this->validate();

        if ($this->totalDays <= 0) {
            $this->addError('return_date', 'Durasi sewa harus minimal 1 hari.');

            return redirect()->back();
        }

        Session::put('booking_cart', [
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'product_image' => $this->product->image_url,
            'daily_rate' => (int) $this->product->daily_rate,
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
        return view('livewire.product-booking');
    }
}
