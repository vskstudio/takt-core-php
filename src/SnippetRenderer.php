<?php

namespace Vskstudio\Takt;

final class SnippetRenderer
{
    private const CDN_BASE = 'https://cdn.jsdelivr.net/npm/@vskstudio/takt-core/dist/takt.js';
    private const ASSET_PATH = '/takt/takt.js';

    public function __construct(private readonly Options $options)
    {
    }

    public function render(): string
    {
        return match ($this->options->mode) {
            Mode::Cdn => $this->renderLoader(self::CDN_BASE),
            Mode::Asset => $this->renderLoader($this->assetSrc()),
            Mode::Inline => $this->renderInline(),
        };
    }

    private function assetSrc(): string
    {
        $origin = $this->options->scriptOrigin;

        return $origin !== null && $origin !== ''
            ? rtrim($origin, '/') . self::ASSET_PATH
            : self::ASSET_PATH;
    }

    private function renderLoader(string $src): string
    {
        return sprintf(
            '<script defer src="%s"%s%s></script>',
            htmlspecialchars($src, ENT_QUOTES),
            $this->dataAttrs(),
            $this->nonceAttr(),
        );
    }

    private function renderInline(): string
    {
        $path = __DIR__ . '/../resources/takt.js';
        $bundle = file_get_contents($path);
        if ($bundle === false) {
            throw new \RuntimeException("Takt: unable to read inline bundle at {$path}");
        }

        return sprintf(
            "<script%s%s>%s</script>",
            $this->dataAttrs(),
            $this->nonceAttr(),
            self::neutralizeScriptClose($bundle),
        );
    }

    /**
     * Neutralize any "</script" inside an inline bundle so it cannot break out of
     * the surrounding <script> tag. Matched case-insensitively because the HTML
     * parser closes the block on "</script" regardless of case.
     */
    public static function neutralizeScriptClose(string $js): string
    {
        return preg_replace('#</(script)#i', '<\\\\/$1', $js) ?? $js;
    }

    private function dataAttrs(): string
    {
        $o = $this->options;
        $attrs = sprintf(
            ' data-domain="%s" data-endpoint="%s"',
            htmlspecialchars($o->domain, ENT_QUOTES),
            htmlspecialchars($o->endpoint, ENT_QUOTES),
        );
        if ($o->scriptOrigin !== null && $o->scriptOrigin !== '') {
            $attrs .= sprintf(' data-script-origin="%s"', htmlspecialchars($o->scriptOrigin, ENT_QUOTES));
        }
        if ($o->outbound) {
            $attrs .= ' data-outbound';
        }
        if ($o->files) {
            $attrs .= ' data-files';
        }
        if (!$o->excludeLocalhost) {
            $attrs .= ' data-exclude-localhost="false"';
        }

        return $attrs;
    }

    private function nonceAttr(): string
    {
        return $this->options->nonce !== null
            ? sprintf(' nonce="%s"', htmlspecialchars($this->options->nonce, ENT_QUOTES))
            : '';
    }
}
