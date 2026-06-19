<?php

namespace Vskstudio\Takt;

final class SnippetRenderer
{
    private const CDN_BASE = 'https://cdn.jsdelivr.net/npm/@vskstudio/takt-core@0.4.2/dist/takt.auto.js';
    private const ASSET_PATH = '/takt/takt.auto.js';

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
        $path = __DIR__ . '/../resources/takt.auto.js';
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
        $attrs = sprintf(' data-domain="%s"', htmlspecialchars($o->domain, ENT_QUOTES));
        // On n'émet data-endpoint que s'il est explicitement personnalisé : laissé
        // au défaut, le tracker dérive lui-même {scriptOrigin}/api/event (collecte
        // first-party anti-adblock) et retombe sur /api/event sans scriptOrigin.
        if ($o->endpoint !== '/api/event') {
            $attrs .= sprintf(' data-endpoint="%s"', htmlspecialchars($o->endpoint, ENT_QUOTES));
        }
        if ($o->scriptOrigin !== null && $o->scriptOrigin !== '') {
            $attrs .= sprintf(' data-script-origin="%s"', htmlspecialchars($o->scriptOrigin, ENT_QUOTES));
        }
        // Autocapture is opt-in and driven by a single data-auto token list read
        // by takt.auto.js : outbound clicks, downloads, HTML-tagged events, 404.
        $auto = [];
        if ($o->outbound) {
            $auto[] = 'outbound';
        }
        if ($o->files) {
            $auto[] = 'downloads';
        }
        if ($o->tagged) {
            $auto[] = 'tagged';
        }
        if ($o->notFound) {
            $auto[] = '404';
        }
        if ($auto !== []) {
            $attrs .= sprintf(' data-auto="%s"', implode(',', $auto));
        }
        if ($o->fileExtensions !== []) {
            $attrs .= sprintf(' data-downloads-ext="%s"', htmlspecialchars(implode(',', $o->fileExtensions), ENT_QUOTES));
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
