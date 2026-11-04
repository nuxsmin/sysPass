<?php
declare(strict_types=1);

$root = dirname(__DIR__, 3);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$candidate = realpath($root . $requestPath);
$staticRoots = [
    $root . DIRECTORY_SEPARATOR . 'public',
    $root . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'web'
        . DIRECTORY_SEPARATOR . 'themes',
];

$isStaticFile = false;

if ($candidate !== false && is_file($candidate)) {
    foreach ($staticRoots as $staticRoot) {
        if ($candidate === $staticRoot || str_starts_with($candidate, $staticRoot . DIRECTORY_SEPARATOR)) {
            $isStaticFile = true;
            break;
        }
    }
}

if ($requestPath !== '/' && $isStaticFile) {
    return false;
}

$_SERVER['SCRIPT_FILENAME'] = $root . '/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';

require $root . '/index.php';
