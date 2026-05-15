# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0]

Version-line realignment. Extension major now tracks Flarum core
major, so the version numbers stop lying about what they target:

- `v2.x` (this line, on `main`) — for **Flarum 2.x** sites.
- `v1.x` (on the new `1.x` branch) — for **Flarum 1.x** sites.

No functional changes vs `v1.0.1` (which was, despite its number,
also a Flarum 2.x release). Existing installs locked to `^1.0` will
not auto-update across the major boundary; widen the constraint to
`^2.0` (or `^1.0 || ^2.0`) to follow this line going forward. Flarum
1.x users should require `^1.0` — composer resolves it to the new
`1.x` branch automatically (see `[1.1.0]` on that branch's CHANGELOG).

### Changed

- `branch-alias`: `dev-main` now aliases to `2.x-dev` (was `1.x-dev`),
  matching the actual core-compatibility line.

### Removed

- `@flarum/jest-config` and the empty `js/tests/`, `jest.config.cjs`,
  `tsconfig.test.json` scaffolding. The package was never wired in
  (`frontendTesting: false`, CI `enable_tests: false`); removing it
  drops the transitive `@tootallnate/once` advisory
  (`GHSA-vpq2-c234-7xj6`, low severity, dependabot alert #1). No
  runtime impact; the webpack bundle is byte-identical.

## [1.0.1]

### Changed

- Login ceremony now sends `hints: ['client-device', 'hybrid']`. On modern Chromium, Edge and Safari this collapses the credential picker to a single local prompt when only one platform passkey matches the origin, while still leaving the cross-device hybrid flow as a fallback.

## [1.0.0]

Initial release.

### Added

- Sign in with passkey from the login modal, including conditional UI (autofill on the identification field).
- "Suggest a passkey" modal after a successful password sign-in. Dismissable, with a 30-day cool-down to avoid pestering anyone who said no.
- Register, rename, and revoke passkeys from the user security page.
- Bulk "Revoke all" action on the user security tab. Issues one summary email instead of one per credential.
- Cross-device hybrid (CTAP 2.2) support out of the box, including QR-code flow when a phone holds the credential.
- Discoverable credentials (resident keys) so users can sign in without typing a username.
- Authenticator type display: passkeys are labelled with the make/model of the credential (iCloud Keychain, Google Password Manager, Windows Hello, 1Password, Bitwarden, YubiKey 5 series, ...) when the AAGUID is recognised. Falls back to the synced/device-only hint for anonymous credentials.
- Counter regression detection on every assertion, with a dedicated `PasskeyCounterRegression` event and an email to the owner. This is the canonical clone-detection signal in WebAuthn.
- Backup-state (BS flag) change detection, surfaced via the `PasskeyUsed` event and a notification email when a credential transitions between synced and device-only.
- Email notification on every passkey revoke. The credential owner is told which passkey was removed and whether they or a moderator triggered it.
- Site moderators can revoke any user's passkeys via the API. Useful for support cases where the owner has lost every device.
- "Require passkey" toggle on every group, edited from the canonical Edit Group modal. Members of flagged groups are reminded with a sticky banner until they register at least one passkey.
- Admin settings page with controls for relying party ID, relying party name, related origins, user verification policy, attestation conveyance, and login throttling.
- Built-in IP-scoped throttler on the login endpoint, configurable per minute.
- W3C related-origin requests served at `/.well-known/webauthn` for cross-domain login.
- Locale coverage: English and Vietnamese.
- Optional integration with `flarum/gdpr`: passkeys are exported when a user requests their data, and revoked when the user is anonymized or deleted.
- Domain events for extensibility: `PasskeyRegistered`, `PasskeyRevoked`, `PasskeyBulkRevoked`, `PasskeyUsed`, `PasskeyCounterRegression`. The standard `LoggedIn` event also fires on a successful passkey login.

[2.0.0]: https://github.com/datlechin/flarum-passkey/releases/tag/v2.0.0
[1.0.1]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.0.1
[1.0.0]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.0.0
