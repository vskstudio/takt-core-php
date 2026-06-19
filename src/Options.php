<?php

namespace Vskstudio\Takt;

final class Options
{
    /**
     * @param list<string> $fileExtensions
     * @param list<string> $queryParams
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $endpoint = '/api/event',
        /** First-party origin to serve the tracker + derive the endpoint from ({origin}/api/event); endpoint wins. */
        public readonly ?string $scriptOrigin = null,
        public readonly bool $outbound = false,
        public readonly bool $files = false,
        public readonly bool $excludeLocalhost = true,
        public readonly ?string $nonce = null,
        public readonly Mode $mode = Mode::Inline,
        public readonly bool $notFound = false,
        public readonly bool $tagged = false,
        public readonly array $fileExtensions = [],
        // Advanced options. null = "unset" (tracker default applies); only a
        // non-default value is rendered. scrubUrl is a raw JS expression and is
        // expressible only in Mode::Sdk (SnippetRenderer fails fast otherwise).
        public readonly ?float $sampleRate = null,
        public readonly ?bool $trackQuery = null,
        public readonly array $queryParams = [],
        public readonly ?bool $respectDnt = null,
        public readonly ?bool $enabled = null,
        public readonly ?string $scrubUrl = null,
    ) {
    }

    /** @param array<string,mixed> $a */
    public static function fromArray(array $a): self
    {
        $mode = match ($a['mode'] ?? 'inline') {
            'cdn', Mode::Cdn => Mode::Cdn,
            'asset', Mode::Asset => Mode::Asset,
            'sdk', Mode::Sdk => Mode::Sdk,
            default => Mode::Inline,
        };

        return new self(
            domain: self::str($a['domain'] ?? ''),
            endpoint: self::str($a['endpoint'] ?? '/api/event'),
            scriptOrigin: isset($a['scriptOrigin']) ? self::str($a['scriptOrigin']) : null,
            outbound: (bool) ($a['outbound'] ?? false),
            files: (bool) ($a['files'] ?? false),
            excludeLocalhost: (bool) ($a['excludeLocalhost'] ?? ($a['exclude_localhost'] ?? true)),
            nonce: isset($a['nonce']) ? self::str($a['nonce']) : null,
            mode: $mode,
            notFound: (bool) ($a['notFound'] ?? ($a['not_found'] ?? false)),
            tagged: (bool) ($a['tagged'] ?? false),
            fileExtensions: self::strList($a['fileExtensions'] ?? ($a['file_extensions'] ?? [])),
            sampleRate: self::nullableFloat($a['sampleRate'] ?? ($a['sample_rate'] ?? null)),
            trackQuery: self::nullableBool($a['trackQuery'] ?? ($a['track_query'] ?? null)),
            queryParams: self::strList($a['queryParams'] ?? ($a['query_params'] ?? [])),
            respectDnt: self::nullableBool($a['respectDnt'] ?? ($a['respect_dnt'] ?? null)),
            enabled: self::nullableBool($a['enabled'] ?? null),
            scrubUrl: isset($a['scrubUrl']) || isset($a['scrub_url']) ? self::str($a['scrubUrl'] ?? $a['scrub_url']) : null,
        );
    }

    private static function str(mixed $v): string
    {
        return is_scalar($v) ? (string) $v : '';
    }

    private static function nullableFloat(mixed $v): ?float
    {
        return is_numeric($v) ? (float) $v : null;
    }

    private static function nullableBool(mixed $v): ?bool
    {
        return $v === null ? null : (bool) $v;
    }

    /** @return list<string> */
    private static function strList(mixed $v): array
    {
        if (!is_array($v)) {
            return [];
        }
        $out = [];
        foreach ($v as $item) {
            if (is_scalar($item) && ($s = trim((string) $item)) !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }
}
