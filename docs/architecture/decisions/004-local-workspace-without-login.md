# ADR-004: One local workspace, without application login

## Status

Accepted. Supersedes the application-user ownership decision in ADR-001.

## Context

The product owner clarified that opening a desktop mail client must lead directly to email account setup. The previous increment incorrectly retained the web starter kit's separate application login. Email-provider authorization and local application login are different concerns.

## Decision

Use a single workspace in the current OS user's NativePHP application data directory. Opening the app shows Add your email if there are no mail accounts, otherwise the cached inbox. Do not create a synthetic user, silently log in, or require registration/password recovery. Disable Fortify routes and remove application profile/security routes and UI. Keep installed packages unchanged.

Mail accounts belong directly to this local workspace. Preserve existing account IDs and dependent mail. Retain old user IDs only in a nullable, hidden legacy_user_id column with no foreign key or live ownership relationship; new accounts leave it null. All local accounts are visible to whoever can open this OS user's app. Duplicate addresses are allowed because different servers can legitimately use the same email address.

Keep composite account foreign keys and explicit account/mailbox/message selection checks. NativePHP's runtime secret gate remains active. Browser development is allowed only on loopback addresses and loopback hosts; CSRF stays enabled. This is a desktop application, not an anonymously deployable mail web service.

IMAP/SMTP account setup stores explicit TLS/STARTTLS configuration and platform-encrypted passwords atomically, without creating remote mailboxes or performing synchronous provider access. Saved means configured, not connected: connection testing and synchronization follow in the provider milestone. Google authorization is a separate, later flow; never request Google passwords.

## Alternatives Considered

- Hidden automatic login: retains unnecessary users and login/session dependencies.
- Fresh database or deleting old users/accounts: unnecessary data loss.
- Public unauthenticated web endpoints: inappropriate for private local email content.

## Consequences

The OS profile and NativePHP process boundary protect access to the workspace. Application-user isolation tests are replaced with local access and per-mail-account integrity tests. Legacy user records are not deleted. An explicit migration retains legacy IDs; rolling back cannot assign ownership to accounts created after this change.
