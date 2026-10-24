<?php

namespace Tests\Unit;

use App\Services\MidtransService;
use App\Services\PaymentService;
use PHPUnit\Framework\TestCase;

class MidtransServiceTest extends TestCase
{
    public function test_order_id_prefix_is_tdk(): void
    {
        $this->assertSame('TDK-', MidtransService::ORDER_ID_PREFIX);
    }

    public function test_order_id_generation_matches_specification(): void
    {
        $service = new PaymentService;
        $orderId = $service->generateOrderId('TDK-20261024-0001', 'dp', 1);

        $this->assertSame('TDK-20261024-0001-DP-1', $orderId);
        $this->assertStringStartsWith(MidtransService::ORDER_ID_PREFIX, $orderId);
    }
}
