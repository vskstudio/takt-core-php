# vskstudio/takt-core-php

## Unreleased

### Minor Changes

- The default client-side ingest endpoint is now the hosted Takt origin
  (`https://taktlytics.com/api/event`) instead of the same-origin relative path
  `/api/event`, mirroring `@vskstudio/takt-core@0.6.0`. A bare `Options` /
  `SnippetRenderer` setup now works out of the box, including on static sites
  with no backend (the old `/api/event` default 405s there). To restore the
  same-origin first-party proxy, pass `endpoint: '/api/event'`; `endpoint` and
  `scriptOrigin` overrides keep working as before. A new `Options::HOSTED_ORIGIN`
  / `Options::HOSTED_ENDPOINT` constant holds the hosted origin. The
  server-to-server `Takt` client is unchanged: its `$endpoint` is a required base
  origin (it appends `/api/event`), so it has no default to migrate.

## 0.3.2

### Patch Changes

- Re-bundle the inline browser tracker (`resources/takt.auto.js`) from
  `@vskstudio/takt-core@0.5.1`: outbound/download autocapture now matches the
  link protocol explicitly against `http:`/`https:`.

## 0.3.1

### Patch Changes

- Harden the server-to-server client: control characters (incl. CR/LF) are now
  stripped from the `Authorization`, `X-Forwarded-For` and `User-Agent` header
  values before they are sent, so a spoofed buyer IP/User-Agent can never split
  or inject headers — defense in depth on top of the PSR-7 layer's validation.
- `Revenue` now rejects non-numeric amounts (only the 3-letter currency code was
  validated before).

## 0.3.0 and earlier

See the Git tags (`v0.1.2` … `v0.3.0`) for the history that predates this
changelog: the advanced tracker options (`sampleRate`, `trackQuery`,
`queryParams`, `respectDnt`, `enabled`, `scrubUrl`) and `Mode::Sdk`, the
autocapture parity (`404`/`tagged`/`fileExtensions`) and the initial S2S client.
