---
paths:
    - 'app/**'
---

# App

## Read mail from the account-scoped local cache

Follow docs/architecture/decisions/001-local-mail-cache.md. Interactive mail requests read the local database and explicitly restrict accounts to the authenticated user; never initiate provider sync in a page request. Keep Gmail IDs account-scoped and IMAP UID + UIDVALIDITY scoped to mailbox membership. Cross-account references must remain database-constrained.

## Use the OS-local workspace without application authentication

ADR-004 supersedes the authenticated-user restriction in the earlier cache rule. All configured mail accounts belong to this OS-local workspace; do not require or synthesize a User/login. Keep loopback browser access, NativePHP's process-secret gate, CSRF, and explicit mail-account consistency checks. Page reads use only the local cache. CredentialCipher must fail closed outside supported native secure storage; never use a bundled APP_KEY or plaintext fallback.
