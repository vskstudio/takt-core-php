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

    public function test_asset_mode_emits_self_hosted_loader(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', mode: Mode::Asset)))->render();
        $this->assertStringContainsString('src="/takt/takt.js"', $html);
        $this->assertStringContainsString('data-domain="example.com"', $html);
    }

    public function test_exclude_localhost_false_emits_attr(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', excludeLocalhost: false, mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('data-exclude-localhost="false"', $html);
    }

    public function test_endpoint_attr_is_emitted(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', endpoint: '/collect', mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('data-endpoint="/collect"', $html);
    }

    public function test_default_endpoint_is_omitted(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', mode: Mode::Cdn)))->render();
        $this->assertStringNotContainsString('data-endpoint', $html);
    }

    public function test_script_origin_emits_data_attr(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', scriptOrigin: 'https://t.example.com', mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('data-script-origin="https://t.example.com"', $html);
    }

    public function test_script_origin_with_default_endpoint_omits_data_endpoint(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', scriptOrigin: 'https://t.example.com', mode: Mode::Cdn)))->render();
        $this->assertStringContainsString('data-script-origin="https://t.example.com"', $html);
        $this->assertStringNotContainsString('data-endpoint', $html);
    }

    public function test_asset_mode_src_uses_script_origin(): void
    {
        $html = (new SnippetRenderer(new Options(domain: 'example.com', scriptOrigin: 'https://t.example.com/', mode: Mode::Asset)))->render();
        $this->assertStringContainsString('src="https://t.example.com/takt/takt.js"', $html);
        $this->assertStringContainsString('data-script-origin="https://t.example.com/"', $html);
    }

    public function test_inline_neutralizes_script_close_case_insensitively(): void
    {
        $this->assertSame('<\\/script>', SnippetRenderer::neutralizeScriptClose('</script>'));
        $this->assertSame('<\\/SCRIPT >', SnippetRenderer::neutralizeScriptClose('</SCRIPT >'));
        $this->assertSame('var takt={};', SnippetRenderer::neutralizeScriptClose('var takt={};'));
    }
}
