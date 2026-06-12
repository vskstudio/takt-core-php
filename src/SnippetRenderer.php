<?php
namespace Vskstudio\Takt;

final class SnippetRenderer
{
    private const CDN_BASE = 'https://cdn.jsdelivr.net/npm/@vskstudio/takt-core/dist/takt.js';
    private const ASSET_PATH = '/takt/takt.js';

    public function __construct(private readonly Options $options) {}

    public function render(): string
    {
        return match ($this->options->mode) {
            Mode::Cdn => $this->renderLoader(self::CDN_BASE),
            Mode::Asset => $this->renderLoader(self::ASSET_PATH),
            Mode::Inline => $this->renderInline(),
        };
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
        $bundle = file_get_contents(__DIR__ . '/../resources/takt.js');

        return sprintf(
            "<script%s%s>%s</script>",
            $this->dataAttrs(),
            $this->nonceAttr(),
            $bundle,
        );
    }

    private function dataAttrs(): string
    {
        $o = $this->options;
        $attrs = sprintf(' data-domain="%s" data-endpoint="%s"',
            htmlspecialchars($o->domain, ENT_QUOTES),
            htmlspecialchars($o->endpoint, ENT_QUOTES),
        );
        if ($o->outbound) { $attrs .= ' data-outbound'; }
        if ($o->files) { $attrs .= ' data-files'; }
        if (!$o->excludeLocalhost) { $attrs .= ' data-exclude-localhost="false"'; }

        return $attrs;
    }

    private function nonceAttr(): string
    {
        return $this->options->nonce !== null
            ? sprintf(' nonce="%s"', htmlspecialchars($this->options->nonce, ENT_QUOTES))
            : '';
    }
}
