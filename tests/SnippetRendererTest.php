<?php
namespace Vskstudio\Takt\Tests;

use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\Mode;
use Vskstudio\Takt\Options;
use Vskstudio\Takt\SnippetRenderer;

final class SnippetRendererTest extends TestCase
{
    public function test_cdn_mode_emits_loader_with_data_attrs(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', outbound: true, mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('cdn.jsdelivr.net/npm/@vskstudio/takt-core', $html);
        $this->assertStringContainsString('data-domain="example.com"', $html);
        $this->assertStringContainsString('data-outbound', $html);
        $this->assertStringNotContainsString('data-files', $html);
    }

    public function test_inline_mode_embeds_bundle_with_data_attrs(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', files: true, mode: Mode::Inline)))->render();
        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('data-domain="example.com"', $html);
        $this->assertStringContainsString('data-files', $html);
        $this->assertStringContainsString('var takt=', $html);
        $this->assertStringNotContainsString(' src=', $html);
    }

    public function test_nonce_is_applied(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', nonce: 'abc123', mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('nonce="abc123"', $html);
    }

    public function test_domain_is_escaped(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'a"><x', mode: Mode::Cdn)))->render();
        $this->assertStringNotContainsString('a"><x', $html);
    }
}
