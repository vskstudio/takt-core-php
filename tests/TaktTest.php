<?php

namespace Vskstudio\Takt\Tests;

use Http\Mock\Client;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Vskstudio\Takt\Revenue;
use Vskstudio\Takt\Takt;

final class TaktTest extends TestCase
{
    private function makeClient(Client $mock): Takt
    {
        $psr17 = new Psr17Factory();
        return new Takt(
            endpoint: 'https://takt.example.com',
            domain: 'example.com',
            apiKey: 'k_test',
            httpClient: $mock,
            requestFactory: $psr17,
            streamFactory: $psr17,
        );
    }

    public function test_event_posts_raw_payload_with_bearer(): void
    {
        $mock = new Client();
        $mock->addResponse(new Response(202));
        $takt = $this->makeClient($mock);

        $takt->event('Signup', ['plan' => 'pro'], new Revenue('29.00', 'EUR'), 'https://example.com/p');

        $req = $mock->getLastRequest();
        $this->assertSame('POST', $req->getMethod());
        $this->assertSame('https://takt.example.com/api/event', (string) $req->getUri());
        $this->assertSame('Bearer k_test', $req->getHeaderLine('Authorization'));
        $body = (array) json_decode((string) $req->getBody(), true);
        $this->assertSame('Signup', $body['n']);
        $this->assertSame('example.com', $body['d']);
        $this->assertSame('https://example.com/p', $body['u']);
        $this->assertSame(['plan' => 'pro'], $body['p']);
        $this->assertSame(['a' => '29.00', 'c' => 'EUR'], $body['$']);
        $this->assertArrayNotHasKey('w', $body);
    }

    public function test_forwards_ip_and_user_agent(): void
    {
        $mock = new Client();
        $mock->addResponse(new Response(202));
        $takt = $this->makeClient($mock)->withVisitor('203.0.113.7', 'Mozilla/5.0');
        $takt->pageview('https://example.com/');

        $req = $mock->getLastRequest();
        $this->assertSame('203.0.113.7', $req->getHeaderLine('X-Forwarded-For'));
        $this->assertSame('Mozilla/5.0', $req->getHeaderLine('User-Agent'));
    }

    public function test_strict_mode_throws_on_non_202(): void
    {
        $mock = new Client();
        $mock->addResponse(new Response(401));
        $takt = $this->makeClient($mock)->strict();

        $this->expectException(\RuntimeException::class);
        $takt->event('X');
    }

    public function test_default_mode_swallows_errors(): void
    {
        $mock = new Client();
        $mock->addException(new \RuntimeException('network down'));
        $takt = $this->makeClient($mock);

        $takt->event('X');
        $this->assertTrue(true);
    }

    public function test_non_string_props_are_coerced(): void
    {
        $mock = new \Http\Mock\Client();
        $mock->addResponse(new \Nyholm\Psr7\Response(202));
        $takt = $this->makeClient($mock);
        $takt->event('Buy', ['count' => 3, 'paid' => true]);
        $body = (array) json_decode((string) $mock->getLastRequest()->getBody(), true);
        $this->assertSame(['count' => '3', 'paid' => '1'], $body['p']);
    }
}
