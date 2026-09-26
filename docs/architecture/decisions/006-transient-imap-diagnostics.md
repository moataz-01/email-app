# ADR-006: Test IMAP without persisting credentials

## Status

Accepted. Extends ADR-002 and ADR-005 without changing their storage policy.

## Context

The current checkout already has saved-account IMAP/SMTP tests. Browser/Linux users cannot configure credentials because the cipher correctly requires the Windows native runtime. A real IMAP login test should not require SMTP settings or persistent credential storage. The existing raw client also needs bounded response processing, SASL PLAIN support, and correct continuation failure handling.

## Decision

Add an explicit IMAP-only diagnostic page and `mail:test-imap` command using the existing ImapConnectionTester contract. Test input is used in memory for one attempt; neither entry point creates accounts, stores credentials, or downloads mail. The CLI password is requested through a hidden prompt with visible fallback disabled; there is no password command-line option. Browser results contain only fixed messages and reason codes, use no-store responses, and retain CSRF/loopback/native-secret protections. Request recording stays disabled and password fields are cleared after submission. Browser diagnostics are not an encrypted-storage fallback.

Use implicit TLS or required STARTTLS, certificate/hostname validation, a 20-second socket-operation budget and bounded response lines. DNS lookup remains subject to the operating system resolver's timeout. Prefer SASL PLAIN when advertised after TLS; otherwise use literal LOGIN only when not disabled. Never retry an authentication failure automatically. Authentication success means only that login worked at the time of the test, not that a persistent connection or mail synchronization exists.

## Alternatives Considered

- Store browser passwords with the bundled APP_KEY: violates key separation.
- Require Windows to perform even a temporary test: unnecessarily blocks development diagnostics.
- Introduce another IMAP client only for testing: duplicates the existing boundary and needs dependency approval. Revisit a maintained parser before implementing general mailbox synchronization.

## Consequences

Users can test real provider credentials locally now, then configure durable accounts in Windows. Re-enter passwords for each test. Existing saved-account testing remains available. OAuth-only providers need the later OAuth flow. Normal automated tests use provider/socket fakes; opt-in local TLS integration tests verify the real stream transport without external accounts. A successful authenticated round trip against the user's provider can only be confirmed when they enter their credentials locally.

Protocol references: [IMAP authentication and STARTTLS semantics (RFC 9051)](https://www.rfc-editor.org/rfc/rfc9051.html), [PHP stream TLS negotiation](https://www.php.net/manual/en/function.stream-socket-enable-crypto.php). NativePHP 2.3.1 already supplies its certificate bundle to `openssl.cafile` in `resources/electron/electron-plugin/src/server/php.ts`; retain that configuration on Windows.
