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
        $this->assertFalse($o->notFound);
        $this->assertFalse($o->tagged);
        $this->assertSame([], $o->fileExtensions);
    }

    public function test_from_array_overrides(): void
    {
        $o = Options::fromArray(['domain' => 'a.com', 'outbound' => true, 'mode' => 'cdn']);
        $this->assertTrue($o->outbound);
        $this->assertSame(Mode::Cdn, $o->mode);
    }

    public function test_from_array_parses_autocapture_options(): void
    {
        $o = Options::fromArray([
            'domain' => 'a.com',
            'not_found' => true,
            'tagged' => true,
            'file_extensions' => ['pdf', ' docx ', '', 7],
        ]);
        $this->assertTrue($o->notFound);
        $this->assertTrue($o->tagged);
        $this->assertSame(['pdf', 'docx', '7'], $o->fileExtensions);
    }

    public function test_from_array_reads_snake_and_camel_keys(): void
    {
        $snake = Options::fromArray(['domain' => 'a.com', 'exclude_localhost' => false, 'not_found' => true]);
        $this->assertFalse($snake->excludeLocalhost);
        $this->assertTrue($snake->notFound);

        $camel = Options::fromArray(['domain' => 'a.com', 'fileExtensions' => ['zip']]);
        $this->assertSame(['zip'], $camel->fileExtensions);
    }

    public function test_from_array_parses_script_origin(): void
    {
        $o = Options::fromArray(['domain' => 'a.com', 'scriptOrigin' => 'https://t.a.com']);
        $this->assertSame('https://t.a.com', $o->scriptOrigin);
    }
}
