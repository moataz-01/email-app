---
paths:
    - 'resources/js/**'
---

# Js

## Treat cached mail content as untrusted

Follow ADR-003: initial mail reading renders escaped plain text only. Never send raw email HTML or stored authentication secrets in Inertia props. Remote images and attachment opening remain disabled until a tested sanitizer/isolation and file-handling policy exists. Keep backend links typed through Wayfinder.
