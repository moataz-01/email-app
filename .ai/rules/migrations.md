---
paths:
    - 'database/migrations/**'
---

# Migrations

## Preserve opaque mail identities across database engines

NativePHP uses SQLite but browser development uses MySQL. Provider identifiers are byte-sensitive, including case and trailing spaces: keep MySQL/MariaDB provider_id columns binary and SQLite binary comparisons. Maintain the reverse mailbox_messages message lookup index and composite account ownership foreign keys; see ADR-001.
