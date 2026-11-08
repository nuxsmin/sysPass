# 0010 - Upgrade phpseclib to 3.x and keep roave/security-advisories

Status: Accepted (2026-11-02)

Issue: #1987 (tracking #1977, decision D7)

## Context

PHP 8.5 support requires the dependency set to resolve with security blocking enabled. The locked `phpseclib/phpseclib` 2.0.47 is blocked by the advisory set for the 2.x range, and `roave/security-advisories` conflicts with `phpseclib/phpseclib <3.0.57` as well as with PHP `<8.3.36`. The original plan left phpseclib 2 to 3 out of scope; Phase 0 showed that position cannot hold while `roave` is retained.

sysPass uses phpseclib only for RSA PKI in `CryptPKI`: key generation on first run and PKCS#1 v1.5 decryption of client-encrypted form fields. It does not use X25519, X.509, SSH, or phpseclib's AES implementation. The legacy key formats are a PKCS#1 private key and a PKCS#8 public key consumed by JSEncrypt.

Considered options:

- (a) Upgrade `phpseclib/phpseclib` to `^3.0.57` and keep `roave/security-advisories`.
- (b) Keep phpseclib 2, remove `roave`, and allowlist the phpseclib advisory IDs.
- (c) Keep phpseclib 2, remove `roave`, and disable advisory blocking.

## Decision

Adopt option (a).

1. Set the constraint:

   ```diff
      "require": {
   -    "phpseclib/phpseclib": "^2.0",
   +    "phpseclib/phpseclib": "^3.0.57",
   ```

   Keep `roave/security-advisories` unchanged.

2. Companion changes are owned by other issues: remove `config.platform.php` (#1988), upgrade Guzzle to `^7.15.2` (#1993), implement this migration in #2088 (P2-9), and regenerate the lock (#1996).

3. Migrate the call sites:

   - `lib/SP/Core/Crypt/CryptPKI.php`: import `phpseclib3\Crypt\RSA` and `phpseclib3\Crypt\PublicKeyLoader`; drop the injected `RSA` constructor argument; generate with `RSA::createKey()`; save the public key with `getPublicKey()->toString('PKCS8')` and the private key with `toString('PKCS1')`; decrypt with `PublicKeyLoader::loadPrivateKey(...)->withPadding(RSA::ENCRYPTION_PKCS1)->decrypt($data)`. Catch `RuntimeException`/`LengthException` so invalid input still returns `null` and logs at debug level.
   - `lib/SP/Core/Definitions/CoreDefinitions.php`: remove the `RSA` import and the `new RSA()` constructor argument.
   - `tests/SP/Core/Crypt/CryptPKITest.php`: replace the `RSA` mock with a real key round trip and add an invalid-input case asserting `null`.
   - Add an `instanceof RSA\PrivateKey` guard before `withPadding()` so PHPStan level 6 remains clean.

4. Do not regenerate keys. phpseclib 3.0.57 loads the existing PKCS#1 private and PKCS#8 public keys; existing ciphertexts decrypt with the same result. The 1024-bit key size is unchanged and remains out of scope.

5. Ordering: #1987 decision → #1988 → #1993 → #2088 (P2-9 phpseclib implementation) → #1996 lock regeneration. #2088 must land before #1996 because #1996 owns the final lock. #1987 closes with the decision only.

## Considered options

- **Option (b): keep phpseclib 2, remove `roave`, allowlist the advisories.** Rejected. Any `roave` version conflicts with phpseclib 2, so this requires removing `roave` and allowlisting 9 advisory IDs. It loses roave's package enforcement and PHP floor, retains the 5 advisories applicable to 2.0.47, and keeps the pure-PHP PKCS#1 decryption path on pre-authentication input.
- **Option (c): keep phpseclib 2, remove `roave`, disable blocking.** Rejected for the same reasons as (b), plus loss of composer-level blocking for every package.
- **Option (a): upgrade phpseclib.** Accepted.

## Consequences

- The upgrade expands the original plan's scope (phpseclib 2 to 3 was listed as out of scope).
- phpseclib 3 adds `paragonie/constant_time_encoding` and the `paragonie/random_compat` shim to the lock.
- `decryptRSA` must catch phpseclib 3 exceptions; otherwise pre-authentication invalid payloads would be logged at error level rather than debug.
- Existing stored keys and ciphertexts remain compatible; no re-encryption or migration.
- Blocking-enabled `composer update --dry-run --with-all-dependencies` succeeds only with phpseclib 3, Guzzle 7, and a platform pin of at least 8.3.36; it fails with either dependency alone.

## References

- #1987: decision and evidence (`tests/_output/1987-proposal.md`).
- #1977: PHP 8.5 upgrade tracking issue and plan.
- #1988, #1993, #1996: companion constraint and lock changes.
- #2088: P2-9 implementation of the phpseclib 3 migration.
