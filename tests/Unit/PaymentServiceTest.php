<?php

namespace Tests\Unit;

use App\Services\Payment\PaymentService;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    public function test_dp_amount_is_always_fifty_percent_of_the_charge_base(): void
    {
        $service = app(PaymentService::class);

        $this->assertSame(50, $service->dpPercentage());
        $this->assertSame(150000, $service->calculateDpAmount(300000));
        $this->assertSame(175000, $service->calculateDpAmount(300000, 50000));
        $this->assertSame(125000, $service->calculateDpAmount(300000, 50000, 100000));
    }
}
