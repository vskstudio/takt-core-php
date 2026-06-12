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
}
