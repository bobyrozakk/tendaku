<?php

namespace Tests\Unit;

use App\Services\Payment\MidtransService;
use App\Services\Payment\PaymentService;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    public function test_order_id_prefix_is_tdk(): void
    {
        $this->assertSame('TDK', MidtransService::ORDER_ID_PREFIX);
    }

    public function test_order_id_omits_booking_sequence_and_appends_unpadded_payment_sequence(): void
    {
        $service = app(PaymentService::class);

        $this->assertSame(
            'TDK20261024020311',
            $service->generateOrderId('TDK202610240203001', 'bca_va', 1),
        );
        $this->assertSame(
            'TDK20261024020321',
            $service->generateOrderId('TDK202610240203001', 'bni_va', 1),
        );
        $this->assertSame(
            'TDK20261024020361',
            $service->generateOrderId('TDK202610240203001', 'qris', 1),
        );
        $this->assertSame(
            'TDK20261024020301',
            $service->generateOrderId('TDK202610240203001', null, 1),
        );
        $this->assertSame(
            'TDK202610240203110',
            $service->generateOrderId('TDK202610240203001', 'bca_va', 10),
        );
        $this->assertSame(
            'TDK20261024020311',
            $service->generateOrderId('TDK2026102402031000', 'bca_va', 1),
        );
    }

    public function test_order_sequence_stays_the_same_when_the_bank_changes(): void
    {
        $service = app(PaymentService::class);
        $bookingCode = 'TDK202610240203001';

        $this->assertSame(
            1,
            $service->orderSequence($bookingCode, 'TDK20261024020321'),
        );
        $this->assertSame(
            1,
            $service->orderSequence($bookingCode, 'TDK20261024020300121'),
        );
        $this->assertSame(
            10,
            $service->orderSequence($bookingCode, 'TDK202610240203110'),
        );
        $this->assertSame(
            3,
            $service->orderSequence($bookingCode, 'TDK2026102420203001-3'),
        );
    }
}
