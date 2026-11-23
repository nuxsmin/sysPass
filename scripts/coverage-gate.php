<?php

declare(strict_types=1);

/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Report and enforce per-layer statement coverage from a PHPUnit Clover report.
 *
 * Usage:
 *   php scripts/coverage-gate.php [--clover=PATH] [--gate]
 *
 * Without --gate the report is printed and the script exits 0.
 * With --gate every bucket with statements must meet its threshold from
 * tests/coverage-thresholds.php, otherwise the script exits 1.
 */

const REPO_ROOT = __DIR__ . '/..';

/**
 * @return array<string, string> Bucket label => path regular expression.
 */
function coverageBuckets(): array
{
    return [
        'Domain\*\UseCases' => '#^/lib/SP/Domain/[^/]+/UseCases/#',
        'Domain\*\Services' => '#^/lib/SP/Domain/[^/]+/Services/#',
        'Domain (all)' => '#^/lib/SP/Domain/#',
        'Core' => '#^/lib/SP/Core/#',
        'Infrastructure' => '#^/lib/SP/Infrastructure/#',
        'Modules web' => '#^/app/modules/web/#',
        'Modules api' => '#^/app/modules/api/#',
        'Modules cli' => '#^/app/modules/cli/#',
        'Total' => '#^#',
    ];
}

/**
 * @return array<string, float> Bucket label => minimum statement coverage percentage.
 */
function coverageThresholds(): array
{
    $file = REPO_ROOT . '/tests/coverage-thresholds.php';

    if (!is_file($file)) {
        return [];
    }

    /** @var array<string, float> $thresholds */
    $thresholds = require $file;

    return $thresholds;
}

function relativePath(string $file): string
{
    $root = realpath(REPO_ROOT);
    $real = realpath($file);

    if ($root === false || $real === false) {
        return $file;
    }

    return str_starts_with($real, $root) ? substr($real, strlen($root)) : $real;
}

/**
 * @return array<string, array{statements: int, covered: int}>|null
 */
function readCoverage(string $cloverPath): ?array
{
    if (!is_file($cloverPath)) {
        fwrite(STDERR, sprintf("Coverage report not found: %s\n", $cloverPath));

        return null;
    }

    $xml = simplexml_load_file($cloverPath);

    if ($xml === false) {
        fwrite(STDERR, sprintf("Cannot parse coverage report: %s\n", $cloverPath));

        return null;
    }

    $buckets = coverageBuckets();

    /** @var array<string, array{statements: int, covered: int}> $totals */
    $totals = array_fill_keys(array_keys($buckets), ['statements' => 0, 'covered' => 0]);

    foreach ($xml->project->package as $package) {
        foreach ($package->file as $file) {
            $path = relativePath((string)$file['name']);
            $metrics = $file->metrics;
            $statements = (int)$metrics['statements'];
            $covered = (int)$metrics['coveredstatements'];

            foreach ($buckets as $label => $pattern) {
                if (preg_match($pattern, $path) === 1) {
                    $totals[$label]['statements'] += $statements;
                    $totals[$label]['covered'] += $covered;
                }
            }
        }
    }

    return $totals;
}

/**
 * @param array<string, array{statements: int, covered: int}> $totals
 * @param array<string, float> $thresholds
 */
function printReport(array $totals, array $thresholds): void
{
    printf("%-22s %10s %10s %10s %10s\n", 'Layer', 'Statements', 'Covered', 'Percent', 'Minimum');

    foreach ($totals as $label => $data) {
        $percent = $data['statements'] > 0
            ? ($data['covered'] / $data['statements']) * 100
            : 0.0;
        $minimum = $thresholds[$label] ?? null;

        printf(
            "%-22s %10d %10d %9.2f%% %9s\n",
            $label,
            $data['statements'],
            $data['covered'],
            $percent,
            $minimum === null ? '-' : sprintf('%.2f%%', $minimum)
        );
    }
}

/**
 * @param array<string, array{statements: int, covered: int}> $totals
 * @param array<string, float> $thresholds
 */
function passesGate(array $totals, array $thresholds): bool
{
    $passed = true;

    foreach ($totals as $label => $data) {
        if ($data['statements'] === 0 || !isset($thresholds[$label])) {
            continue;
        }

        $percent = ($data['covered'] / $data['statements']) * 100;

        if ($percent + 0.0001 < $thresholds[$label]) {
            $passed = false;
            fwrite(
                STDERR,
                sprintf(
                    "Coverage gate failed: %s is %.2f%% (minimum %.2f%%)\n",
                    $label,
                    $percent,
                    $thresholds[$label]
                )
            );
        }
    }

    return $passed;
}

$options = getopt('', ['clover::', 'gate']);
$cloverPath = $options['clover'] ?? REPO_ROOT . '/tests/_output/coverage-clover.xml';
$gate = array_key_exists('gate', $options);

if (!is_string($cloverPath)) {
    fwrite(STDERR, "Invalid --clover value\n");
    exit(2);
}

$totals = readCoverage($cloverPath);

if ($totals === null) {
    exit(2);
}

$thresholds = coverageThresholds();
printReport($totals, $thresholds);

if ($gate && !passesGate($totals, $thresholds)) {
    exit(1);
}
