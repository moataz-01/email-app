# Email client implementation ledger

## M4a follow-up — Real IMAP diagnostics (2026-09-26)

Repository findings: Laravel 13.33, NativePHP 2.3.1, Inertia 3.4 and Symfony Mailer 8.1.7 are installed. Saved-account connection testing already exists; 19 targeted tests pass before this increment. Browser/Linux onboarding remains blocked by the intentional Windows-only credential cipher. Setup copy is stale. The raw IMAP client needs authentication-negotiation and response-bound fixes before broader interoperability testing. Git metadata remains unavailable.

Objective: test a real IMAP login from the app or terminal without requiring saved credentials or SMTP configuration. ADR-006 applies. No new package or schema change is required.

Definition of Done, recorded before implementation:

- [x] An accessible IMAP test page accepts TLS/STARTTLS settings and reports success or a fixed actionable failure; retry and request errors are handled.
- [x] The CLI uses a hidden password prompt, correct exit codes, and the same provider boundary.
- [x] Diagnostics never create accounts or persist passwords; validation/session/recording boundaries are tested, history remembering is unused, and React clears the password data and defaults after the attempt. Local access and CSRF remain enforced.
- [x] Protocol tests cover authentication negotiation/rejection, unsolicited responses, TLS refusal, malformed/oversized replies and bounded processing; opt-in stream transport tests exercise real local TLS.
- [x] Existing saved-account tests continue to pass; relevant PHP tests, PHPStan, TypeScript, scoped formatting, application lint and build pass.
- [x] [Windows testing instructions](imap-connection-testing.md) and actual verification limits are documented. No claim of completed mailbox sync or full application roadmap.
- [ ] Windows UI/credential restart checks and the user's real provider login remain unverified: this environment is Linux and no real account credentials were supplied. Enter credentials locally using the documented Windows flow; do not send them in chat. This acceptance gate is outstanding.

Verification: `RUN_IMAP_SOCKET_TESTS=1 php artisan test --compact` passed **145 tests, 644 assertions, no skips**. The local socket tests required execution outside the socket-restricted sandbox; the first full sandbox run was blocked by the existing SMTP loopback test. New endpoint/CLI tests failed on missing routes/options before implementation, and six new IMAP protocol cases reproduced defects before fixes. The eight TLS integration cases passed against a disposable loopback server with generated certificates and synthetic credentials. No external mail provider was contacted.

`composer types:check`, `npm run types:check`, `npm run check -- --no-fmt`, scoped frontend/docs formatting and the production build pass. Pint passed using explicit changed-file paths because `--dirty` cannot run without valid Git metadata. Unscoped `npm run check` still reports **27 unrelated formatting violations** in agent skills and root configuration; the optional fontaine build warning also remains. Neither is hidden by a suppression or dependency change. The pre-existing NativePHP connection-result migration was applied successfully with `native:migrate`; this follow-up introduces no schema/dependency change.

Review found Inertia's successful HTTP submissions update form defaults automatically; the diagnostic explicitly keeps the password default empty and clears its data/DOM value, preventing reuse on later attempts. Actual browser/native interaction is not automated here. Mailbox discovery and background sync remain M4b; this deliverable verifies secure login only.

## Repository assessment — 2026-09-09

- PHP 8.5 environment; Laravel 13.31.0, NativePHP Desktop 2.3.0, Inertia Laravel 3.3.3, Fortify 1.39, Wayfinder 0.1.21. Composer manifest allows PHP 8.3, while Laravel 13 requires newer PHP; CI currently selects 8.3 and needs correction before production CI can be trusted.
- React 19, strict TypeScript, Tailwind 4 CSS-first theme, Vite 8/Vite Plus, React compiler, Radix components, Lucide, Sonner. Reuse components, appearance hook, Form Requests, typed route generation, and existing settings layouts.
- Fortify supports login and password reset; registration and verification are not enabled. Preserve this boundary. First-run desktop profile provisioning remains future work.
- Only User, settings controllers, Fortify reset action, framework cache/jobs/user migrations exist. No application services/jobs/events/listeners, mail providers, mail cache, search, drafts, attachments, or desktop integrations beyond the default window. The console route is the starter inspirational command.
- Browser development uses MySQL; tests use in-memory SQLite. NativePHP rewrites its own connection to local SQLite with WAL/busy timeout and redirects queue connections. Its default worker consumes `default`. Keep this infrastructure.
- Pest 5 + Laravel plugin, RefreshDatabase feature convention, Larastan level 7, Pint, `npm run check`, `npm run types:check`, `npm run build`. No configured application frontend test suite or browser runner. Baseline: 21 tests pass, 3 skip. Static/lint baselines are recorded below after execution.
- `.ai/rules` initially absent; no previous ADRs. Git commands report an invalid repository despite a `.git` entry, so changes are verified against a local pre-edit source snapshot, not Git history.
- No IMAP client, Gmail SDK, OAuth client, rich text editor, or HTML sanitizer is installed. Laravel's existing HTTP client and Symfony Mailer may serve future adapters. No dependencies added in these increments.

## M1 — Account-scoped local mail domain

Objective: persistent accounts, mailboxes, threads, messages, bodies, recipients, attachment metadata, memberships and sync cursors.

Critical paths: account identity isolation, duplicate remote identities, IMAP UID epoch identity, cross-account foreign keys, dependent cache removal, opaque cursor precision, bodies excluded from list serialization.

Definition of Done review — implementation delivered; acceptance gates remain:

- [x] Reversible migrations, typed Eloquent relationships/casts, useful factories and opt-in sample seeder exist.
- [x] Behavioral persistence tests fail before implementation and pass afterward on SQLite.
- [x] Account ownership, uniqueness and cascade integrity are enforced by the database.
- [x] No password/token storage or external requests introduced.
- [ ] Global static/format acceptance remains blocked by the pre-existing issues listed below. Relevant tests, scoped Pint/formatting, TypeScript, lint and build pass.
- [x] NativePHP migration is exercised where practical; Windows packaging/runtime limitations are explicit.
- [x] ADR-001 and ADR-002 document storage/provider boundaries.

## M2 — Local mailbox and desktop shell

Objective: replace the dashboard placeholder with cache-backed account/folder navigation, a unified inbox, stable bounded message pages, metadata search and a safe reading pane. Reuse login/settings and theme behavior. Use Gmail's navigation/density/reading-pane patterns with original branding and no proprietary assets.

Critical paths: unauthenticated access, cross-user account/mailbox/message access, mailbox/account filter combinations, pagination with equal dates, search isolation, empty account state, HTML/body exclusion from list responses.

Definition of Done review — implementation delivered; acceptance gates remain:

- [x] Controller/Form Request scope and validate every read; no provider request is made.
- [x] Responsive React shell, account/folder navigation, search, pagination, reader, loading/empty states, theme support and clear unavailable-feature states exist.
- [x] HTTP tests protect ownership, bounded results, filtering and safe response shape.
- [ ] Global static/format acceptance remains blocked by the pre-existing issues listed below. All changed code passes its applicable checks.
- [x] Optional fixture seeding requires an explicit user ID and never runs automatically.
- [x] ADR-003 documents the temporary plain-text-only renderer.
- [ ] Windows resize/keyboard/rendering smoke test is blocked: this environment is Linux and has no browser runner. Native launch configuration is tested through a fake native HTTP boundary; actual rendering and packaging still require Windows.

## Next milestones

M3: platform-protected credential storage and account configuration, followed by provider connection tests. M4: IMAP discovery and bounded sync with UIDVALIDITY tests. Then SMTP, Gmail OAuth/API, secure HTML/attachments, threads, drafts, durable offline mutations, advanced local search, NativePHP integrations, productivity and hardening. Each begins with repository/ADR review and a new DoD. This ledger does not claim the full roadmap is implemented.

## Verification evidence — M1 and M2

- `php artisan test --compact`: **58 passed, 3 existing skips, 252 assertions** (61 tests total). Added 37 tests/cases across MailPersistenceTest, MailboxTest, MailDemoSeederTest and NativeWindowTest. The first persistence run failed on missing domain classes; the first mailbox run failed on missing props/ownership behavior. Malformed cursor and native launch regressions were observed failing before their fixes.
- `npm run types:check`: passed.
- `npm run check -- --no-fmt`: passed, no warnings/errors in 57 source files. Scoped `npm run check:fix -- resources/js/pages/mail resources/js/components/mail resources/js/types/mail.ts resources/js/app.tsx docs` passed formatting and lint.
- `npm run build`: passed. Existing optional `fontaine` font-fallback warning remains; bundled font files are emitted, and no dependency was added.
- `vendor/bin/pint --dirty --format agent`: unavailable because `.git` has no valid repository metadata. Ran Pint explicitly over every changed PHP file; passed.
- `composer types:check`: one **pre-existing** failure remains at `database/factories/UserFactory.php:49`: `withTwoFactor()` declares a static return type but has an empty body. The NativeAppServiceProvider return annotation discovered during baseline was fixed while updating native launch configuration. No new static-analysis errors remain.
- Unscoped `npm run check` is blocked by 26 pre-existing formatting violations in agent skills/configuration and related root files. These were not broadly rewritten. No lint suppressions or baselines were added.
- All three mail migrations applied successfully to the existing NativePHP SQLite and browser MySQL databases. MySQL metadata confirms all four provider ID columns use `varbinary(1020)` with no text collation. Automated persistence behavior is tested on SQLite; MySQL has migration/schema verification, not an isolated behavioral suite.
- Independent code review identified binary identity comparison and reader minimum-width issues; both fixed. Added starred/all views, backward pagination, literal wildcard search and malformed cursor coverage. Added a reverse membership index for message-to-folder lookups.
- Windows application launch, keyboard navigation, resizing, OS storage, restart, and installer behavior are **not verified** here. Do not mark either milestone fully accepted until its remaining gates are resolved.

## Trying the current increment

Opening the app now goes directly to Add your email on a fresh installation or to the cached inbox when accounts exist. NativePHP opens the launch route at 1440×900, minimum 800×600. There is no application login, registration, automatic user creation, or profile provisioning. Appearance and account management are available directly.

The optional command `php artisan mail:demo` adds two clearly marked sample accounts with eight cached messages to the active development database; no user ID is required. Repeated runs are harmless and existing non-demo accounts remain intact. Normal CLI uses MySQL; NativePHP uses a separate SQLite database. To preview in the NativePHP development database from Bash: `DB_CONNECTION=sqlite DB_DATABASE="$PWD/database/nativephp.sqlite" php artisan mail:demo`. It never calls providers or runs automatically.

Gmail UI references reviewed: [inbox layouts](https://support.google.com/mail/answer/18522) and [reading pane](https://support.google.com/mail/answer/9499937). The implementation uses original Letter branding and existing icons/components.

Current functionality includes local mailbox browsing/search/pagination, plain-text reading, attachment metadata, account setup and protected credential references. IMAP/SMTP settings can be saved only when the Windows native cipher is available. A saved account can now be tested — a user-triggered "Test connection" action attempts a real IMAP login and SMTP handshake and records per-protocol status — but this does not create remote mailboxes or synchronize mail. Bounded mailbox discovery/sync, Gmail OAuth, sending, drafts, HTML rendering and attachment downloads remain future increments. Metadata search currently scans its scoped cache; FTS and large-mailbox profiling remain future work.

## M3 — Login-free startup and secure account configuration

User correction: application login is not part of this desktop product. ADR-004 supersedes the earlier decision to use authenticated application users. The M1/M2 records above are historical.

Objective: launch directly into email setup or the cached inbox, preserve existing mail, remove application authentication endpoints/UI, and save IMAP/SMTP settings and protected credential references.

Definition of Done review (scope recorded before implementation):

- [x] New installations show Add your email; returning installations open cached mail without a login/session dependency.
- [x] Migration preserves existing mail and legacy ownership metadata without requiring a User for new accounts.
- [x] Login, logout, password reset, profile and application security endpoints are unavailable; appearance remains available.
- [x] Loopback-only development access, NativePHP runtime gate and CSRF remain effective.
- [x] IMAP/SMTP form validates hostnames, ports, TLS modes, usernames and passwords; passwords are excluded from flashed input, Inertia props and logs.
- [x] Native platform encryption is behind a testable contract; no insecure fallback; failed storage rolls back account configuration.
- [x] Configured accounts are clearly distinguished from connected/synchronized accounts. Gmail OAuth is not misrepresented as implemented.
- [ ] Global format gate remains blocked by unrelated repository formatting; source formatting, tests, PHPStan, TypeScript, lint and build pass. See evidence below.
- [x] ADRs/shared rules reflect the corrected ownership and credential strategy; Windows runtime limitations are explicit.

- [ ] Windows acceptance: native encryption/decryption, app restart with saved credentials, loss of OS access, main-window resizing and keyboard navigation require a Windows runtime. This Linux environment cannot verify them. M3 is implemented but not fully accepted until these checks pass.

## Verification evidence — M3

- The initial new tests failed on the retained login/user dependency and absent account-configuration classes. The Inertia recording regression separately failed because it attempted to persist an entry, then passed after recording was disabled.
- Full PHP regression suite: `php artisan test --compact` passed **90 tests, 444 assertions, no skips**. Targeted tests cover first/repeat launch, absent legacy auth endpoints, legacy migration/data preservation, account selection, CSRF, native secret gate, loopback/hostname rejection, account validation, password whitespace, ciphertext binding/rotation/corruption, rollback, and secret-free responses/session data. No real mail provider calls are made.
- PHPStan now passes with zero errors. Removed the obsolete empty two-factor UserFactory method while removing application authentication; this resolves the previous static-analysis blocker.
- TypeScript, application lint, scoped frontend/docs formatting, explicit-path Pint and production build pass. `pint --dirty` remains unavailable because Git metadata is invalid. Unscoped `npm run check` reports 26 pre-existing formatting violations in agent instructions and root configuration files; no application source violation is reported. No dependencies or lint suppressions were added.
- Both forward migrations applied successfully to browser MySQL and NativePHP SQLite. MySQL schema confirms nullable legacy_user_id with no user foreign key. A separate in-memory SQLite upgrade test proves account/message/body preservation and removes the old user-deletion cascade. Native SQLite was backed up before applying the upgrade. Ownership rollback intentionally fails with recovery instructions rather than inventing user mappings; use a pre-migration backup for an ownership rollback.
- Independent review found the Inertia request recorder leakage described in ADR-002; fixed by disabling both server recording and client hooks. No other critical/important issue was reported.
- HTTP tests and frontend compilation are verified; an extra live HTTP smoke check could not run because no application server was listening at the configured localhost:8000. No browser automation runner is installed. Actual Windows encryption and UI verification remain blocked as listed above.

## M4a — Provider connection testing

Objective: given a saved account, actually attempt an IMAP login and an SMTP handshake using the stored configuration and decrypted credentials, classify success/failure without leaking provider or credential text, and record per-protocol and aggregate status. Bounded mailbox discovery/sync (LIST, SELECT, UID SEARCH/FETCH, UIDVALIDITY cursors, background sync jobs) is explicitly out of scope and deferred to M4b.

Critical paths: no IMAP client library installed anywhere in the dependency tree; SMTP reuses Symfony Mailer's `EsmtpTransport` (already a transitive dependency of `laravel/framework`, now formalized as direct); credential-storage failure must leave the account row untouched; connection parameters are read only from the persisted account, never from the request body; failure messages must never surface raw server/exception text that could echo credentials.

Definition of Done review — implementation delivered; acceptance gates remain:

- [x] A minimal raw-socket IMAP client (CAPABILITY/LOGIN/LOGOUT only) exists behind a fakeable transport seam; no third-party IMAP package was added.
- [x] SMTP connection testing reuses `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport`; STARTTLS is required, never optional, when the account is configured for it.
- [x] Both protocol testers are contracts (`ImapConnectionTester`, `SmtpConnectionTester`) bound in the container and substituted with fakes in every Feature test; no test opens a socket to a real external host.
- [x] Failure results carry only a fixed `ConnectionFailureReason`-derived summary; raw provider/exception text is never returned, flashed, or logged.
- [x] The connection-test route reads host/port/credentials only from the persisted account; a crafted request body cannot redirect the test elsewhere.
- [x] `mail_accounts` gains `imap_status`, `smtp_status`, `last_connection_tested_at`; a credential-storage failure leaves all three untouched.
- [ ] Global static/format acceptance remains blocked by the pre-existing issues listed below. Relevant tests, scoped Pint, PHPStan, TypeScript, lint and build pass.
- [x] ADR-005 documents the raw-socket-vs-library decision, the SMTP reuse, and the synchronous-not-queued choice for this increment.

- [ ] Windows acceptance: this increment introduces no new platform gate (connection testing has none, unlike ADR-002's credential boundary), but it has only been run against fakes/local sockets on Linux; a real provider round trip on Windows is not verified here.

## Verification evidence — M4a

- Full PHP regression suite: `php artisan test --compact` passed **110 tests, 512 assertions, no skips** (up from 90/444 after M3). New coverage: `tests/Unit/RawImapClientTest.php` (implicit-TLS and STARTTLS success sequences including the post-STARTTLS `CAPABILITY` re-issue, tagged `NO`/`BAD` handling, missing-STARTTLS refusal, read timeout — all against an in-memory scripted socket, no real I/O), `tests/Unit/EsmtpConnectionTesterTest.php` (a real connection-refused round trip against a closed local port, to prove the classification wiring end to end without any external host), and `tests/Feature/MailAccountConnectionTest.php` (success, per-protocol failure identification, credential-storage-unavailable leaves the row untouched, nonexistent account 404s, request body cannot override the tested host/port, repeated tests keep only the latest result, secrets never appear in the response/session/devtools recorder, and the account-creation flash copy no longer claims testing is unavailable).
- The first IMAP unit test run failed on a test-file quoting bug (single-quoted `\r\n` literals not being interpreted as CRLF bytes), not the implementation; fixed by using double-quoted strings for scripted protocol responses.
- `vendor/bin/phpstan analyse` (Larastan level 7): first run reported eight `property.notFound` errors on `MailAccount`'s `imap_*`/`smtp_*` columns — the first code in the application to access them as object properties rather than through mass assignment or an explicit column allowlist. Fixed with `@property` PHPDoc annotations on `MailAccount`, the same pattern already used on `MailMessage`. Passes with zero errors afterward.
- `npm run types:check`: passed. Scoped `npm run check:fix -- resources/js/pages/accounts resources/js/types/mail.ts resources/js/routes/accounts` and the new ADR file: passed formatting and lint. `npm run build`: passed; the pre-existing optional `fontaine` warning remains, no dependency was added for it.
- `vendor/bin/pint --format agent` was run explicitly over every new/changed PHP file (`--dirty` remains unavailable: this working directory has no `.git` repository at all, not just invalid metadata). Two files needed formatting fixes (import ordering, brace/operator spacing); both applied cleanly with no behavior change.
- `composer.json` gained one direct requirement, `symfony/mailer` (flagged to and approved by the user before editing): the package was already installed transitively via `laravel/framework`; `composer.lock`'s content-hash was refreshed with `composer update symfony/mailer --no-install`, which reported "Nothing to modify in lock file" — no new package was downloaded.
- The new migration (`imap_status`, `smtp_status`, `last_connection_tested_at` on `mail_accounts`) applied successfully to the active development database.
- Real provider round trips (an actual IMAP/SMTP server on the far end) and any Windows-specific behavior are **not exercised** here — all network-facing tests use fakes or a same-process closed local port. Do not consider provider interoperability proven until this has been tried against a real mailbox.

Next coherent increment (M4b): bounded IMAP mailbox discovery and sync — LIST, SELECT/EXAMINE, UID SEARCH/FETCH, UIDVALIDITY-aware cursors, and background execution via NativePHP's queue worker. The raw IMAP client and its fakeable transport seam from M4a are the foundation that increment extends.
