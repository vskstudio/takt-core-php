<?php

namespace Vskstudio\Takt\Tests;

use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\Revenue;

final class RevenueTest extends TestCase
{
    public function test_serializes_to_wire_keys(): void
    {
        $r = new Revenue('29.00', 'EUR');
        $this->assertSame(['a' => '29.00', 'c' => 'EUR'], $r->toArray());
    }

    public function test_rejects_bad_currency(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Revenue('29.00', 'euro');
    }

    /** @return list<array{string}> */
    public static function badAmounts(): array
    {
        return [['29,00'], ['€29'], ['12.3.4'], [''], ['1e3'], ['NaN']];
    }

    /** @dataProvider badAmounts */
    public function test_rejects_non_numeric_amount(string $amount): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Revenue($amount, 'EUR');
    }

    public function test_accepts_plain_decimal_amounts(): void
    {
        $this->assertSame('0', (new Revenue('0', 'USD'))->amount);
        $this->assertSame('1234', (new Revenue('1234', 'USD'))->amount);
    }
}
