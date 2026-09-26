# ADR-005: Provider connection testing without a third-party IMAP client

## Status

Accepted; implemented as M4a.

## Context

M3 saves IMAP/SMTP account configuration with a platform-encrypted credential reference, but never attempts a connection: `status` starts and stays `not_connected`, and the account form told the user connection testing was not enabled. The ledger's next coherent increment calls for actually testing a saved account's IMAP and SMTP settings, with a provider contract, fake-transport tests, strict TLS verification and error mapping. Bounded mailbox discovery/sync (LIST, SELECT, UID SEARCH/FETCH, UIDVALIDITY cursors) is deliberately out of scope here and deferred to a later increment.

No IMAP client library is installed, and CLAUDE.md requires approval before adding a dependency. `symfony/mailer` is already installed as a direct dependency of `laravel/framework` and implements a complete, TLS/STARTTLS-aware SMTP client (`EsmtpTransport`) that Laravel's own `Mail` facade already relies on.

## Decision

Build a minimal raw-socket IMAP client (`App\Services\Mail\Imap`) scoped to exactly CAPABILITY, LOGIN and LOGOUT — nothing that lists or reads mail. It sends credentials as IMAP literals rather than quoted strings, re-issues `CAPABILITY` after a STARTTLS upgrade per RFC 3501 §6.2.1, verifies TLS peer certificates, and bounds every connect/read with an explicit timeout. Its transport is a small `ImapSocket` interface, so `RawImapClient`'s protocol logic is unit-tested against a pure in-memory fake (`ScriptedImapSocket`) with no real sockets in the test suite.

Reuse `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport` for SMTP rather than writing a second protocol client, formalizing `symfony/mailer` as a direct `composer.json` requirement (no new package; `composer.lock` is unchanged). `EsmtpTransport::setRequireTls(true)` is set unconditionally so a `starttls`-configured account can never silently fall back to a plaintext session if the server doesn't advertise STARTTLS. Symfony's own stream classes are `@internal`/`final`, so SMTP correctness below the transport API is not re-tested here; one test exercises a real connection-refused failure against a closed local port to prove the classification wiring end to end.

Both protocol testers implement a single-method contract (`ImapConnectionTester`, `SmtpConnectionTester`) returning a `ConnectionTestResult` built only from a fixed, small `ConnectionFailureReason` enum — never from raw exception or server-response text, which for SMTP auth failures and some IMAP server replies can echo the submitted username back. `TestMailAccountConnection` runs both tests synchronously within the request (never a page load — this is an explicit user action, not automatic sync) and records `imap_status`, `smtp_status`, `status` and `last_connection_tested_at` on the account in one update after both network calls complete; a credential-storage failure leaves the account row untouched, since no network was attempted.

## Alternatives Considered

- Add an off-the-shelf IMAP package (e.g. `webklex/php-imap`): rejected. It needs dependency approval and brings far more surface area than three commands require.
- Write a raw SMTP client to mirror the raw IMAP client for symmetry: rejected. It would reinvent EHLO/STARTTLS/AUTH negotiation Symfony already implements correctly and that Laravel's mailer already exercises in production.
- Run the connection test as a queued job with a polling/broadcast UI: rejected for this increment. A single bounded round trip per protocol, hard-timeout-bounded and triggered by an explicit click, does not need background execution; revisit when M4b's actual mailbox sync needs it.

## Consequences

This is the application's first real outbound network I/O, and it is gated entirely behind an explicit user action. `RawImapClient` is new protocol-parsing surface with its own unit tests; `EsmtpConnectionTester`'s failure classification is coarser than the IMAP client's since Symfony's internals aren't owned by this codebase. `mail_accounts.status` gains two new sibling columns (`imap_status`, `smtp_status`) and `last_connection_tested_at`, which must stay in sync between the controller's explicit column allowlist and the frontend `MailAccount` type. Windows/Linux platform differences do not apply here — unlike ADR-002's credential boundary, connection testing itself has no platform gate; it runs wherever the account was configured.
