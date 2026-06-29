<?php

namespace Vskstudio\Takt;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class Takt
{
    private bool $strict = false;
    private ?string $userIp = null;
    private ?string $userAgent = null;

    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    /**
     * @param string $endpoint Base ingest origin (NOT a full path); '/api/event'
     *   is appended on each send. Pass {@see Options::HOSTED_ORIGIN}
     *   ('https://taktlytics.com') to target the hosted Takt collector, or your
     *   own first-party origin. This is a required base origin with no default:
     *   unlike the client-side snippet it is always explicit, so there is no
     *   default to migrate and no risk of a doubled '/api/event/api/event'.
     */
    public function __construct(
        private readonly string $endpoint,
        private readonly string $domain,
        private readonly ?string $apiKey = null,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    public function withVisitor(?string $ip, ?string $userAgent): self
    {
        $clone = clone $this;
        $clone->userIp = $ip;
        $clone->userAgent = $userAgent;
        return $clone;
    }

    public function strict(): self
    {
        $clone = clone $this;
        $clone->strict = true;
        return $clone;
    }

    /** @param array<string,scalar> $props */
    public function event(string $name, array $props = [], ?Revenue $revenue = null, ?string $url = null, ?string $referrer = null): void
    {
        $payload = [
            'n' => $name,
            'd' => $this->domain,
            'u' => $url ?? '',
            'r' => $referrer ?? '',
        ];
        if ($props !== []) {
            $payload['p'] = array_map(static fn ($v) => (string) $v, $props);
        }
        if ($revenue !== null) {
            $payload['$'] = $revenue->toArray();
        }

        $this->send($payload);
    }

    public function pageview(?string $url = null, ?string $referrer = null): void
    {
        $this->event('pageview', [], null, $url, $referrer);
    }

    /** @param array<string,mixed> $payload */
    private function send(array $payload): void
    {
        try {
            $request = $this->requestFactory
                ->createRequest('POST', rtrim($this->endpoint, '/') . '/api/event')
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(
                    json_encode($payload, JSON_THROW_ON_ERROR)
                ));

            if ($this->apiKey !== null) {
                $request = $request->withHeader('Authorization', 'Bearer ' . self::headerSafe($this->apiKey));
            }
            if ($this->userIp !== null) {
                $request = $request->withHeader('X-Forwarded-For', self::headerSafe($this->userIp));
            }
            if ($this->userAgent !== null) {
                $request = $request->withHeader('User-Agent', self::headerSafe($this->userAgent));
            }

            $response = $this->httpClient->sendRequest($request);

            if ($this->strict && $response->getStatusCode() !== 202) {
                throw new \RuntimeException('Takt ingest returned ' . $response->getStatusCode());
            }
        } catch (\Throwable $e) {
            if ($this->strict) {
                throw $e instanceof \RuntimeException ? $e : new \RuntimeException($e->getMessage(), 0, $e);
            }
        }
    }

    /**
     * Strip control characters (incl. CR/LF) from an outgoing header value, so a
     * spoofed buyer IP/User-Agent or a mangled key can never split or inject
     * headers — defense in depth on top of the PSR-7 layer's own validation.
     */
    private static function headerSafe(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/', '', $value) ?? '';
    }
}
