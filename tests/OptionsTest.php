<?php

namespace Vskstudio\Takt\Tests;

use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\Mode;
use Vskstudio\Takt\Options;

final class OptionsTest extends TestCase
{
    public function test_defaults(): void
    {
        $o = new Options(domain: 'example.com');
        $this->assertSame('/api/event', $o->endpoint);
        $this->assertFalse($o->outbound);
        $this->assertFalse($o->files);
        $this->assertTrue($o->excludeLocalhost);
        $this->assertNull($o->nonce);
        $this->assertNull($o->scriptOrigin);
        $this->assertSame(Mode::Inline, $o->mode);
    }

    public function test_from_array_overrides(): void
    {
        $o = Options::fromArray(['domain' => 'a.com', 'outbound' => true, 'mode' => 'cdn']);
        $this->assertTrue($o->outbound);
        $this->assertSame(Mode::Cdn, $o->mode);
    }

    public function test_from_array_parses_script_origin(): void
    {
        $o = Options::fromArray(['domain' => 'a.com', 'scriptOrigin' => 'https://t.a.com']);
        $this->assertSame('https://t.a.com', $o->scriptOrigin);
    }
}
