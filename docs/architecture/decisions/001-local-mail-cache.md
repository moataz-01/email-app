# ADR-001: Local mail cache and provider identities

## Status

Accepted. The application-user ownership portion is superseded by ADR-004.

## Context

The repository contains Laravel 13.31, NativePHP Desktop 2.3, Inertia 3.3 and a React starter kit, but no mail domain. NativePHP already selects SQLite, enables WAL and a 5-second busy timeout, and starts a database queue worker. Browser development currently uses MySQL. Gmail labels and IMAP folder membership have different identity rules.

## Decision

Use the active Laravel database connection: NativePHP's local SQLite in desktop mode, SQLite in tests, and the existing development connection in browser mode. Each mail account belongs to the OS-local workspace, without application login (ADR-004). All interactive reads query the cache with explicit account/mailbox consistency checks. The earlier authenticated-user requirement is superseded.

Keep message content separate from mailbox membership. `mailbox_messages` relates a message to one or more folders/labels and holds IMAP UID and UIDVALIDITY as decimal strings. Gmail message IDs and thread IDs are account-scoped opaque strings. RFC Message-ID is metadata, never a unique identity or sufficient evidence to merge messages. IMAP copies may remain separate messages until a provider supplies reliable cross-folder identity.

On MySQL/MariaDB, opaque provider identifiers use VARBINARY(1020), preserving case, Unicode bytes and trailing spaces instead of inheriting a case-insensitive collation. SQLite already uses binary string comparison. The binary-identity forward migration preserves existing data; its rollback to text can fail if distinct identifiers would collide, so use forward fixes for populated caches.

Use composite foreign keys to reject cross-account thread, membership, and sync-state references, including writes outside Eloquent. Bodies live separately from list metadata; attachment rows hold metadata only. Lists use stable date/ID cursor pagination. Sync cursors are opaque strings and are scoped to an account or a mailbox.

Provider adapters will normalize bounded batches behind contracts, without exposing transport objects to controllers or React. Define executable contracts with the first provider implementation, rather than speculative empty adapters. Sync writes and cursor advancement must eventually share a transaction and account lock; page requests never initiate network synchronization.

## Alternatives Considered

- Provider-backed page requests: simpler initially, incompatible with offline usage and responsive navigation.
- One mailbox foreign key on each message: cannot represent Gmail labels without duplicate content.
- Globally unique RFC Message-ID: unsafe for duplicates, missing headers, and unrelated accounts.
- Replace the development database with SQLite: unnecessary; preserve installed infrastructure while testing desktop semantics.

## Consequences

Cache reads work offline while the local application runs. Account removal cascades cached data; downloaded files will need explicit cleanup when implemented. This milestone does not implement transport synchronization, conflict resolution, automatic threading, FTS, sending, or credential entry. Message content in the cache is not encrypted at rest; credential encryption is a separate boundary. Schema rollback deletes mail cache data and is for disposable development databases only.

## Evidence

Installed `vendor/nativephp/desktop/src/NativeServiceProvider.php::rewriteDatabase` and `config/nativephp.php`; [NativePHP queue documentation](https://nativephp.com/docs/desktop/2/digging-deeper/queues).
