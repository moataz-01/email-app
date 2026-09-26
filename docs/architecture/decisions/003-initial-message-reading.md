# ADR-003: Plain-text reading before HTML rendering

## Status

Accepted

## Context

The starter kit has no HTML sanitizer or isolated email renderer. Email HTML and attachment names are untrusted inputs; a desktop browser context raises the consequence of script execution.

## Decision

The initial reading pane displays cached plain text through React's escaped text rendering. Raw HTML is hidden from model serialization and is not sent to the reading pane. HTML-only messages show an explicit unavailable-body state. No remote images, embedded frames, clickable untrusted URLs, or attachment file operations are enabled in this increment.

## Alternatives Considered

- Render raw HTML directly: rejected due to script execution and tracking risks.
- Build a sanitizer with regular expressions: rejected; real email markup needs a maintained parser/sanitizer and isolated rendering policy.

## Consequences

Plain-text messages are readable safely now. Rich HTML, CID resolution, attachments, external link handling, and privacy controls remain later work, with their own security tests and dependency review.
