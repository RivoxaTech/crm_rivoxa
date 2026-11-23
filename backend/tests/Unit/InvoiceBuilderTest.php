<?php

namespace Tests\Unit;

use App\Services\InvoiceBuilder;
use App\Support\Money;
use PHPUnit\Framework\TestCase;

class InvoiceBuilderTest extends TestCase
{
    private function items(?float $imageTotal = null): array
    {
        return [
            ['name' => 'Website Development', 'unit_price' => 35000, 'amount' => 35000],
            ['name' => 'Domain Registration', 'unit_price' => 15700, 'amount' => 15700, 'rate_text' => '15,700 / 3 yrs'],
            ['name' => 'Server & Hosting', 'unit_price' => 10000, 'amount' => 10000, 'rate_text' => '10,000 / yr'],
            ['name' => 'Image Resizing', 'short_label' => 'Image / logo changes', 'billing' => 'per_unit', 'unit' => 'image', 'unit_price' => 40, 'amount' => $imageTotal],
        ];
    }

    public function test_pending_items_stay_out_of_the_subtotal(): void
    {
        $snap = InvoiceBuilder::build($this->items(), [['amount' => 5000, 'method' => 'bank', 'paid_at' => '2026-09-26']]);

        $this->assertSame(60700.0, $snap['subtotal']);
        $this->assertSame(55700.0, $snap['balance']);
        $this->assertTrue($snap['has_pending']);
        $this->assertSame('On delivery', $snap['lines'][3]['amount_text']);
        $this->assertSame('40 / image', $snap['lines'][3]['rate']);
        $this->assertSame('+ image / logo changes', $snap['pending_sub']);
        $this->assertSame('Advance paid', $snap['payments'][0]['label']);
    }

    public function test_final_invoice_totals_and_payment_record(): void
    {
        $items = $this->items(12400);
        $items[] = ['name' => 'All Access', 'unit_price' => 13600, 'amount' => 13600];
        $payments = [
            ['amount' => 5000, 'method' => 'bank', 'paid_at' => '2026-09-26'],
            ['amount' => 25700, 'method' => 'bank', 'paid_at' => '2026-10-03'],
            ['amount' => 25000, 'method' => 'cash', 'paid_at' => '2026-11-19'],
        ];
        $snap = InvoiceBuilder::build($items, $payments);

        $this->assertSame(86700.0, $snap['subtotal']);
        $this->assertSame(55700.0, $snap['paid']);
        $this->assertSame(31000.0, $snap['balance']);
        $this->assertFalse($snap['has_pending']);
        $this->assertSame(
            'Rs 5,000 and Rs 25,700 paid via online bank transfer; bank receipts / screenshots are the source of truth. Rs 25,000 paid in cash on 19 Nov 2026.',
            $snap['payment_record'],
        );
    }

    public function test_free_items_show_free_and_cost_nothing(): void
    {
        $snap = InvoiceBuilder::build([['name' => 'SEO setup', 'unit_price' => 5000, 'is_free' => true], ['name' => 'Site', 'unit_price' => 1000, 'amount' => 1000]], []);

        $this->assertSame('Free', $snap['lines'][0]['amount_text']);
        $this->assertSame(1000.0, $snap['subtotal']);
        $this->assertFalse($snap['has_pending']);
    }

    public function test_forward_balance_becomes_the_first_line(): void
    {
        $snap = InvoiceBuilder::build([['name' => 'Ads', 'unit_price' => 20000, 'amount' => 20000]], [], ['forward' => 5000]);

        $this->assertSame('Previous balance', $snap['lines'][0]['name']);
        $this->assertSame(25000.0, $snap['balance']);
    }

    public function test_money_helpers(): void
    {
        $this->assertSame('35,000', Money::fmt(35000));
        $this->assertSame('33,333.33', Money::fmt(33333.33));
        $this->assertSame('Rs 60,700', Money::rs(60700));
        $this->assertSame('923001234567', Money::phone('0300-1234567'));
        $this->assertSame('923001234567', Money::phone('+92 300 1234567'));
        $this->assertNull(Money::phone(''));
    }
}
