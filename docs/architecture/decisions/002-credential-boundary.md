# ADR-002: Platform-protected credentials

## Status

Accepted; implemented in M3, with Windows runtime acceptance pending.

## Context

Desktop application keys distributed in an application bundle cannot protect user passwords effectively. Installed NativePHP Desktop 2.3 exposes `System::canEncrypt`, `encrypt`, and `decrypt`; its Electron bridge uses `safeStorage`.

## Decision

Use CredentialCipher backed by NativePHP System encryption and CredentialStore for persistence. Enable password entry only when NativePHP is running on Windows and canEncrypt succeeds. Electron safeStorage uses Windows DPAPI; browser and Linux development fail closed because Linux may select an insecure basic_text backend. Accounts will hold only an opaque credential reference, excluded from UI serialization. Ciphertext is stored separately in mail_credentials, uniquely bound to the account; its encrypted payload also carries the account ID. React never receives persisted secrets. Configuration and credential-reference writes share a transaction; encryption failure leaves no partial account. Check encryption availability and fail closed if the platform bridge cannot protect credentials; do not silently fall back to a bundled APP_KEY or plaintext. Mock the platform boundary in normal tests.

## Alternatives Considered

- Laravel encrypted casts using bundled APP_KEY: insufficient key separation for a distributed desktop client.
- New OS credential package: unnecessary until the installed bridge is evaluated on Windows.
- Plaintext development fallback: rejected.

## Consequences

The account form can save settings in the supported Windows runtime; browser/Linux password entry remains disabled. Automated tests use a cipher fake to exercise persistence, replacement, corruption, account binding and rollback; these do not prove Windows encryption, restart, or decryption behavior. Those acceptance checks remain outstanding. OS encryption protects storage, not malicious software running as the same OS user. Inertia DevTools recording and client hooks are explicitly disabled: its local defaults record request/response bodies and omit the new password fields and native process-secret header from redaction. Tests exercise an Inertia POST in the local environment to prevent this regression. Passwords are also excluded from trimming and session flash data.

Background workers must be tested for access to the NativePHP bridge. OAuth client registration, PKCE/state/callback validation, and token lifecycle require a later ADR and tests.
