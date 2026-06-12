# takt-core-php

Framework-agnostic PHP core for [Takt](https://github.com/vskstudio) analytics: a browser snippet renderer and a server-to-server event client.

## Install

```bash
composer require vskstudio/takt-core-php
```

## SnippetRenderer

Render the tracking snippet server-side and echo it into your `<head>`.

```php
use Vskstudio\Takt\SnippetRenderer;
use Vskstudio\Takt\Options;

$renderer = new SnippetRenderer(new Options(
    domain: 'example.com',
    outbound: true,
    files: true,
));

echo $renderer->render();
```

### Modes

The `Mode` enum controls how the bundle is delivered:

- `Mode::Inline` (default) — embeds the bundle inline in a `<script>` tag that self-boots. No extra request, and CSP-friendly: pass `nonce:` in `Options` to emit a `nonce` attribute.
- `Mode::Cdn` — emits a deferred loader pointing at the jsDelivr-hosted bundle.
- `Mode::Asset` — emits a deferred loader pointing at a self-hosted `/takt/takt.js`.

```php
use Vskstudio\Takt\Mode;

new Options(domain: 'example.com', mode: Mode::Cdn, nonce: $cspNonce);
```

The snippet honors `domain`, `endpoint`, `outbound`, `files` and `excludeLocalhost`. SPA tracking and Do-Not-Track respect are always on.

## Takt (server-to-server client)

Send events directly from your backend, attributed to the real visitor.

```php
use Vskstudio\Takt\Takt;
use Vskstudio\Takt\Revenue;

$takt = new Takt($endpoint, 'example.com', $apiKey);

$takt
    ->withVisitor($request->ip(), $request->userAgent())
    ->event('Signup', ['plan' => 'pro'], new Revenue('29.00', 'EUR'));

// or a pageview
$takt->withVisitor($ip, $userAgent)->pageview('https://example.com/welcome');
```

- Requires an ingest-scoped API key bound to the domain.
- Use `->withVisitor($ip, $userAgent)` so events are attributed to the visitor rather than your server.
- Fire-and-forget by default: transport errors are swallowed. Call `->strict()` to get a client that throws on failure (handy in tests).
- The PSR-18 HTTP client and PSR-17 factories are auto-discovered (`php-http/discovery`). You may also inject your own.

## Wire payload

Events are posted as JSON with compact keys: `n` (name), `d` (domain), `u` (url), `r` (referrer), `p` (props) and `$` (revenue). Screen width is not sent server-side.

## License

MIT
