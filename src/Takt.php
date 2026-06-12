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

    public function __construct(
        private readonly string $endpoint,
        private readonly string $domain,
        private readonly ?string $apiKey = null,
        private ?ClientInterface $httpClient = null,
        private ?RequestFactoryInterface $requestFactory = null,
        private ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient ??= Psr18ClientDiscovery::find();
        $this->requestFactory ??= Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory ??= Psr17FactoryDiscovery::findStreamFactory();
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
    public function event(string $name, array $props = [], ?Revenue $revenue = null, ?string $url = null): void
    {
        $payload = [
            'n' => $name,
            'd' => $this->domain,
            'u' => $url ?? '',
            'r' => '',
        ];
        if ($props !== []) {
            $payload['p'] = array_map(static fn ($v) => (string) $v, $props);
        }
        if ($revenue !== null) {
            $payload['$'] = $revenue->toArray();
        }

        $this->send($payload);
    }

    public function pageview(?string $url = null): void
    {
        $this->event('pageview', [], null, $url);
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
                $request = $request->withHeader('Authorization', 'Bearer ' . $this->apiKey);
            }
            if ($this->userIp !== null) {
                $request = $request->withHeader('X-Forwarded-For', $this->userIp);
            }
            if ($this->userAgent !== null) {
                $request = $request->withHeader('User-Agent', $this->userAgent);
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
}
