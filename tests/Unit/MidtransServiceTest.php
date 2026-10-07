<?php

namespace Tests\Unit;

use App\Services\MidtransService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MidtransServiceTest extends TestCase
{
    public function test_order_id_includes_tenda_prefix_and_payment_id(): void
    {
        $orderId = MidtransService::generateOrderId(1);

        $this->assertSame('TENDA-INV-001', $orderId);
    }

    public function test_order_id_rejects_a_non_positive_payment_id(): void
    {
        $this->expectException(InvalidArgumentException::class);

        MidtransService::generateOrderId(0);
    }
}
