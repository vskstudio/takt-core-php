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
- `Mode::Sdk` — emits an ES-module `<script type="module">import{init}…;init({…})</script>` that boots the full SDK. The only mode able to express `scrubUrl` (see below). The module is loaded from `{scriptOrigin}/takt/takt.esm.js` when `scriptOrigin` is set, otherwise from jsDelivr. Self-hosting it is your responsibility — the vendored bundle only ships `takt.auto.js` (for `Mode::Asset`), not the ES module.

```php
use Vskstudio\Takt\Mode;

new Options(domain: 'example.com', mode: Mode::Cdn, nonce: $cspNonce);
```

The snippet honors `domain`, `endpoint`, `scriptOrigin` and `excludeLocalhost`. SPA tracking and Do-Not-Track respect are always on.

### Where events are sent

By default the snippet ingests to the hosted Takt origin — `https://taktlytics.com/api/event` — so a bare setup works out of the box, including on static sites with no backend. To override:

- `endpoint: '/api/event'` — restore the same-origin first-party proxy (you forward `/api/event` to Takt from your own server). Anti-adblock, but requires a backend.
- `endpoint: 'https://collect.example.com/api/event'` — any custom absolute collector URL.
- `scriptOrigin: 'https://t.example.com'` — let the tracker derive `{scriptOrigin}/api/event` (first-party, anti-adblock). `endpoint` wins over `scriptOrigin`.

Autocapture is opt-in and bundled into the vendored `takt.auto.js`. Each toggle adds a token to a single `data-auto` attribute the tracker reads:

- `outbound: true` — outbound link clicks (`outbound`)
- `files: true` — download clicks (`downloads`); narrow the matched extensions with `fileExtensions: ['pdf', 'zip']`, emitted as `data-downloads-ext`
- `tagged: true` — elements tagged in HTML with `data-takt-event` (`tagged`)
- `notFound: true` — 404 pageviews (`404`)

`scriptOrigin` sets a first-party origin to serve the tracker + derive the endpoint from (`{origin}/api/event`) — your Takt domain or a custom domain to dodge ad-blockers (`endpoint` wins over it). In `Mode::Asset` the loader `src` is also served from that origin (`{origin}/takt/takt.auto.js`).

### Advanced options

Each is `null` by default ("unset" — the tracker's own default applies); only a non-default value is rendered.

- `sampleRate: 0.5` — keep ~50% of hits (`data-sample-rate`).
- `trackQuery: true` — keep the raw query string + hash in tracked URLs (`data-track-query`); off by default URLs are stripped.
- `queryParams: ['utm_source', 'utm_medium']` — allowlist of params to keep when `trackQuery` is off (`data-query-params`).
- `respectDnt: false` — stop honoring the browser Do-Not-Track header (`data-respect-dnt`).
- `enabled: false` — kill-switch; the snippet still renders but the tracker boots disabled (`data-enabled`).
- `scrubUrl: '(u) => u.split("#")[0]'` — a **raw JS function** to rewrite every URL before it is sent.

```php
new Options(domain: 'example.com', sampleRate: 0.5, queryParams: ['utm_source']);
```

`scrubUrl` cannot be expressed as a data-attribute, so it requires `Mode::Sdk`; constructing a `SnippetRenderer` with `scrubUrl` set in any other mode throws. It is injected **verbatim** into the page as JavaScript — it is **dev-controlled only**. Never build it from user input.

```php
new Options(
    domain: 'example.com',
    mode: Mode::Sdk,
    scrubUrl: '(u) => u.split("?")[0]',
);
```

## Takt (server-to-server client)

Send events directly from your backend, attributed to the real visitor.

```php
use Vskstudio\Takt\Takt;
use Vskstudio\Takt\Revenue;

// $endpoint is a base origin (NOT a full path); '/api/event' is appended for you.
// Use Options::HOSTED_ORIGIN ('https://taktlytics.com') for the hosted collector.
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
