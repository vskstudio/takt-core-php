<?php

namespace Vskstudio\Takt;

final class Revenue
{
    public function __construct(
        public readonly string $amount,
        public readonly string $currency,
    ) {
        if (!preg_match('/^-?\d+(?:\.\d+)?$/', $amount)) {
            throw new \InvalidArgumentException("amount must be a plain decimal string (e.g. \"29.00\"), got: {$amount}");
        }
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new \InvalidArgumentException("currency must be a 3-letter uppercase code, got: {$currency}");
        }
    }

    /** @return array{a: string, c: string} */
    public function toArray(): array
    {
        return ['a' => $this->amount, 'c' => $this->currency];
    }
}
