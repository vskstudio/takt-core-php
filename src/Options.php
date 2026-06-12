<?php

namespace Vskstudio\Takt;

final class Options
{
    public function __construct(
        public readonly string $domain,
        public readonly string $endpoint = '/api/event',
        public readonly bool $outbound = false,
        public readonly bool $files = false,
        public readonly bool $excludeLocalhost = true,
        public readonly ?string $nonce = null,
        public readonly Mode $mode = Mode::Inline,
    ) {
    }

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): self
    {
        $mode = match ($a['mode'] ?? 'inline') {
            'cdn', Mode::Cdn => Mode::Cdn,
            'asset', Mode::Asset => Mode::Asset,
            default => Mode::Inline,
        };

        return new self(
            domain: self::str($a['domain'] ?? ''),
            endpoint: self::str($a['endpoint'] ?? '/api/event'),
            outbound: (bool) ($a['outbound'] ?? false),
            files: (bool) ($a['files'] ?? false),
            excludeLocalhost: (bool) ($a['excludeLocalhost'] ?? true),
            nonce: isset($a['nonce']) ? self::str($a['nonce']) : null,
            mode: $mode,
        );
    }

    private static function str(mixed $v): string
    {
        return is_scalar($v) ? (string) $v : '';
    }
}
