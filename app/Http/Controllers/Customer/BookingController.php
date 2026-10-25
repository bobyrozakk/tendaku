<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Rental;
use App\Services\MidtransService;
use App\Services\RentalAccessGuard;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends Controller
{
    private const PER_PAGE = 10;

    private const MIDTRANS_CHANNEL = 'midtrans';

    private const RENTAL_PENDING = 'pending';

    private const PAYMENT_PENDING = 'pending';

    private const PAYMENT_PAID = 'paid';

    private const PAYABLE_TYPES = ['full', 'dp'];

    public function __construct(private readonly RentalAccessGuard $accessGuard) {}

    /**
     * Riwayat sewa customer (harus login).
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');

        $rentals = Rental::query()
            ->where('user_id', Auth::id())
            ->when(filled($status), fn ($query) => $query->where('status', $status))
            ->with(['vendor:id,name,phone,city', 'details.masterItem'])
            ->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('customer.booking.index', compact('rentals', 'status'));
    }

    /**
     * Halaman status booking & rental.
     */
    public function status(Request $request, string $code, MidtransService $midtrans): View
    {
        $rental = $this->findAuthorizedRental($request, $code);

        $rental->loadMissing([
            'vendor',
            'customer',
            'details.masterItem',
            'details.itemUnit',
            'payments' => fn ($query) => $query->latest('id'),
        ]);

        $latestPayment = $rental->payments->firstWhere('channel', self::MIDTRANS_CHANNEL);

        // Jika payment masih pending dan belum memiliki nomor VA dari Midtrans, generate langsung via Core API
        if ($latestPayment && $latestPayment->status === 'pending' && empty($latestPayment->va_details)) {
            try {
                $midtrans->chargeBankTransfer($latestPayment, 'bca');
                $latestPayment->refresh();
                $rental->refresh();
            } catch (\Throwable $e) {
                // Abaikan jika ada kegagalan koneksi sesaat
            }
        }

        return view('customer.booking.status', compact('rental', 'latestPayment'));
    }

    /**
     * Polling: sinkronkan status pembayaran dengan Midtrans.
     */
    public function syncPayment(Request $request, string $code, MidtransService $midtrans): JsonResponse
    {
        $rental = $this->findAuthorizedRental($request, $code);

        $payment = $this->midtransPayments($rental)
            ->whereNotNull('order_id')
            ->latest('id')
            ->first();

        if (! $payment) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ditemukan tagihan pembayaran online aktif untuk rental ini.',
            ], 404);
        }

        $payment = $midtrans->syncStatus($payment);
        $rental->refresh();

        $isPaid = $payment->status === self::PAYMENT_PAID;

        return response()->json([
            'status' => 'success',
            'order_id' => $payment->order_id,
            'payment_status' => $payment->status,
            'rental_status' => $rental->status,
            'is_paid' => $isPaid,
            'paid_at' => $payment->paid_at?->translatedFormat('d M Y, H:i T'),
            'message' => $isPaid
                ? 'Pembayaran berhasil diverifikasi! Pesanan Anda telah dikonfirmasi.'
                : 'Status pembayaran saat ini: '.$payment->status,
        ]);
    }

    /**
     * Ambil (atau buat) Snap token untuk rental yang masih menunggu pembayaran.
     */
        public function pay(Request $request, string $code, MidtransService $midtrans): JsonResponse
    {
        $rental = $this->findAuthorizedRental($request, $code);

        abort_unless(
            $rental->status === self::RENTAL_PENDING,
            422,
            'Rental ini tidak lagi menunggu pembayaran.',
        );

        $payment = $this->midtransPayments($rental)
            ->where('status', self::PAYMENT_PENDING)
            ->whereIn('payment_type', self::PAYABLE_TYPES)
            ->latest('id')
            ->firstOrFail();

        $selectedBank = $request->input('bank', 'bca');

        // Panggil Midtrans Core API untuk generate nomor VA langsung dari Sandbox
        $vaInfo = $midtrans->chargeBankTransfer($payment, $selectedBank);

        return response()->json([
            'status' => 'success',
            'order_id' => $payment->order_id,
            'va_info' => $vaInfo,
            'amount' => (int) $payment->amount,
        ]);
    }

    /**
     * Tanda terima KTP digital.
     */
    public function receipt(Request $request, string $code): View
    {
        $rental = $this->findAuthorizedRental($request, $code);

        $rental->loadMissing(['vendor', 'customer', 'pickedUpBy']);

        return view('customer.booking.receipt', compact('rental'));
    }

    /**
     * Tampilkan foto jaminan KTP (hanya untuk yang berhak).
     */
    public function collateralPhoto(Request $request, string $code): Response
    {
        $rental = $this->findAuthorizedRental($request, $code);
        $photoPath = $rental->ktp_collateral_photo_url;

        abort_if(empty($photoPath), 404, 'Foto jaminan KTP belum tersedia.');

        foreach ($this->collateralDisks() as $disk) {
            if ($disk->exists($photoPath)) {
                return $disk->response($photoPath);
            }
        }

        // Foto disimpan sebagai URL eksternal
        abort_unless(filter_var($photoPath, FILTER_VALIDATE_URL), 404, 'Foto jaminan KTP tidak ditemukan.');

        return redirect()->away($photoPath);
    }

    /**
     * Disk tempat foto jaminan mungkin disimpan.
     *
     * @return Filesystem[]
     */
    private function collateralDisks(): array
    {
        return [Storage::disk('public'), Storage::disk()];
    }

    /**
     * Relasi pembayaran Midtrans milik rental.
     */
    private function midtransPayments(Rental $rental): HasMany
    {
        return $rental->payments()->where('channel', self::MIDTRANS_CHANNEL);
    }

    /**
     * Cari rental dari kode booking, lalu pastikan request berhak mengaksesnya.
     */
    private function findAuthorizedRental(Request $request, string $code): Rental
    {
        $rental = Rental::query()
            ->where('booking_code', strtoupper($code))
            ->firstOrFail();

        $this->accessGuard->authorize($request, $rental);

        return $rental;
    }
}
