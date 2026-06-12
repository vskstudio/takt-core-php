<?php
namespace Vskstudio\Takt;

final readonly class Options
{
    public function __construct(
        public string $domain,
        public string $endpoint = '/api/event',
        public bool $outbound = false,
        public bool $files = false,
        public bool $excludeLocalhost = true,
        public ?string $nonce = null,
        public Mode $mode = Mode::Inline,
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
            domain: (string) ($a['domain'] ?? ''),
            endpoint: (string) ($a['endpoint'] ?? '/api/event'),
            outbound: (bool) ($a['outbound'] ?? false),
            files: (bool) ($a['files'] ?? false),
            excludeLocalhost: (bool) ($a['excludeLocalhost'] ?? true),
            nonce: isset($a['nonce']) ? (string) $a['nonce'] : null,
            mode: $mode,
        );
    }
}
