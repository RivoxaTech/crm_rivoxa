<?php

namespace Tests\Unit;

use App\Services\ProfitSplitter;
use PHPUnit\Framework\TestCase;

class ProfitSplitterTest extends TestCase
{
    private array $partners = [1 => 'Ali', 2 => 'Sara'];

    public function test_half_mode_gives_partners_25_each_and_company_50(): void
    {
        $rows = ProfitSplitter::split(10000, ProfitSplitter::HALF, $this->partners);

        $this->assertSame([2500.0, 2500.0, 5000.0], array_column($rows, 'amount'));
        $this->assertSame([25.0, 25.0, 50.0], array_column($rows, 'percent'));
        $this->assertSame('company', $rows[2]['party']);
    }

    public function test_equal_mode_splits_three_ways_and_company_takes_the_rounding(): void
    {
        $rows = ProfitSplitter::split(10000, ProfitSplitter::EQUAL, $this->partners);

        $this->assertSame([3333.33, 3333.33, 3333.34], array_column($rows, 'amount'));
        $this->assertEqualsWithDelta(10000, array_sum(array_column($rows, 'amount')), 0.001);
    }

    public function test_a_loss_is_carried_by_the_company_only(): void
    {
        $rows = ProfitSplitter::split(-500, ProfitSplitter::HALF, $this->partners);

        $this->assertSame([0.0, 0.0, -500.0], array_column($rows, 'amount'));
    }
}
