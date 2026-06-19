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
- `Mode::Asset` — emits a deferred loader pointing at a self-hosted `/takt/takt.auto.js`.

```php
use Vskstudio\Takt\Mode;

new Options(domain: 'example.com', mode: Mode::Cdn, nonce: $cspNonce);
```

The snippet honors `domain`, `endpoint`, `scriptOrigin` and `excludeLocalhost`. SPA tracking and Do-Not-Track respect are always on.

Autocapture is opt-in and bundled into the vendored `takt.auto.js`. Each toggle adds a token to a single `data-auto` attribute the tracker reads:

- `outbound: true` — outbound link clicks (`outbound`)
- `files: true` — download clicks (`downloads`); narrow the matched extensions with `fileExtensions: ['pdf', 'zip']`, emitted as `data-downloads-ext`
- `tagged: true` — elements tagged in HTML with `data-takt-event` (`tagged`)
- `notFound: true` — 404 pageviews (`404`)

`scriptOrigin` sets a first-party origin to serve the tracker + derive the endpoint from (`{origin}/api/event`) — your Takt domain or a custom domain to dodge ad-blockers (`endpoint` wins over it). In `Mode::Asset` the loader `src` is also served from that origin (`{origin}/takt/takt.auto.js`).

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
