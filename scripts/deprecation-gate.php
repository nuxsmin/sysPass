#!/usr/bin/env php
<?php

declare(strict_types=1);

const DEPRECATION_GATE_OK = 0;
const DEPRECATION_GATE_VIOLATION = 1;
const DEPRECATION_GATE_USAGE = 2;

$root = dirname(__DIR__);
$options = getopt('', ['suite:', 'events:', 'allowlist::', 'command::']);

$suite = $options['suite'] ?? null;
$eventsPath = $options['events'] ?? null;
$allowlistPath = $options['allowlist'] ?? $root . '/tests/deprecation-vendor-allowlist.php';
$command = $options['command'] ?? 'n/a';

if (!is_string($suite) || !is_string($eventsPath)) {
    fwrite(STDERR, "Usage: deprecation-gate.php --suite=<name> --events=<phpunit-events.txt> [--allowlist=<file>] [--command=<command>]\n");
    exit(DEPRECATION_GATE_USAGE);
}

if (!is_file($eventsPath)) {
    printf("Deprecation gate: %s\n", $suite);
    printf("PHP: %s\n", PHP_VERSION);
    printf("Command: %s\n", $command);
    printf("Dependency lock: sha256:%s composer.lock\n", hash_file('sha256', $root . '/composer.lock'));
    fwrite(STDERR, sprintf("deprecation-gate: event log not found: %s\n", $eventsPath));
    exit(DEPRECATION_GATE_USAGE);
}

if (!is_file($allowlistPath)) {
    fwrite(STDERR, sprintf("deprecation-gate: allowlist not found: %s\n", $allowlistPath));
    exit(DEPRECATION_GATE_USAGE);
}

$allowlist = require $allowlistPath;

if (!is_array($allowlist)) {
    fwrite(STDERR, "deprecation-gate: allowlist must return an array\n");
    exit(DEPRECATION_GATE_USAGE);
}

$lockPath = $root . '/composer.lock';
$lock = json_decode((string)file_get_contents($lockPath), true, 512, JSON_THROW_ON_ERROR);
$lockedVersions = [];

foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
    if (isset($package['name'], $package['version'])) {
        $lockedVersions[strtolower($package['name'])] = ltrim((string)$package['version'], 'v');
    }
}

$events = readDeprecationEvents($eventsPath);
$firstParty = [];
$allowlisted = [];
$unexplained = [];
$seenPackages = [];
$ignored = 0;

foreach ($events as $event) {
    $package = packageFor($event['file'], $event['message']);

    if ($package !== null) {
        $seenPackages[$package] = true;
        $reason = allowlistViolation($package, $event['message'], $allowlist, $lockedVersions);

        if ($reason === null) {
            $allowlisted[$package] = ($allowlisted[$package] ?? 0) + 1;
        } else {
            $unexplained[] = $event + ['package' => $package, 'reason' => $reason];
        }

        continue;
    }

    if (isFirstParty($event['file'], $root)) {
        $firstParty[] = $event;
    } else {
        $ignored++;
    }
}

printf("Deprecation gate: %s\n", $suite);
printf("PHP: %s\n", PHP_VERSION);
printf("Command: %s\n", $command);
printf("Dependency lock: sha256:%s composer.lock\n", hash_file('sha256', $lockPath));
printf("Deprecation events: %d\n", count($events));
printf("First-party events: %d\n", count($firstParty));
printf("Vendor allowlisted events: %d\n", array_sum($allowlisted));
printf("Vendor unexplained events: %d\n", count($unexplained));
printf("Non-gated events: %d\n", $ignored);

foreach ($allowlisted as $package => $count) {
    printf("  allowlisted %s: %d\n", $package, $count);
}

$unusedAllowlist = array_diff(array_keys($allowlist), array_keys($seenPackages));

if ($unusedAllowlist !== []) {
    printf("Allowlist entries without events: %s\n", implode(', ', $unusedAllowlist));
}

if ($firstParty !== []) {
    printEvents('First-party deprecations:', $firstParty);
}

if ($unexplained !== []) {
    printEvents('Unexplained vendor deprecations:', $unexplained);
}

if ($firstParty !== [] || $unexplained !== []) {
    exit(DEPRECATION_GATE_VIOLATION);
}

exit(DEPRECATION_GATE_OK);

function readDeprecationEvents(string $path): array
{
    $lines = file($path, FILE_IGNORE_NEW_LINES);

    if ($lines === false) {
        return [];
    }

    $events = [];

    for ($index = 0, $total = count($lines); $index < $total; $index++) {
        $line = $lines[$index];

        if (!preg_match('/^(?:Test|TestRunner|TestSuite) Triggered (?:PHP |PHPUnit )?Deprecation\b.*\bin (.+):(\d+)$/', $line, $matches)) {
            continue;
        }

        $events[] = [
            'event' => $line,
            'test' => testId($line),
            'file' => $matches[1],
            'line' => (int)$matches[2],
            'message' => $lines[$index + 1] ?? '',
        ];

        $index++;
    }

    return $events;
}

function testId(string $event): string
{
    if (preg_match('/^[^(]+\(([^,)]*)/', $event, $matches)) {
        return trim($matches[1]);
    }

    return '';
}

function packageFor(string $file, string $message): ?string
{
    if (preg_match('/^Since ([a-z0-9_.-]+\/[a-z0-9_.-]+) [^:]+:/i', $message, $matches)) {
        return strtolower($matches[1]);
    }

    $normalized = str_replace('\\', '/', $file);

    if (preg_match('#/vendor/([^/]+/[^/]+)/#', $normalized, $matches)) {
        return strtolower($matches[1]);
    }

    return null;
}

function allowlistViolation(string $package, string $message, array $allowlist, array $lockedVersions): ?string
{
    if (!isset($allowlist[$package])) {
        return 'package is not in the vendor deprecation allowlist';
    }

    $expectedVersion = ltrim((string)($allowlist[$package]['version'] ?? ''), 'v');
    $lockedVersion = $lockedVersions[$package] ?? null;

    if ($lockedVersion === null) {
        return 'package is not present in composer.lock';
    }

    if ($expectedVersion !== $lockedVersion) {
        return sprintf(
            'allowlist version %s does not match locked version %s',
            $expectedVersion,
            $lockedVersion
        );
    }

    foreach ($allowlist[$package]['patterns'] ?? [] as $pattern) {
        if (preg_match($pattern, $message) === 1) {
            return null;
        }
    }

    return 'message does not match an allowlist pattern';
}

function isFirstParty(string $file, string $root): bool
{
    $normalized = str_replace('\\', '/', $file);

    if (str_contains($normalized, '/vendor/')) {
        return false;
    }

    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    $relative = str_starts_with($normalized, $root) ? substr($normalized, strlen($root)) : $normalized;

    if (!str_ends_with($relative, '.php')) {
        return false;
    }

    if (str_starts_with($relative, 'app/modules/web/Html/Assets/')) {
        return false;
    }

    if ($relative === 'lib/SP/Domain/Config/Adapters/ConfigData.php') {
        return false;
    }

    return preg_match(
        '#^(?:lib/SP/|lib/Base\.php$|lib/BaseFunctions\.php$|app/modules/(?:web|api|cli)/|tests/SP/|index\.php$|api\.php$|cli\.php$)#',
        $relative
    ) === 1;
}

function printEvents(string $title, array $events): void
{
    printf("\n%s\n", $title);

    foreach (array_slice($events, 0, 100) as $index => $event) {
        $location = sprintf('%s:%d', $event['file'], $event['line']);
        $package = isset($event['package']) ? sprintf('[%s] ', $event['package']) : '';
        $reason = isset($event['reason']) ? sprintf(' (%s)', $event['reason']) : '';

        printf("%d) %s%s%s\n", $index + 1, $package, $event['test'] ?: 'unknown test', $reason);
        printf("   %s\n", $location);
        printf("   %s\n", $event['message']);
    }

    if (count($events) > 100) {
        printf("... %d more events, see the full event log\n", count($events) - 100);
    }
}
