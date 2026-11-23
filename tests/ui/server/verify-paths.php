<?php
declare(strict_types=1);

use SP\Core\Bootstrap\Path;
use SP\Core\Definitions\CoreDefinitions;

require dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 3));
}

if (!defined('APP_PATH')) {
    define('APP_PATH', APP_ROOT . '/app');
}

require dirname(__DIR__, 3) . '/lib/BaseFunctions.php';

$errors = [];
$runRoot = getenv('SP_UI_RUN_ROOT') ?: '';
$runId = getenv('SP_UI_RUN_ID') ?: '';
$repoRoot = dirname(__DIR__, 3);

$fail = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

$canonicalRunRoot = realpath($runRoot);

if ($canonicalRunRoot === false || $canonicalRunRoot !== $runRoot) {
    $fail('run root is not a canonical existing path inside PHP');
}

$isInsideRunRoot = static function (string $path) use ($runRoot): bool {
    $canonical = realpath($path);

    if ($canonical === false) {
        $parent = realpath(dirname($path));
        $canonical = $parent !== false ? $parent . DIRECTORY_SEPARATOR . basename($path) : false;
    }

    return $canonical !== false
        && ($canonical === $runRoot || str_starts_with($canonical, $runRoot . DIRECTORY_SEPARATOR));
};

$checkOwnedPath = static function (string $label, string $path) use ($isInsideRunRoot, $fail): void {
    if ($path === '' || !$isInsideRunRoot($path)) {
        $fail(sprintf('%s does not resolve inside the run root', $label));
    }
};

$checkMarker = static function (string $label, string $directory) use ($runId, $fail): void {
    $marker = $directory . DIRECTORY_SEPARATOR . '.sp-ui-owner';

    if (!is_file($marker) || trim((string)file_get_contents($marker)) !== $runId) {
        $fail(sprintf('%s is not bound to this run', $label));
    }
};

$configPath = getenv('CONFIG_PATH') ?: '';
$configFile = getenv('CONFIG_FILE') ?: '';
$cachePath = getenv('CACHE_PATH') ?: '';
$logFile = getenv('LOG_FILE') ?: '';
$sessionPath = (string)ini_get('session.save_path');

$checkOwnedPath('CONFIG_PATH', $configPath);
$checkOwnedPath('CONFIG_FILE', $configFile);
$checkOwnedPath('CACHE_PATH', $cachePath);
$checkOwnedPath('LOG_FILE', $logFile);
$checkOwnedPath('session.save_path', $sessionPath);
$checkMarker('CONFIG_PATH', $configPath);
$checkMarker('CACHE_PATH', $cachePath);
$checkMarker('session.save_path', $sessionPath);

$method = new ReflectionMethod(CoreDefinitions::class, 'getPaths');
$method->setAccessible(true);
$paths = [];

foreach ($method->invoke(null, $repoRoot) as [$path, $value]) {
    $paths[$path->name] = $value;
}

$expectedAppCache = $repoRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'cache';
$expectedAppTemp = $repoRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'temp';

if (($paths[Path::CACHE->name] ?? '') !== $expectedAppCache) {
    $fail('Path::CACHE is not the application cache path');
} else {
    $checkMarker('Path::CACHE', $expectedAppCache);
}

if (($paths[Path::TMP->name] ?? '') !== $expectedAppTemp) {
    $fail('Path::TMP is not the application temp path');
} else {
    $checkMarker('Path::TMP', $expectedAppTemp);
}

if (($paths[Path::CONFIG->name] ?? '') !== $configPath) {
    $fail('Path::CONFIG does not match CONFIG_PATH');
}

if (($paths[Path::CONFIG_FILE->name] ?? '') !== $configFile) {
    $fail('Path::CONFIG_FILE does not match CONFIG_FILE');
}

if (($paths[Path::LOG_FILE->name] ?? '') !== $logFile) {
    $fail('Path::LOG_FILE does not match LOG_FILE');
}

$result = [
    'ok' => $errors === [],
    'errors' => $errors,
    'paths' => [
        'config' => $configPath,
        'config_file' => $configFile,
        'di_cache' => $cachePath,
        'log_file' => $logFile,
        'session' => $sessionPath,
        'app_cache' => $paths[Path::CACHE->name] ?? '',
        'app_temp' => $paths[Path::TMP->name] ?? '',
    ],
];

echo json_encode($result, JSON_THROW_ON_ERROR), PHP_EOL;

exit($result['ok'] ? 0 : 1);
