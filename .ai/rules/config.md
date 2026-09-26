---
paths:
    - 'config/**'
---

# Config

## Keep request recording disabled for private mail

ADR-002 disables Inertia DevTools server recording and client hooks even in local development. The installed v3 defaults record request/response bodies and fail to redact IMAP/SMTP passwords and X-NativePHP-Secret. Do not enable request capture for real mail or credentials; tests must include X-Inertia:true when verifying this boundary.
