# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://github.com/datlechin/flarum-passkey/releases/tag/v1.0.0
