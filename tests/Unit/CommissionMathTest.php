<?php

namespace Tests\Unit;

use App\Support\CommissionMath;
use PHPUnit\Framework\TestCase;

class CommissionMathTest extends TestCase
{
    public function test_commission_uses_exact_final_order_total_without_cent_rounding(): void
    {
        $this->assertSame('0.642000', CommissionMath::calculate('3.21', '0.2000'));
        $this->assertSame('0.054000', CommissionMath::calculate('0.54', '0.1000'));
        $this->assertSame('0.081000', CommissionMath::calculate('0.54', '0.1500'));
    }

    public function test_cents_are_converted_without_float_rounding(): void
    {
        $this->assertSame('3.21', CommissionMath::fromCents(321));
        $this->assertSame('0.54', CommissionMath::fromCents(54));
        $this->assertSame('0.00', CommissionMath::fromCents(0));
    }
}
