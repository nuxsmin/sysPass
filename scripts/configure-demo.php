<?php

declare(strict_types=1);

/**
 * sysPass demo provisioning: writes app/config/config.xml and resets the
 * admin credentials on the configured database. Run inside the app container.
 */

namespace SP\Tools;

use DOMDocument;
use DOMXPath;
use PDO;
use RuntimeException;
use SP\Domain\Core\Crypt\Crypt;
use Throwable;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

function envValue(string $name, string $default): string
{
    $value = getenv($name);

    return $value === false || $value === '' ? $default : $value;
}

try {
    $template = $root . '/tests/res/config/config.xml';
    $configDir = $root . '/app/config';
    $configFile = $configDir . '/config.xml';
    $cacheFile = $root . '/app/cache/config.cache';

    foreach ([$configDir, $root . '/app/temp', $root . '/app/cache'] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Unable to create directory: %s', $dir));
        }
    }

    if (!is_file($template)) {
        throw new RuntimeException(sprintf('Config template not found: %s', $template));
    }

    $appUrl = rtrim(envValue('APP_URL', 'http://localhost:8080'), '/');
    $dbHost = envValue('APP_DB_HOST', 'db');
    $dbPort = envValue('APP_DB_PORT', '3306');
    $dbName = envValue('APP_DB_NAME', 'syspass');
    $dbUser = envValue('APP_DB_USER', 'root');
    $dbPass = envValue('APP_DB_PASS', 'syspass');
    $adminLogin = envValue('ADMIN_LOGIN', 'admin');
    $adminPassword = envValue('ADMIN_PASSWORD', 'admin123');
    $masterPassword = envValue('MASTER_PASSWORD', '12345678900');
    $debugValue = strtolower(envValue('APP_DEBUG', '0'));
    $debug = in_array($debugValue, ['1', 'true', 'yes', 'on'], true) ? '1' : '0';
    $sessionTimeout = envValue('APP_SESSION_TIMEOUT', '300');

    if (!preg_match('/^[A-Za-z0-9_]+$/', $dbName)) {
        throw new RuntimeException('APP_DB_NAME may only contain letters, digits, and underscores');
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    $configSource = is_file($configFile) ? $configFile : $template;

    if (!$dom->load($configSource)) {
        if ($configSource !== $template && $dom->load($template)) {
            fwrite(STDERR, sprintf("Warning: regenerating invalid config from template: %s\n", $configSource));
        } else {
            throw new RuntimeException(sprintf('Unable to load config file: %s', $configSource));
        }
    }

    $xpath = new DOMXPath($dom);
    $set = static function (string $name, string $value) use ($xpath): void {
        $nodes = $xpath->query('/config/' . $name);

        if ($nodes === false || $nodes->length === 0) {
            throw new RuntimeException(sprintf('Missing config field: %s', $name));
        }

        $node = $nodes->item(0);

        if ($node === null) {
            throw new RuntimeException(sprintf('Missing config field: %s', $name));
        }

        $node->nodeValue = $value;
    };

    $set('applicationUrl', $appUrl);
    $set('authBasicAutoLoginEnabled', '0');
    $set('authBasicEnabled', '0');
    $set('dbHost', $dbHost);
    $set('dbName', $dbName);
    $set('dbPass', $dbPass);
    $set('dbPort', $dbPort);
    $set('dbSocket', '');
    $set('dbUser', $dbUser);
    $set('debug', $debug);
    $set('httpsEnabled', '');
    $set('installed', '1');
    $set('maintenance', '0');
    $set('sessionTimeout', $sessionTimeout);
    $set('siteLang', 'en_US');
    $set('siteTheme', 'material-blue');

    $saltNode = $xpath->query('/config/passwordSalt')->item(0);
    $salt = $saltNode?->nodeValue;

    if (!is_string($salt) || $salt === '') {
        throw new RuntimeException('The config template has no passwordSalt');
    }

    $tmpFile = $configFile . '.tmp';

    if ($dom->save($tmpFile) === false) {
        throw new RuntimeException(sprintf('Unable to write config file: %s', $tmpFile));
    }

    chmod($tmpFile, 0640);

    if (!rename($tmpFile, $configFile)) {
        throw new RuntimeException(sprintf('Unable to replace config file: %s', $configFile));
    }

    if (is_file($cacheFile) && !unlink($cacheFile)) {
        throw new RuntimeException(sprintf('Unable to remove config cache: %s', $cacheFile));
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbPort, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $tableCount = (int)$pdo->query(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'Config'"
    )->fetchColumn();

    if ($tableCount === 0) {
        throw new RuntimeException('The Config table does not exist; load schemas/dbstructure.sql first');
    }

    $masterHash = $pdo->query("SELECT `value` FROM `Config` WHERE `parameter` = 'masterPwd'")->fetchColumn();

    if (!is_string($masterHash) || !password_verify($masterPassword, $masterHash)) {
        $stmt = $pdo->prepare("UPDATE `Config` SET `value` = ? WHERE `parameter` = 'masterPwd'");
        $stmt->execute([password_hash($masterPassword, PASSWORD_BCRYPT)]);
        echo "Updated Config.masterPwd\n";
    }

    $userCount = (int)$pdo->query('SELECT COUNT(*) FROM `User` WHERE `id` = 1')->fetchColumn();

    if ($userCount === 0) {
        throw new RuntimeException('The fixture admin user (id = 1) does not exist');
    }

    $crypt = new Crypt();
    $key = $adminPassword . $adminLogin . $salt;
    $securedKey = $crypt->makeSecuredKey($key);
    $encryptedMasterPassword = $crypt->encrypt($masterPassword, $securedKey, $key);

    $stmt = $pdo->prepare(
        'UPDATE `User`
         SET `login` = ?, `pass` = ?, `mPass` = ?, `mKey` = ?, `lastUpdateMPass` = ?,
             `isChangePass` = 0, `isChangedPass` = 0
         WHERE `id` = 1'
    );
    $stmt->execute([
        $adminLogin,
        password_hash($adminPassword, PASSWORD_BCRYPT),
        $encryptedMasterPassword,
        $securedKey,
        time(),
    ]);

    printf("Config: %s\n", $configFile);
    printf("Database: %s:%s/%s\n", $dbHost, $dbPort, $dbName);
    printf("Admin: %s / %s\n", $adminLogin, $adminPassword);
    printf("Master password: %s\n", $masterPassword);
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("Setup failed: %s\n", $e->getMessage()));
    exit(1);
}
