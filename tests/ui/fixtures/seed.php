<?php

declare(strict_types=1);

/**
 * Generate and verify the isolated UI smoke fixture.
 *
 * Usage (inside the runner-owned PHP container):
 *   php tests/ui/fixtures/seed.php --reset
 *   php tests/ui/fixtures/seed.php --verify
 *
 * The fixture contains only synthetic data. Credentials live in
 * tests/ui/fixtures/e2e-fixture.json and are committed for reproducibility.
 * Authentication state is never written here; it stays in the Playwright run
 * and the runner-owned session directory.
 */

use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Crypt\Ports\HashService;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserService;

$root = dirname(__DIR__, 3);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', $root);
}

if (!defined('APP_MODULE')) {
    define('APP_MODULE', 'cli');
}

/** @var Psr\Container\ContainerInterface $container */
$container = require $root . '/lib/Base.php';

$options = getopt('', ['reset', 'verify']);
$reset = array_key_exists('reset', $options);
$verify = array_key_exists('verify', $options);

if ($reset === $verify) {
    fwrite(STDERR, "Specify exactly one of --reset or --verify\n");
    exit(2);
}

/**
 * @return array<string, mixed>
 */
function fixtureData(): array
{
    $path = getenv('SP_UI_FIXTURE_FILE');
    $path = is_string($path) && $path !== ''
        ? $path
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR . 'e2e-fixture.json';

    $contents = file_get_contents($path);

    if ($contents === false) {
        throw new RuntimeException('Unable to read the E2E fixture definition');
    }

    $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
        throw new RuntimeException('E2E fixture definition is not an object');
    }

    return $data;
}

function environmentValue(string $name): string
{
    $value = getenv($name);

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Missing required environment variable %s', $name));
    }

    return $value;
}

function database(): PDO
{
    $host = environmentValue('DATABASE_HOST');
    $name = environmentValue('DATABASE_NAME');
    $user = environmentValue('DATABASE_USER');
    $password = environmentValue('DATABASE_PASSWORD');

    return new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $host, $name),
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

/**
 * @param array<string, mixed> $fixture
 */
function fixtureId(array $fixture, string $group, string $key): int
{
    $value = $fixture['ids'][$group][$key] ?? null;

    if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
        throw new RuntimeException(sprintf('Fixture id %s.%s is missing', $group, $key));
    }

    return (int)$value;
}

/**
 * @param array<string, mixed> $fixture
 */
function fixtureName(array $fixture, string $key): string
{
    $value = $fixture['names'][$key] ?? null;

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Fixture name %s is missing', $key));
    }

    return $value;
}

/**
 * @param array<string, mixed> $fixture
 */
function fixtureCredential(array $fixture, string $account, string $key): string
{
    $value = $fixture['credentials'][$account][$key] ?? null;

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Fixture credential %s.%s is missing', $account, $key));
    }

    return $value;
}

/**
 * @param array<string, mixed> $fixture
 */
function fixtureContent(array $fixture, string $key): string
{
    $value = $fixture['content'][$key] ?? null;

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Fixture content %s is missing', $key));
    }

    return $value;
}

function itemHash(string $name): string
{
    return sha1(strtolower(str_replace(
        ['.', ' ', '_', ', ', '-', ';', '\'', '"', ':', '(', ')', '|', '/'],
        '',
        $name
    )));
}

/**
 * @param array<string, mixed> $fixture
 */
function configureApplication(ConfigFileService $configFileService): void
{
    $config = $configFileService->getConfigData()
        ->setInstalled(true)
        ->setSiteLang('en_US')
        ->setFilesEnabled(true)
        ->setCheckUpdates(false)
        ->setCheckNotices(false)
        ->setDebug(false)
        ->setDemoEnabled(false)
        ->setAuthBasicAutoLoginEnabled(false);

    $configFileService->save($config);
}

/**
 * @param array<string, mixed> $fixture
 */
function resetFixture(
    PDO                   $pdo,
    ConfigFileService     $configFileService,
    HashService           $hashService,
    CryptPKIHandler       $cryptPKIHandler,
    UserMasterPassService $userMasterPassService,
    AccountCryptService   $accountCryptService,
    array                 $fixture
): void {
    $parentId = fixtureId($fixture, 'accounts', 'parent');
    $childId = fixtureId($fixture, 'accounts', 'child');
    $fileId = fixtureId($fixture, 'files', 'childDocument');
    $clientId = fixtureId($fixture, 'clients', 'primary');
    $categoryId = fixtureId($fixture, 'categories', 'primary');
    $adminId = fixtureId($fixture, 'users', 'admin');
    $demoId = fixtureId($fixture, 'users', 'demo');
    $demoGroupId = fixtureId($fixture, 'groups', 'demo');
    $demoProfileId = fixtureId($fixture, 'profiles', 'demo');
    $restrictedId = fixtureId($fixture, 'users', 'restricted');
    $restrictedGroupId = fixtureId($fixture, 'groups', 'restricted');
    $restrictedProfileId = fixtureId($fixture, 'profiles', 'restricted');

    $masterPassword = fixtureCredential($fixture, 'admin', 'masterPassword');
    $demoPassword = fixtureCredential($fixture, 'demo', 'password');
    $demoLogin = fixtureCredential($fixture, 'demo', 'login');
    $restrictedPassword = fixtureCredential($fixture, 'restricted', 'password');
    $restrictedLogin = fixtureCredential($fixture, 'restricted', 'login');
    $clientName = fixtureName($fixture, 'client');
    $categoryName = fixtureName($fixture, 'category');
    $parentName = fixtureName($fixture, 'parentAccount');
    $childName = fixtureName($fixture, 'childAccount');
    $fileName = fixtureName($fixture, 'childFile');
    $parentPassword = fixtureContent($fixture, 'parentPassword');
    $childPassword = fixtureContent($fixture, 'childPassword');
    $fileText = fixtureContent($fixture, 'childFileText');

    $pdo->beginTransaction();

    try {
        $pdo->prepare('DELETE FROM AccountFile WHERE id = :fileId OR accountId IN (:fileParentId, :fileChildId)')
            ->execute(['fileId' => $fileId, 'fileParentId' => $parentId, 'fileChildId' => $childId]);
        $pdo->prepare('DELETE FROM AccountHistory WHERE accountId IN (:historyParentId, :historyChildId)')
            ->execute(['historyParentId' => $parentId, 'historyChildId' => $childId]);
        $pdo->prepare(
            'DELETE FROM Account
             WHERE id IN (:accountParentId, :accountChildId)
                OR parentId IN (:childParentId, :childChildId)'
        )->execute([
            'accountParentId' => $parentId,
            'accountChildId' => $childId,
            'childParentId' => $parentId,
            'childChildId' => $childId,
        ]);
        $pdo->prepare('DELETE FROM Client WHERE id = :clientId')
            ->execute(['clientId' => $clientId]);
        $pdo->prepare('DELETE FROM Category WHERE id = :categoryId')
            ->execute(['categoryId' => $categoryId]);
        $pdo->prepare('DELETE FROM Notification WHERE userId = :userId')
            ->execute(['userId' => $demoId]);
        $pdo->prepare('DELETE FROM User WHERE id = :demoId OR login = :demoLogin')
            ->execute(['demoId' => $demoId, 'demoLogin' => $demoLogin]);
        $pdo->prepare('DELETE FROM AccountToUserGroup WHERE userGroupId = :groupId')
            ->execute(['groupId' => $restrictedGroupId]);
        $pdo->prepare('DELETE FROM User WHERE id = :restrictedId OR login = :restrictedLogin')
            ->execute([
                'restrictedId' => $restrictedId,
                'restrictedLogin' => $restrictedLogin,
            ]);
        $pdo->prepare('DELETE FROM UserGroup WHERE id = :groupId')
            ->execute(['groupId' => $demoGroupId]);
        $pdo->prepare('DELETE FROM UserGroup WHERE id = :groupId')
            ->execute(['groupId' => $restrictedGroupId]);
        $pdo->prepare('DELETE FROM UserProfile WHERE id = :profileId')
            ->execute(['profileId' => $demoProfileId]);
        $pdo->prepare('DELETE FROM UserProfile WHERE id = :profileId')
            ->execute(['profileId' => $restrictedProfileId]);

        configureApplication($configFileService);
        $cryptPKIHandler->createKeys();

        $adminProfile = $pdo->prepare('SELECT profile FROM UserProfile WHERE id = :id');
        $adminProfile->execute(['id' => 1]);
        $profileJson = $adminProfile->fetchColumn();

        if (!is_string($profileJson) || $profileJson === '') {
            throw new RuntimeException('The installed admin profile is missing');
        }

        $pdo->prepare('INSERT INTO UserGroup (id, name, description) VALUES (:id, :name, :description)')
            ->execute([
                'id' => $demoGroupId,
                'name' => fixtureName($fixture, 'demoGroup'),
                'description' => 'Synthetic UI smoke group',
            ]);

        $pdo->prepare('INSERT INTO UserProfile (id, name, profile) VALUES (:id, :name, :profile)')
            ->execute([
                'id' => $demoProfileId,
                'name' => fixtureName($fixture, 'demoProfile'),
                'profile' => $profileJson,
            ]);

        $preferencesJson = (new UserPreferences([
            'lang' => 'en_US',
            'resultsPerPage' => 10,
            'checkNotifications' => true,
        ]))->toJson();

        $updatePreferences = $pdo->prepare('UPDATE User SET preferences = :preferences WHERE id = :id');
        $updatePreferences->bindValue(':preferences', $preferencesJson, PDO::PARAM_LOB);
        $updatePreferences->bindValue(':id', $adminId, PDO::PARAM_INT);
        $updatePreferences->execute();

        $demoMasterPass = $userMasterPassService->create($masterPassword, $demoLogin, $demoPassword);

        $pdo->prepare(
            'INSERT INTO User
                (id, name, userGroupId, login, ssoLogin, pass, mPass, mKey, email, notes, loginCount,
                 userProfileId, lastLogin, lastUpdate, lastUpdateMPass, isAdminApp, isAdminAcc, isLdap,
                 isDisabled, hashSalt, isMigrate, isChangePass, isChangedPass, preferences)
             VALUES
                (:id, :name, :userGroupId, :login, NULL, :pass, :mPass, :mKey, NULL, NULL, 0,
                 :userProfileId, NULL, NOW(), :lastUpdateMPass, 0, 0, 0, 0, \'\', 0, 0, 0, NULL)'
        )->execute([
            'id' => $demoId,
            'name' => fixtureName($fixture, 'demoProfile'),
            'userGroupId' => $demoGroupId,
            'login' => $demoLogin,
            'pass' => $hashService->hashKey($demoPassword),
            'mPass' => $demoMasterPass->getCryptMasterPass(),
            'mKey' => $demoMasterPass->getCryptSecuredKey(),
            'userProfileId' => $demoProfileId,
            'lastUpdateMPass' => time(),
        ]);

        $updatePreferences->bindValue(':preferences', $preferencesJson, PDO::PARAM_LOB);
        $updatePreferences->bindValue(':id', $demoId, PDO::PARAM_INT);
        $updatePreferences->execute();

        $pdo->prepare('INSERT INTO UserGroup (id, name, description) VALUES (:id, :name, :description)')
            ->execute([
                'id' => $restrictedGroupId,
                'name' => fixtureName($fixture, 'restrictedGroup'),
                'description' => 'Synthetic view-only UI discovery group',
            ]);

        $restrictedProfile = (new ProfileData())
            ->setAccView(true)
            ->toJson();

        $pdo->prepare('INSERT INTO UserProfile (id, name, profile) VALUES (:id, :name, :profile)')
            ->execute([
                'id' => $restrictedProfileId,
                'name' => fixtureName($fixture, 'restrictedProfile'),
                'profile' => $restrictedProfile,
            ]);

        $restrictedMasterPass = $userMasterPassService->create(
            $masterPassword,
            $restrictedLogin,
            $restrictedPassword
        );

        $pdo->prepare(
            'INSERT INTO User
                (id, name, userGroupId, login, ssoLogin, pass, mPass, mKey, email, notes, loginCount,
                 userProfileId, lastLogin, lastUpdate, lastUpdateMPass, isAdminApp, isAdminAcc, isLdap,
                 isDisabled, hashSalt, isMigrate, isChangePass, isChangedPass, preferences)
             VALUES
                (:id, :name, :userGroupId, :login, NULL, :pass, :mPass, :mKey, NULL, NULL, 0,
                 :userProfileId, NULL, NOW(), :lastUpdateMPass, 0, 0, 0, 0, \'\', 0, 0, 0, NULL)'
        )->execute([
            'id' => $restrictedId,
            'name' => fixtureName($fixture, 'restrictedProfile'),
            'userGroupId' => $restrictedGroupId,
            'login' => $restrictedLogin,
            'pass' => $hashService->hashKey($restrictedPassword),
            'mPass' => $restrictedMasterPass->getCryptMasterPass(),
            'mKey' => $restrictedMasterPass->getCryptSecuredKey(),
            'userProfileId' => $restrictedProfileId,
            'lastUpdateMPass' => time(),
        ]);

        $updatePreferences->bindValue(':preferences', $preferencesJson, PDO::PARAM_LOB);
        $updatePreferences->bindValue(':id', $restrictedId, PDO::PARAM_INT);
        $updatePreferences->execute();

        $pdo->prepare(
            'INSERT INTO Client (id, name, hash, description, isGlobal)
             VALUES (:id, :name, :hash, :description, 1)'
        )->execute([
            'id' => $clientId,
            'name' => $clientName,
            'hash' => itemHash($clientName),
            'description' => 'Synthetic UI smoke client',
        ]);

        $pdo->prepare(
            'INSERT INTO Category (id, name, description, hash)
             VALUES (:id, :name, :description, :hash)'
        )->execute([
            'id' => $categoryId,
            'name' => $categoryName,
            'description' => 'Synthetic UI smoke category',
            'hash' => itemHash($categoryName),
        ]);

        $parentPasswordData = $accountCryptService->getPasswordEncrypted($parentPassword, $masterPassword);
        $childPasswordData = $accountCryptService->getPasswordEncrypted($childPassword, $masterPassword);
        $dateAdd = gmdate('Y-m-d H:i:s');
        $passDate = time();

        $insertAccount = $pdo->prepare(
            'INSERT INTO Account
                (id, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`,
                 notes, countView, countDecrypt, dateAdd, dateEdit, otherUserGroupEdit, otherUserEdit,
                 isPrivate, isPrivateGroup, passDate, passDateChange, parentId)
             VALUES
                (:id, :userGroupId, :userId, :userEditId, :clientId, :name, :categoryId, :login, :url, :pass, :key,
                 :notes, 0, 0, :dateAdd, :dateEdit, 0, 0, 0, 0, :passDate, :passDateChange, :parentId)'
        );

        $insertAccount->execute([
            'id' => $parentId,
            'userGroupId' => 1,
            'userId' => $adminId,
            'userEditId' => $adminId,
            'clientId' => $clientId,
            'name' => $parentName,
            'categoryId' => $categoryId,
            'login' => fixtureContent($fixture, 'parentLogin'),
            'url' => 'https://e2e.invalid/parent',
            'pass' => $parentPasswordData->getPass(),
            'key' => $parentPasswordData->getKey(),
            'notes' => 'Synthetic parent account for the UI smoke fixture',
            'dateAdd' => $dateAdd,
            'dateEdit' => $dateAdd,
            'passDate' => $passDate,
            'passDateChange' => $passDate,
            'parentId' => null,
        ]);

        $insertAccount->execute([
            'id' => $childId,
            'userGroupId' => 1,
            'userId' => $adminId,
            'userEditId' => $adminId,
            'clientId' => $clientId,
            'name' => $childName,
            'categoryId' => $categoryId,
            'login' => fixtureContent($fixture, 'childLogin'),
            'url' => 'https://e2e.invalid/child',
            'pass' => $childPasswordData->getPass(),
            'key' => $childPasswordData->getKey(),
            'notes' => 'Synthetic child account with a synthetic attachment',
            'dateAdd' => $dateAdd,
            'dateEdit' => $dateAdd,
            'passDate' => $passDate,
            'passDateChange' => $passDate,
            'parentId' => $parentId,
        ]);

        $pdo->prepare(
            'INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit)
             VALUES (:accountId, :userGroupId, 0)'
        )->execute([
            'accountId' => $parentId,
            'userGroupId' => $restrictedGroupId,
        ]);
        $pdo->prepare(
            'INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit)
             VALUES (:accountId, :userGroupId, 0)'
        )->execute([
            'accountId' => $childId,
            'userGroupId' => $restrictedGroupId,
        ]);

        $insertFile = $pdo->prepare(
            'INSERT INTO AccountFile (id, accountId, name, type, size, content, extension, thumb)
             VALUES (:id, :accountId, :name, :type, :size, :content, :extension, :thumb)'
        );
        $insertFile->bindValue(':id', $fileId, PDO::PARAM_INT);
        $insertFile->bindValue(':accountId', $childId, PDO::PARAM_INT);
        $insertFile->bindValue(':name', $fileName);
        $insertFile->bindValue(':type', 'text/plain');
        $insertFile->bindValue(':size', strlen($fileText), PDO::PARAM_INT);
        $insertFile->bindValue(':content', $fileText, PDO::PARAM_LOB);
        $insertFile->bindValue(':extension', 'txt');
        $insertFile->bindValue(':thumb', 'no_thumb');
        $insertFile->execute();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    echo "E2E fixture reset OK: admin, demo, restricted, 2 accounts, 1 file\n";
}

/**
 * @param array<string, mixed> $fixture
 */
function verifyFixture(
    PDO               $pdo,
    ConfigFileService $configFileService,
    HashService       $hashService,
    CryptInterface    $crypt,
    UserService       $userService,
    array             $fixture
): void {
    $errors = [];

    $parentId = fixtureId($fixture, 'accounts', 'parent');
    $childId = fixtureId($fixture, 'accounts', 'child');
    $fileId = fixtureId($fixture, 'files', 'childDocument');
    $demoId = fixtureId($fixture, 'users', 'demo');
    $adminId = fixtureId($fixture, 'users', 'admin');
    $restrictedId = fixtureId($fixture, 'users', 'restricted');
    $restrictedGroupId = fixtureId($fixture, 'groups', 'restricted');
    $restrictedProfileId = fixtureId($fixture, 'profiles', 'restricted');
    $masterPassword = fixtureCredential($fixture, 'admin', 'masterPassword');
    $adminLogin = fixtureCredential($fixture, 'admin', 'login');
    $adminPassword = fixtureCredential($fixture, 'admin', 'password');
    $demoLogin = fixtureCredential($fixture, 'demo', 'login');
    $demoPassword = fixtureCredential($fixture, 'demo', 'password');
    $restrictedLogin = fixtureCredential($fixture, 'restricted', 'login');
    $restrictedPassword = fixtureCredential($fixture, 'restricted', 'password');
    $config = $configFileService->getConfigData();
    $salt = (string)$config->getPasswordSalt();

    $check = static function (bool $condition, string $message) use (&$errors): void {
        if (!$condition) {
            $errors[] = $message;
        }
    };

    $check($salt !== '', 'application password salt is missing');
    $check($config->isFilesEnabled(), 'filesEnabled is not enabled in application configuration');
    $check(!$config->isCheckUpdates(), 'checkUpdates is not disabled in application configuration');
    $check(!$config->isCheckNotices(), 'checkNotices is not disabled in application configuration');
    $check(!$config->isDebug(), 'debug is not disabled in application configuration');
    $check(
        !$config->isAuthBasicAutoLoginEnabled(),
        'authBasicAutoLoginEnabled is not disabled in application configuration'
    );

    $configFile = environmentValue('CONFIG_FILE');
    $document = new DOMDocument();

    if (!$document->load($configFile)) {
        $errors[] = 'application configuration file is not readable';
    } else {
        foreach (['filesEnabled', 'checkUpdates', 'checkNotices', 'debug', 'authBasicAutoLoginEnabled'] as $name) {
            $node = $document->getElementsByTagName($name)->item(0);
            $actual = $node?->textContent;
            $valid = $name === 'filesEnabled'
                ? $actual === '1'
                : $actual === '0' || $actual === '';

            if (!$valid) {
                $errors[] = sprintf('%s has an unexpected value in the application configuration file', $name);
            }
        }
    }

    $masterHash = $pdo->prepare('SELECT value FROM Config WHERE parameter = :parameter');
    $masterHash->execute(['parameter' => 'masterPwd']);
    $masterHashValue = $masterHash->fetchColumn();
    $check(
        is_string($masterHashValue) && $hashService->checkHashKey($masterPassword, $masterHashValue),
        'admin Master Password metadata does not match the fixture'
    );

    $lastUpdate = $pdo->prepare('SELECT value FROM Config WHERE parameter = :parameter');
    $lastUpdate->execute(['parameter' => 'lastupdatempass']);
    $check((int)$lastUpdate->fetchColumn() > 0, 'master password timestamp metadata is missing');

    $readUser = $pdo->prepare(
        'SELECT id, login, pass, mPass, mKey, lastUpdateMPass, preferences FROM User WHERE id = :id AND login = :login'
    );

    foreach ([
                 'admin' => [$adminId, $adminLogin, $adminPassword],
                 'demo' => [$demoId, $demoLogin, $demoPassword],
                 'restricted' => [$restrictedId, $restrictedLogin, $restrictedPassword],
             ] as $label => [$id, $login, $password]) {
        $readUser->execute(['id' => $id, 'login' => $login]);
        $user = $readUser->fetch();

        if (!is_array($user)) {
            $errors[] = sprintf('%s user is missing', $label);

            continue;
        }

        $check($hashService->checkHashKey($password, (string)$user['pass']), sprintf('%s login hash is invalid', $label));

        if ($label === 'admin') {
            try {
                $loginUser = $userService->getByLogin($login);
                $check(
                    $hashService->checkHashKey($password, (string)$loginUser->getPass()),
                    'admin login lookup does not return a matching password hash'
                );
            } catch (Throwable) {
                $errors[] = 'admin login lookup failed';
            }
        }

        try {
            $key = trim($password . $login . $salt);
            $unlocked = $crypt->decrypt((string)$user['mPass'], (string)$user['mKey'], $key);
            $check($unlocked === $masterPassword, sprintf('%s user vault does not unlock the master password', $label));
        } catch (Throwable) {
            $errors[] = sprintf('%s user vault cannot be decrypted', $label);
        }

        $check((int)$user['lastUpdateMPass'] > 0, sprintf('%s user master password metadata is missing', $label));

        $preferences = json_decode((string)$user['preferences'], true);
        $check(
            is_array($preferences) && ($preferences['lang'] ?? null) === 'en_US',
            sprintf('%s user preferences are missing or unexpected', $label)
        );
    }

    $readAccount = $pdo->prepare(
        'SELECT id, name, pass, `key`, parentId, login FROM Account WHERE id = :id'
    );

    foreach ([
                 [$parentId, fixtureName($fixture, 'parentAccount'), fixtureContent($fixture, 'parentPassword'), null],
                 [$childId, fixtureName($fixture, 'childAccount'), fixtureContent($fixture, 'childPassword'), $parentId],
             ] as [$id, $name, $password, $parent]) {
        $readAccount->execute(['id' => $id]);
        $account = $readAccount->fetch();

        if (!is_array($account)) {
            $errors[] = sprintf('account %d is missing', $id);

            continue;
        }

        $check($account['name'] === $name, sprintf('account %d name is unexpected', $id));
        $check(($account['parentId'] === null ? null : (int)$account['parentId']) === $parent, sprintf('account %d parent is unexpected', $id));

        try {
            $unlocked = $crypt->decrypt((string)$account['pass'], (string)$account['key'], $masterPassword);
            $check($unlocked === $password, sprintf('account %d ciphertext cannot be decrypted', $id));
        } catch (Throwable) {
            $errors[] = sprintf('account %d ciphertext cannot be decrypted', $id);
        }
    }

    $readFile = $pdo->prepare('SELECT accountId, name, content FROM AccountFile WHERE id = :id');
    $readFile->execute(['id' => $fileId]);
    $file = $readFile->fetch();

    if (!is_array($file)) {
        $errors[] = 'synthetic account file is missing';
    } else {
        $check((int)$file['accountId'] === $childId, 'synthetic account file is attached to the wrong account');
        $check($file['name'] === fixtureName($fixture, 'childFile'), 'synthetic account file name is unexpected');
        $check($file['content'] === fixtureContent($fixture, 'childFileText'), 'synthetic account file content is unexpected');
    }

    $clientExists = $pdo->prepare('SELECT COUNT(*) FROM Client WHERE id = :id AND name = :name');
    $clientExists->execute([
        'id' => fixtureId($fixture, 'clients', 'primary'),
        'name' => fixtureName($fixture, 'client'),
    ]);
    $check((int)$clientExists->fetchColumn() === 1, 'synthetic client is missing');

    $groupExists = $pdo->prepare('SELECT COUNT(*) FROM UserGroup WHERE id = :id AND name = :name');
    $groupExists->execute([
        'id' => fixtureId($fixture, 'groups', 'demo'),
        'name' => fixtureName($fixture, 'demoGroup'),
    ]);
    $check((int)$groupExists->fetchColumn() === 1, 'synthetic demo group is missing');

    $restrictedGroupExists = $pdo->prepare('SELECT COUNT(*) FROM UserGroup WHERE id = :id AND name = :name');
    $restrictedGroupExists->execute([
        'id' => $restrictedGroupId,
        'name' => fixtureName($fixture, 'restrictedGroup'),
    ]);
    $check((int)$restrictedGroupExists->fetchColumn() === 1, 'synthetic restricted group is missing');

    $restrictedProfile = $pdo->prepare('SELECT name, profile FROM UserProfile WHERE id = :id');
    $restrictedProfile->execute(['id' => $restrictedProfileId]);
    $profileRow = $restrictedProfile->fetch();

    if (!is_array($profileRow)) {
        $errors[] = 'synthetic restricted profile is missing';
    } else {
        $check(
            $profileRow['name'] === fixtureName($fixture, 'restrictedProfile'),
            'synthetic restricted profile name is unexpected'
        );

        $profile = json_decode((string)$profileRow['profile'], true);
        $check(is_array($profile), 'synthetic restricted profile is not valid JSON');
        $check(($profile['accView'] ?? false) === true, 'synthetic restricted profile must allow account view');
        $check(($profile['accViewPass'] ?? false) === false, 'synthetic restricted profile must deny account password view');
        $check(($profile['accViewHistory'] ?? false) === false, 'synthetic restricted profile must deny account history view');
        $check(($profile['accEdit'] ?? false) === false, 'synthetic restricted profile must deny account edit');
        $check(($profile['accEditPass'] ?? false) === false, 'synthetic restricted profile must deny account password edit');
        $check(($profile['accAdd'] ?? false) === false, 'synthetic restricted profile must deny account create');
        $check(($profile['accDelete'] ?? false) === false, 'synthetic restricted profile must deny account delete');
        $check(($profile['accFiles'] ?? false) === false, 'synthetic restricted profile must deny account files');
        $check(($profile['accPermission'] ?? false) === false, 'synthetic restricted profile must deny account permissions');
        $check(($profile['accPublicLinks'] ?? false) === false, 'synthetic restricted profile must deny public links');
        $check(($profile['configGeneral'] ?? false) === false, 'synthetic restricted profile must deny general configuration');
        $check(($profile['configEncryption'] ?? false) === false, 'synthetic restricted profile must deny encryption configuration');
        $check(($profile['configBackup'] ?? false) === false, 'synthetic restricted profile must deny backup configuration');
        $check(($profile['configImport'] ?? false) === false, 'synthetic restricted profile must deny import configuration');
        $check(($profile['mgmUsers'] ?? false) === false, 'synthetic restricted profile must deny user management');
        $check(($profile['mgmGroups'] ?? false) === false, 'synthetic restricted profile must deny group management');
        $check(($profile['mgmProfiles'] ?? false) === false, 'synthetic restricted profile must deny profile management');
        $check(($profile['evl'] ?? false) === false, 'synthetic restricted profile must deny event log access');
    }

    $restrictedUser = $pdo->prepare(
        'SELECT userGroupId, userProfileId FROM User WHERE id = :id AND login = :login'
    );
    $restrictedUser->execute(['id' => $restrictedId, 'login' => $restrictedLogin]);
    $restrictedUserRow = $restrictedUser->fetch();
    $check(is_array($restrictedUserRow), 'synthetic restricted user is missing');

    if (is_array($restrictedUserRow)) {
        $check((int)$restrictedUserRow['userGroupId'] === $restrictedGroupId, 'synthetic restricted user group is unexpected');
        $check((int)$restrictedUserRow['userProfileId'] === $restrictedProfileId, 'synthetic restricted user profile is unexpected');
    }

    $restrictedLinks = $pdo->prepare(
        'SELECT COUNT(*) FROM AccountToUserGroup
         WHERE userGroupId = :groupId AND accountId IN (:parentId, :childId) AND isEdit = 0'
    );
    $restrictedLinks->execute([
        'groupId' => $restrictedGroupId,
        'parentId' => $parentId,
        'childId' => $childId,
    ]);
    $check((int)$restrictedLinks->fetchColumn() === 2, 'synthetic restricted account view links are missing');

    if ($errors !== []) {
        throw new RuntimeException(implode('; ', $errors));
    }

    echo "E2E fixture verification OK: login hashes, master password metadata, vaults, account ciphertext, restricted profile and configuration\n";
}

try {
    $fixture = fixtureData();
    $pdo = database();
    $configFileService = $container->get(ConfigFileService::class);
    $hashService = $container->get(HashService::class);
    $crypt = $container->get(CryptInterface::class);

    if ($verify) {
        verifyFixture(
            $pdo,
            $configFileService,
            $hashService,
            $crypt,
            $container->get(UserService::class),
            $fixture
        );
    } else {
        resetFixture(
            $pdo,
            $configFileService,
            $hashService,
            $container->get(CryptPKIHandler::class),
            $container->get(UserMasterPassService::class),
            $container->get(AccountCryptService::class),
            $fixture
        );
    }
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("E2E fixture task failed: %s\n", $e->getMessage()));
    exit(1);
}