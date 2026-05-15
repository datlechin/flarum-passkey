# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0]

First release on the Flarum 1.x line. The earlier `v1.0.x` tags were
Flarum 2.x-only; `v1.1.0` and onward on the `1.x` branch target
`flarum/core: ^1.8`. The Flarum 2.x line continues under `v2.0.0+`
on `main`.

### Added

- Flarum 1.x (`^1.8`) compatibility. The full feature set of the
  Flarum 2.x line (`v1.0.x`) runs on Flarum 1.x sites; `composer
  require datlechin/flarum-passkey` on a 1.x install resolves to
  this branch automatically.

### Changed

- Minimum PHP is now `^8.2` (was `^8.3` on the 2.x line), matching
  `web-auth/webauthn-lib`'s actual floor.
- Notification emails (`PasskeyRevoked`, `PasskeyBulkRevoked`,
  `PasskeyCounterRegression`, `BackupStatusChangedEvent`) are sent
  as plain text on Flarum 1.x — the HTML "informational" email
  template used on Flarum 2.x does not exist on 1.x. Subject and
  body content are unchanged; only the wrapping differs.

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

[1.1.0]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.1.0
[1.0.1]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.0.1
[1.0.0]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.0.0
