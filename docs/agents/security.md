# Security Guidance

Read this guide before changing security-sensitive behavior. These are contributor requirements, not a claim that every existing path already meets them. Verify the current implementation; accepted ADRs may describe migration targets rather than shipped protections.

## Disclosure

[SECURITY.md](../../SECURITY.md) is the source of truth for vulnerability reporting. Follow its private disclosure process before public disclosure. Keep exploit details, affected secrets and sensitive reproduction data out of public issues, pull requests and command logs. Do not send reports or contact external parties without user authorization.

## Review Scope

Authentication, authorization, cryptography, key handling, sessions, public links and credential export changes require explicit security review by a maintainer before merge. An agent's implementation or self-review does not substitute for that review.

Identify the trust boundaries, authorized actors, sensitive data and affected entry points before editing. Include alternate paths such as account history, attachments, imports, exports and public links where relevant. Flag changes that expand who can access data, where plaintext can appear or how long access remains valid.

## Access and Input Boundaries

- Enforce authorization server-side for each affected operation and resource. UI visibility and possession of an identifier are not permission checks. Preserve the intended access rules across Web, API and CLI adapters.
- Preserve authentication, session expiration and invalidation, CSRF protection and public-link restrictions where applicable. Reject invalid or missing security context rather than falling back to broader access.
- Validate untrusted input at the appropriate boundary. Use parameterized database operations and context-appropriate output escaping; do not treat stored data as inherently safe.
- Review pages, fragments and API responses for unintended secret exposure. Consult [ADR-0011](../adr/0011-keep-server-side-rendering-with-an-auto-escaping-renderer.md) for rendering decisions, verifying which protections are actually implemented rather than assuming auto-escaping is present.

## Secrets and Cryptography

- Use synthetic credentials and keys in tests. Keep real passwords, tokens, keys, session data, backups and decrypted account data out of fixtures, snapshots, logs, exception messages and review artifacts.
- Limit plaintext handling to the authorized operation and intended recipient. Do not move server-side key material into browser payloads or extend its lifetime for convenience.
- Use existing cryptographic abstractions rather than introducing custom primitives or insecure fallbacks. Algorithm, padding, key-size, key-derivation, randomness and key-lifecycle changes need explicit security review and documented compatibility implications.
- For persisted secrets, verify existing keys and ciphertext remain usable, and test malformed or invalid ciphertext rejection without leaking sensitive data. Follow [ADR-0005](../adr/0005-preserve-serialized-payload-compatibility.md) for serialized payloads and [ADR-0010](../adr/0010-upgrade-phpseclib-to-3.md) when changing RSA dependencies or key-format handling.
- Do not bypass dependency advisory blocking or weaken security checks merely to make builds or tests pass. Surface the conflict for maintainer review.

## Tests and Handoff

Follow the [red-green testing workflow](testing.md). Add focused negative tests appropriate to the change: unauthenticated and unauthorized requests, cross-user or cross-group resource access, invalid or expired security context, malformed input and unintended secret disclosure. Include successful authorized behavior to detect accidental denial of legitimate access.

Exercise the affected public boundaries, not only internal helpers. Preserve regression coverage for stored-data compatibility and test failure behavior as well as round trips when changing cryptography.

In the handoff, record affected trust boundaries, security assumptions, compatibility implications, tests and their actual outcomes, unresolved risks and maintainer review still required. A passing test suite is evidence, not proof of security; do not claim security approval when it has not occurred.
