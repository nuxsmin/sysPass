<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Ui\Fixtures;

use Aura\SqlQuery\QueryFactory;
use DOMDocument;
use PDO;
use PDOStatement;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Crypt\CryptPKIHandler;
use SP\Domain\Crypt\Ports\HashService;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Ports\UserService;
use Throwable;

const DEFAULT_SECOND_CLIENT_ID = 1001;
const DEFAULT_SECOND_CLIENT_NAME = 'E2E_Second_Client';
const DEFAULT_TAG_ID = 1001;
const DEFAULT_TAG_NAME = 'E2E_Tag';

/**
 * @return array<string, mixed>
 */
function loadFixtureRaw(): array
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

function requireEnv(string $name): string
{
    $value = getenv($name);

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Missing required environment variable %s', $name));
    }

    return $value;
}

function connectDatabase(): PDO
{
    return new PDO(
        sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            requireEnv('DATABASE_HOST'),
            requireEnv('DATABASE_NAME')
        ),
        requireEnv('DATABASE_USER'),
        requireEnv('DATABASE_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

/**
 * @param array<string, mixed> $raw
 */
function fixtureId(array $raw, string $group, string $key): int
{
    $value = $raw['ids'][$group][$key] ?? null;

    if (!is_int($value) && !(is_string($value) && ctype_digit($value))) {
        throw new RuntimeException(sprintf('Fixture id %s.%s is missing', $group, $key));
    }

    return (int)$value;
}

/**
 * @param array<string, mixed> $raw
 */
function fixtureName(array $raw, string $key): string
{
    $value = $raw['names'][$key] ?? null;

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Fixture name %s is missing', $key));
    }

    return $value;
}

/**
 * @param array<string, mixed> $raw
 */
function fixtureCredential(array $raw, string $account, string $key): string
{
    $value = $raw['credentials'][$account][$key] ?? null;

    if (!is_string($value) || $value === '') {
        throw new RuntimeException(sprintf('Fixture credential %s.%s is missing', $account, $key));
    }

    return $value;
}

/**
 * @param array<string, mixed> $raw
 */
function fixtureContent(array $raw, string $key): string
{
    $value = $raw['content'][$key] ?? null;

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
 * Build a normalized, typed specification of all synthetic fixture records.
 *
 * @param array<string, mixed> $raw
 * @return array{
 *   masterPassword: string,
 *   adminId: int,
 *   clientId: int,
 *   categoryId: int,
 *   restrictedGroupId: int,
 *   restrictedProfileId: int,
 *   parentAccountId: int,
 *   childAccountId: int,
 *   groups: list<array{label: string, id: int, name: string, description: string}>,
 *   profiles: list<array{label: string, id: int, name: string, copyAdminProfile: bool}>,
 *   users: list<array{
 *     label: string, id: int, name: string, login: string, password: string,
 *     groupId: int|null, profileId: int|null, installed: bool
 *   }>,
 *   clients: list<array{id: int, name: string, description: string, isGlobal: int}>,
 *   categories: list<array{id: int, name: string, description: string}>,
 *   tags: list<array{id: int, name: string}>,
 *   accounts: list<array{
 *     id: int, name: string, login: string, password: string,
 *     url: string, notes: string, parentId: int|null
 *   }>,
 *   accountGroupLinks: list<array{accountId: int, userGroupId: int, isEdit: int}>,
 *   files: list<array{id: int, accountId: int, name: string, type: string, extension: string, content: string}>
 * }
 */
function buildFixtureSpec(array $raw): array
{
    $adminId = fixtureId($raw, 'users', 'admin');
    $demoGroupId = fixtureId($raw, 'groups', 'demo');
    $restrictedGroupId = fixtureId($raw, 'groups', 'restricted');
    $demoProfileId = fixtureId($raw, 'profiles', 'demo');
    $restrictedProfileId = fixtureId($raw, 'profiles', 'restricted');
    $clientId = fixtureId($raw, 'clients', 'primary');
    $categoryId = fixtureId($raw, 'categories', 'primary');
    $parentId = fixtureId($raw, 'accounts', 'parent');
    $childId = fixtureId($raw, 'accounts', 'child');

    $rawSecondClientId = $raw['ids']['clients']['secondary'] ?? DEFAULT_SECOND_CLIENT_ID;
    $rawSecondClientName = $raw['names']['secondClient'] ?? DEFAULT_SECOND_CLIENT_NAME;
    $secondClientId = is_int($rawSecondClientId) || (is_string($rawSecondClientId) && ctype_digit($rawSecondClientId))
        ? (int)$rawSecondClientId
        : DEFAULT_SECOND_CLIENT_ID;
    $secondClientName = is_string($rawSecondClientName) && $rawSecondClientName !== ''
        ? $rawSecondClientName
        : DEFAULT_SECOND_CLIENT_NAME;

    $rawTagId = $raw['ids']['tags']['primary'] ?? DEFAULT_TAG_ID;
    $rawTagName = $raw['names']['tag'] ?? DEFAULT_TAG_NAME;
    $tagId = is_int($rawTagId) || (is_string($rawTagId) && ctype_digit($rawTagId))
        ? (int)$rawTagId
        : DEFAULT_TAG_ID;
    $tagName = is_string($rawTagName) && $rawTagName !== '' ? $rawTagName : DEFAULT_TAG_NAME;

    $accounts = [
        [
            'id' => $parentId,
            'name' => fixtureName($raw, 'parentAccount'),
            'login' => fixtureContent($raw, 'parentLogin'),
            'password' => fixtureContent($raw, 'parentPassword'),
            'url' => 'https://e2e.invalid/parent',
            'notes' => 'Synthetic parent account for the UI smoke fixture',
            'parentId' => null,
        ],
        [
            'id' => $childId,
            'name' => fixtureName($raw, 'childAccount'),
            'login' => fixtureContent($raw, 'childLogin'),
            'password' => fixtureContent($raw, 'childPassword'),
            'url' => 'https://e2e.invalid/child',
            'notes' => 'Synthetic child account with a synthetic attachment',
            'parentId' => $parentId,
        ],
    ];

    return [
        'masterPassword' => fixtureCredential($raw, 'admin', 'masterPassword'),
        'adminId' => $adminId,
        'clientId' => $clientId,
        'categoryId' => $categoryId,
        'restrictedGroupId' => $restrictedGroupId,
        'restrictedProfileId' => $restrictedProfileId,
        'parentAccountId' => $parentId,
        'childAccountId' => $childId,
        'groups' => [
            [
                'label' => 'demo',
                'id' => $demoGroupId,
                'name' => fixtureName($raw, 'demoGroup'),
                'description' => 'Synthetic UI smoke group',
            ],
            [
                'label' => 'restricted',
                'id' => $restrictedGroupId,
                'name' => fixtureName($raw, 'restrictedGroup'),
                'description' => 'Synthetic view-only UI discovery group',
            ],
        ],
        'profiles' => [
            [
                'label' => 'demo',
                'id' => $demoProfileId,
                'name' => fixtureName($raw, 'demoProfile'),
                'copyAdminProfile' => true,
            ],
            [
                'label' => 'restricted',
                'id' => $restrictedProfileId,
                'name' => fixtureName($raw, 'restrictedProfile'),
                'copyAdminProfile' => false,
            ],
        ],
        'users' => [
            [
                'label' => 'admin',
                'id' => $adminId,
                'name' => fixtureCredential($raw, 'admin', 'login'),
                'login' => fixtureCredential($raw, 'admin', 'login'),
                'password' => fixtureCredential($raw, 'admin', 'password'),
                'groupId' => null,
                'profileId' => null,
                'installed' => true,
            ],
            [
                'label' => 'demo',
                'id' => fixtureId($raw, 'users', 'demo'),
                'name' => fixtureName($raw, 'demoProfile'),
                'login' => fixtureCredential($raw, 'demo', 'login'),
                'password' => fixtureCredential($raw, 'demo', 'password'),
                'groupId' => $demoGroupId,
                'profileId' => $demoProfileId,
                'installed' => false,
            ],
            [
                'label' => 'restricted',
                'id' => fixtureId($raw, 'users', 'restricted'),
                'name' => fixtureName($raw, 'restrictedProfile'),
                'login' => fixtureCredential($raw, 'restricted', 'login'),
                'password' => fixtureCredential($raw, 'restricted', 'password'),
                'groupId' => $restrictedGroupId,
                'profileId' => $restrictedProfileId,
                'installed' => false,
            ],
        ],
        'clients' => [
            [
                'id' => $clientId,
                'name' => fixtureName($raw, 'client'),
                'description' => 'Synthetic UI smoke client',
                'isGlobal' => 0,
            ],
            [
                'id' => $secondClientId,
                'name' => $secondClientName,
                'description' => 'Synthetic second client',
                'isGlobal' => 1,
            ],
        ],
        'categories' => [
            [
                'id' => $categoryId,
                'name' => fixtureName($raw, 'category'),
                'description' => 'Synthetic UI smoke category',
            ],
        ],
        'tags' => [
            [
                'id' => $tagId,
                'name' => $tagName,
            ],
        ],
        'accounts' => $accounts,
        'accountGroupLinks' => array_map(
            static fn (array $account): array => [
                'accountId' => $account['id'],
                'userGroupId' => $restrictedGroupId,
                'isEdit' => 0,
            ],
            $accounts
        ),
        'files' => [
            [
                'id' => fixtureId($raw, 'files', 'childDocument'),
                'accountId' => $childId,
                'name' => fixtureName($raw, 'childFile'),
                'type' => 'text/plain',
                'extension' => 'txt',
                'content' => fixtureContent($raw, 'childFileText'),
            ],
        ],
    ];
}

function restrictedProfileModel(): ProfileData
{
    return (new ProfileData())->setAccView(true);
}

/**
 * @param array<string, mixed> $values
 * @param list<string> $lobColumns
 */
function bindStatementParams(PDOStatement $stmt, array $values, array $lobColumns = []): void
{
    foreach ($values as $name => $value) {
        $param = str_starts_with((string)$name, ':') ? (string)$name : ':' . $name;
        $column = ltrim($param, ':');

        $type = match (true) {
            $value === null => PDO::PARAM_NULL,
            is_int($value) || is_bool($value) => PDO::PARAM_INT,
            in_array($column, $lobColumns, true) => PDO::PARAM_LOB,
            default => PDO::PARAM_STR,
        };

        $stmt->bindValue($param, is_bool($value) ? (int)$value : $value, $type);
    }
}

/**
 * @param array<string, mixed> $columns
 * @param array<string, string> $rawExpressions
 * @param list<string> $lobColumns
 */
function insertRow(
    PDO $pdo,
    QueryFactory $queryFactory,
    string $table,
    array $columns,
    array $rawExpressions = [],
    array $lobColumns = []
): void {
    $insert = $queryFactory->newInsert()->into($table)->cols($columns);

    foreach ($rawExpressions as $column => $expression) {
        $insert->set($column, $expression);
    }

    $stmt = $pdo->prepare($insert->getStatement());
    bindStatementParams($stmt, $insert->getBindValues(), $lobColumns);
    $stmt->execute();
}

/**
 * @param array<string, mixed> $columns
 * @param array<string, mixed> $whereParams
 * @param list<string> $lobColumns
 */
function updateWhere(
    PDO $pdo,
    QueryFactory $queryFactory,
    string $table,
    array $columns,
    string $whereSql,
    array $whereParams = [],
    array $lobColumns = []
): void {
    $update = $queryFactory
        ->newUpdate()
        ->table($table)
        ->cols($columns)
        ->where($whereSql, $whereParams);

    $stmt = $pdo->prepare($update->getStatement());
    bindStatementParams($stmt, $update->getBindValues(), $lobColumns);
    $stmt->execute();
}

/**
 * @param array<string, mixed> $params
 */
function deleteWhere(
    PDO $pdo,
    QueryFactory $queryFactory,
    string $table,
    string $whereSql,
    array $params = []
): void {
    $delete = $queryFactory->newDelete()->from($table)->where($whereSql, $params);
    $stmt = $pdo->prepare($delete->getStatement());
    bindStatementParams($stmt, $delete->getBindValues());
    $stmt->execute();
}

/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>|null
 */
function fetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $stmt = $pdo->prepare($sql);
    bindStatementParams($stmt, $params);
    $stmt->execute();
    $row = $stmt->fetch();

    return is_array($row) ? $row : null;
}

/**
 * @param array<string, mixed> $params
 */
function fetchValue(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    bindStatementParams($stmt, $params);
    $stmt->execute();

    return $stmt->fetchColumn();
}

function configureApplication(ConfigFileService $configFileService): void
{
    $config = $configFileService->getConfigData()
        ->setInstalled(true)
        ->setSiteLang('en_US')
        ->setFilesEnabled(true)
        ->setFilesAllowedMime(['text/plain'])
        ->setCheckUpdates(false)
        ->setCheckNotices(false)
        ->setDebug(false)
        ->setDemoEnabled(false)
        ->setMailEnabled(false)
        ->setMailRequestsEnabled(true)
        ->setAuthBasicEnabled(false)
        ->setAuthBasicAutoLoginEnabled(false);

    $configFileService->save($config);
}

/**
 * @param array<string, mixed> $spec
 */
function cleanFixtureRows(PDO $pdo, QueryFactory $queryFactory, array $spec): void
{
    $parentId = $spec['parentAccountId'];
    $childId = $spec['childAccountId'];

    deleteWhere(
        $pdo,
        $queryFactory,
        'AccountFile',
        'accountId IN (:fileParentId, :fileChildId)',
        ['fileParentId' => $parentId, 'fileChildId' => $childId]
    );

    foreach ($spec['files'] as $file) {
        deleteWhere($pdo, $queryFactory, 'AccountFile', 'id = :fileId', ['fileId' => $file['id']]);
    }

    deleteWhere(
        $pdo,
        $queryFactory,
        'AccountHistory',
        'accountId IN (:historyParentId, :historyChildId)',
        ['historyParentId' => $parentId, 'historyChildId' => $childId]
    );

    deleteWhere(
        $pdo,
        $queryFactory,
        'Account',
        'id IN (:accountParentId, :accountChildId) OR parentId IN (:childParentId, :childChildId)',
        [
            'accountParentId' => $parentId,
            'accountChildId' => $childId,
            'childParentId' => $parentId,
            'childChildId' => $childId,
        ]
    );

    foreach ($spec['clients'] as $client) {
        deleteWhere($pdo, $queryFactory, 'Client', 'id = :id OR name = :name OR hash = :hash', [
            'id' => $client['id'],
            'name' => $client['name'],
            'hash' => itemHash($client['name']),
        ]);
    }

    foreach ($spec['categories'] as $category) {
        deleteWhere($pdo, $queryFactory, 'Category', 'id = :id OR hash = :hash', [
            'id' => $category['id'],
            'hash' => itemHash($category['name']),
        ]);
    }

    foreach ($spec['tags'] as $tag) {
        deleteWhere($pdo, $queryFactory, 'Tag', 'id = :id OR name = :name OR hash = :hash', [
            'id' => $tag['id'],
            'name' => $tag['name'],
            'hash' => itemHash($tag['name']),
        ]);
    }

    foreach ($spec['users'] as $user) {
        if ($user['installed']) {
            continue;
        }

        deleteWhere($pdo, $queryFactory, 'Notification', 'userId = :userId', ['userId' => $user['id']]);
        deleteWhere($pdo, $queryFactory, 'PublicLink', 'userId = :userId', ['userId' => $user['id']]);
    }

    deleteWhere(
        $pdo,
        $queryFactory,
        'AccountToUserGroup',
        'userGroupId = :groupId',
        ['groupId' => $spec['restrictedGroupId']]
    );

    foreach ($spec['users'] as $user) {
        if ($user['installed']) {
            continue;
        }

        deleteWhere($pdo, $queryFactory, 'User', 'id = :id OR login = :login', [
            'id' => $user['id'],
            'login' => $user['login'],
        ]);
    }

    foreach ($spec['groups'] as $group) {
        deleteWhere($pdo, $queryFactory, 'UserGroup', 'id = :id', ['id' => $group['id']]);
    }

    foreach ($spec['profiles'] as $profile) {
        deleteWhere($pdo, $queryFactory, 'UserProfile', 'id = :id', ['id' => $profile['id']]);
    }
}

/**
 * @param array<string, mixed> $spec
 */
function resetFixture(
    PDO $pdo,
    QueryFactory $queryFactory,
    ConfigFileService $configFileService,
    HashService $hashService,
    CryptPKIHandler $cryptPKIHandler,
    UserMasterPassService $userMasterPassService,
    AccountCryptService $accountCryptService,
    array $spec
): void {
    $pdo->beginTransaction();

    try {
        $pdo->prepare(
            'INSERT INTO CustomFieldType (name, text)
             SELECT :name, :text FROM DUAL
             WHERE NOT EXISTS (SELECT 1 FROM CustomFieldType WHERE name = :checkName)'
        )->execute([
            'name' => 'text',
            'text' => 'Texto',
            'checkName' => 'text',
        ]);

        cleanFixtureRows($pdo, $queryFactory, $spec);

        $adminProfileJson = fetchValue($pdo, 'SELECT profile FROM UserProfile WHERE id = :id', ['id' => 1]);

        if (!is_string($adminProfileJson) || $adminProfileJson === '') {
            throw new RuntimeException('The installed admin profile is missing');
        }

        $preferencesJson = (new UserPreferences([
            'lang' => 'en_US',
            'resultsPerPage' => 10,
            'checkNotifications' => true,
        ]))->toJson();

        foreach ($spec['groups'] as $group) {
            insertRow($pdo, $queryFactory, 'UserGroup', [
                'id' => $group['id'],
                'name' => $group['name'],
                'description' => $group['description'],
            ]);
        }

        $restrictedProfileJson = restrictedProfileModel()->toJson();

        foreach ($spec['profiles'] as $profile) {
            insertRow(
                $pdo,
                $queryFactory,
                'UserProfile',
                [
                    'id' => $profile['id'],
                    'name' => $profile['name'],
                    'profile' => $profile['copyAdminProfile'] ? $adminProfileJson : $restrictedProfileJson,
                ],
                [],
                ['profile']
            );
        }

        $masterPassword = $spec['masterPassword'];

        foreach ($spec['users'] as $user) {
            if ($user['installed']) {
                updateWhere(
                    $pdo,
                    $queryFactory,
                    'User',
                    ['preferences' => $preferencesJson],
                    'id = :userId',
                    ['userId' => $user['id']],
                    ['preferences']
                );
                continue;
            }

            $userMasterPass = $userMasterPassService->create(
                $masterPassword,
                $user['login'],
                $user['password']
            );

            insertRow(
                $pdo,
                $queryFactory,
                'User',
                [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'userGroupId' => $user['groupId'],
                    'login' => $user['login'],
                    'ssoLogin' => null,
                    'pass' => $hashService->hashKey($user['password']),
                    'mPass' => $userMasterPass->getCryptMasterPass(),
                    'mKey' => $userMasterPass->getCryptSecuredKey(),
                    'email' => null,
                    'notes' => null,
                    'loginCount' => 0,
                    'userProfileId' => $user['profileId'],
                    'lastLogin' => null,
                    'lastUpdateMPass' => time(),
                    'isAdminApp' => 0,
                    'isAdminAcc' => 0,
                    'isLdap' => 0,
                    'isDisabled' => 0,
                    'hashSalt' => '',
                    'isMigrate' => 0,
                    'isChangePass' => 0,
                    'isChangedPass' => 0,
                    'preferences' => $preferencesJson,
                ],
                ['lastUpdate' => 'NOW()'],
                ['preferences']
            );
        }

        foreach ($spec['clients'] as $client) {
            insertRow($pdo, $queryFactory, 'Client', [
                'id' => $client['id'],
                'name' => $client['name'],
                'hash' => itemHash($client['name']),
                'description' => $client['description'],
                'isGlobal' => $client['isGlobal'],
            ]);
        }

        foreach ($spec['categories'] as $category) {
            insertRow($pdo, $queryFactory, 'Category', [
                'id' => $category['id'],
                'name' => $category['name'],
                'description' => $category['description'],
                'hash' => itemHash($category['name']),
            ]);
        }

        foreach ($spec['tags'] as $tag) {
            insertRow($pdo, $queryFactory, 'Tag', [
                'id' => $tag['id'],
                'name' => $tag['name'],
                'hash' => itemHash($tag['name']),
            ]);
        }

        $dateAdd = gmdate('Y-m-d H:i:s');
        $passDate = time();

        foreach ($spec['accounts'] as $account) {
            $encrypted = $accountCryptService->getPasswordEncrypted($account['password'], $masterPassword);

            insertRow($pdo, $queryFactory, 'Account', [
                'id' => $account['id'],
                'userGroupId' => 1,
                'userId' => $spec['adminId'],
                'userEditId' => $spec['adminId'],
                'clientId' => $spec['clientId'],
                'name' => $account['name'],
                'categoryId' => $spec['categoryId'],
                'login' => $account['login'],
                'url' => $account['url'],
                'pass' => $encrypted->getPass(),
                'key' => $encrypted->getKey(),
                'notes' => $account['notes'],
                'countView' => 0,
                'countDecrypt' => 0,
                'dateAdd' => $dateAdd,
                'dateEdit' => $dateAdd,
                'otherUserGroupEdit' => 0,
                'otherUserEdit' => 0,
                'isPrivate' => 0,
                'isPrivateGroup' => 0,
                'passDate' => $passDate,
                'passDateChange' => $passDate,
                'parentId' => $account['parentId'],
            ]);
        }

        foreach ($spec['accountGroupLinks'] as $link) {
            insertRow($pdo, $queryFactory, 'AccountToUserGroup', [
                'accountId' => $link['accountId'],
                'userGroupId' => $link['userGroupId'],
                'isEdit' => $link['isEdit'],
            ]);
        }

        foreach ($spec['files'] as $file) {
            insertRow(
                $pdo,
                $queryFactory,
                'AccountFile',
                [
                    'id' => $file['id'],
                    'accountId' => $file['accountId'],
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => strlen($file['content']),
                    'content' => $file['content'],
                    'extension' => $file['extension'],
                    'thumb' => 'no_thumb',
                ],
                [],
                ['content']
            );
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }

    configureApplication($configFileService);
    $cryptPKIHandler->createKeys();

    echo "E2E fixture reset OK: admin, demo, restricted, 2 accounts, 1 file\n";
}

/**
 * @param list<string> $errors
 */
function verifyApplicationConfig(ConfigFileService $configFileService, array &$errors): string
{
    $check = static function (bool $condition, string $message) use (&$errors): void {
        if (!$condition) {
            $errors[] = $message;
        }
    };

    $config = $configFileService->getConfigData();
    $salt = (string)$config->getPasswordSalt();

    $check($salt !== '', 'application password salt is missing');
    $check($config->isFilesEnabled(), 'filesEnabled is not enabled in application configuration');
    $check(in_array('text/plain', $config->getFilesAllowedMime(), true), 'text/plain uploads are not enabled');
    $check(!$config->isCheckUpdates(), 'checkUpdates is not disabled in application configuration');
    $check(!$config->isCheckNotices(), 'checkNotices is not disabled in application configuration');
    $check(!$config->isDebug(), 'debug is not disabled in application configuration');
    $check(!$config->isMailEnabled(), 'mailEnabled is not disabled in application configuration');
    $check($config->isMailRequestsEnabled(), 'mailRequestsEnabled is not enabled in application configuration');
    $check(!$config->isAuthBasicEnabled(), 'authBasicEnabled is not disabled in application configuration');
    $check(
        !$config->isAuthBasicAutoLoginEnabled(),
        'authBasicAutoLoginEnabled is not disabled in application configuration'
    );

    $configFile = requireEnv('CONFIG_FILE');
    $document = new DOMDocument();

    if (!$document->load($configFile)) {
        $errors[] = 'application configuration file is not readable';

        return $salt;
    }

    $expectedFlags = [
        'filesEnabled' => '1',
        'mailRequestsEnabled' => '1',
        'checkUpdates' => '0',
        'checkNotices' => '0',
        'debug' => '0',
        'mailEnabled' => '0',
        'authBasicEnabled' => '0',
        'authBasicAutoLoginEnabled' => '0',
    ];

    foreach ($expectedFlags as $name => $expected) {
        $actual = $document->getElementsByTagName($name)->item(0)?->textContent;
        $valid = $expected === '1' ? $actual === '1' : ($actual === '0' || $actual === '');
        $check($valid, sprintf('%s has an unexpected value in the application configuration file', $name));
    }

    return $salt;
}

/**
 * @param array<string, mixed> $spec
 */
function verifyFixture(
    PDO $pdo,
    ConfigFileService $configFileService,
    HashService $hashService,
    CryptInterface $crypt,
    UserService $userService,
    array $spec
): void {
    $errors = [];
    $check = static function (bool $condition, string $message) use (&$errors): void {
        if (!$condition) {
            $errors[] = $message;
        }
    };

    $salt = verifyApplicationConfig($configFileService, $errors);
    $masterPassword = $spec['masterPassword'];

    $masterHashValue = fetchValue(
        $pdo,
        'SELECT value FROM Config WHERE parameter = :parameter',
        ['parameter' => 'masterPwd']
    );
    $check(
        is_string($masterHashValue) && $hashService->checkMasterKey($masterPassword, $masterHashValue),
        'admin Master Password metadata does not match the fixture'
    );

    $lastUpdate = fetchValue(
        $pdo,
        'SELECT value FROM Config WHERE parameter = :parameter',
        ['parameter' => 'lastupdatempass']
    );
    $check((int)$lastUpdate > 0, 'master password timestamp metadata is missing');

    foreach ($spec['users'] as $userSpec) {
        $label = $userSpec['label'];
        $user = fetchOne(
            $pdo,
            'SELECT id, login, pass, mPass, mKey, lastUpdateMPass, preferences, userGroupId, userProfileId
             FROM User WHERE id = :id AND login = :login',
            ['id' => $userSpec['id'], 'login' => $userSpec['login']]
        );

        if ($user === null) {
            $errors[] = sprintf('%s user is missing', $label);
            continue;
        }

        $check(
            $hashService->checkHashKey($userSpec['password'], (string)$user['pass']),
            sprintf('%s login hash is invalid', $label)
        );

        if ($label === 'admin') {
            try {
                $loginUser = $userService->getByLogin($userSpec['login']);
                $check(
                    $hashService->checkHashKey($userSpec['password'], (string)$loginUser->getPass()),
                    'admin login lookup does not return a matching password hash'
                );
            } catch (Throwable) {
                $errors[] = 'admin login lookup failed';
            }
        }

        try {
            $key = trim($userSpec['password'] . $userSpec['login'] . $salt);
            $unlocked = $crypt->decrypt((string)$user['mPass'], (string)$user['mKey'], $key);
            $check(
                $unlocked === $masterPassword,
                sprintf('%s user vault does not unlock the master password', $label)
            );
        } catch (Throwable) {
            $errors[] = sprintf('%s user vault cannot be decrypted', $label);
        }

        $check((int)$user['lastUpdateMPass'] > 0, sprintf('%s user master password metadata is missing', $label));

        $preferences = json_decode((string)$user['preferences'], true);
        $check(
            is_array($preferences) && ($preferences['lang'] ?? null) === 'en_US',
            sprintf('%s user preferences are missing or unexpected', $label)
        );

        if ($userSpec['groupId'] !== null) {
            $check(
                (int)$user['userGroupId'] === $userSpec['groupId'],
                sprintf('synthetic %s user group is unexpected', $label)
            );
        }

        if ($userSpec['profileId'] !== null) {
            $check(
                (int)$user['userProfileId'] === $userSpec['profileId'],
                sprintf('synthetic %s user profile is unexpected', $label)
            );
        }
    }

    foreach ($spec['accounts'] as $accountSpec) {
        $id = $accountSpec['id'];
        $account = fetchOne(
            $pdo,
            'SELECT id, name, pass, `key`, parentId, login FROM Account WHERE id = :id',
            ['id' => $id]
        );

        if ($account === null) {
            $errors[] = sprintf('account %d is missing', $id);
            continue;
        }

        $check($account['name'] === $accountSpec['name'], sprintf('account %d name is unexpected', $id));
        $check($account['login'] === $accountSpec['login'], sprintf('account %d login is unexpected', $id));
        $check(
            ($account['parentId'] === null ? null : (int)$account['parentId']) === $accountSpec['parentId'],
            sprintf('account %d parent is unexpected', $id)
        );

        try {
            $unlocked = $crypt->decrypt((string)$account['pass'], (string)$account['key'], $masterPassword);
            $check(
                $unlocked === $accountSpec['password'],
                sprintf('account %d ciphertext cannot be decrypted', $id)
            );
        } catch (Throwable) {
            $errors[] = sprintf('account %d ciphertext cannot be decrypted', $id);
        }
    }

    foreach ($spec['files'] as $fileSpec) {
        $file = fetchOne(
            $pdo,
            'SELECT accountId, name, content FROM AccountFile WHERE id = :id',
            ['id' => $fileSpec['id']]
        );

        if ($file === null) {
            $errors[] = 'synthetic account file is missing';
            continue;
        }

        $check(
            (int)$file['accountId'] === $fileSpec['accountId'],
            'synthetic account file is attached to the wrong account'
        );
        $check($file['name'] === $fileSpec['name'], 'synthetic account file name is unexpected');
        $check($file['content'] === $fileSpec['content'], 'synthetic account file content is unexpected');
    }

    $typeId = fetchValue($pdo, "SELECT id FROM CustomFieldType WHERE name = 'text'");
    $check($typeId !== false && (int)$typeId > 0, 'synthetic custom field type is missing');

    foreach ($spec['clients'] as $client) {
        $count = (int)fetchValue(
            $pdo,
            'SELECT COUNT(*) FROM Client WHERE id = :id AND name = :name AND isGlobal = :isGlobal',
            [
                'id' => $client['id'],
                'name' => $client['name'],
                'isGlobal' => $client['isGlobal'],
            ]
        );
        $check($count === 1, sprintf('synthetic client %d is missing or unexpected', $client['id']));
    }

    foreach ($spec['categories'] as $category) {
        $count = (int)fetchValue(
            $pdo,
            'SELECT COUNT(*) FROM Category WHERE id = :id AND name = :name',
            ['id' => $category['id'], 'name' => $category['name']]
        );
        $check($count === 1, 'synthetic category is missing');
    }

    foreach ($spec['tags'] as $tag) {
        $count = (int)fetchValue(
            $pdo,
            'SELECT COUNT(*) FROM Tag WHERE id = :id AND name = :name',
            ['id' => $tag['id'], 'name' => $tag['name']]
        );
        $check($count === 1, 'synthetic tag is missing');
    }

    foreach ($spec['groups'] as $group) {
        $count = (int)fetchValue(
            $pdo,
            'SELECT COUNT(*) FROM UserGroup WHERE id = :id AND name = :name',
            ['id' => $group['id'], 'name' => $group['name']]
        );
        $check($count === 1, sprintf('synthetic %s group is missing', $group['label']));
    }

    foreach ($spec['profiles'] as $profileSpec) {
        $label = $profileSpec['label'];
        $profileRow = fetchOne(
            $pdo,
            'SELECT name, profile FROM UserProfile WHERE id = :id',
            ['id' => $profileSpec['id']]
        );

        if ($profileRow === null) {
            $errors[] = sprintf('synthetic %s profile is missing', $label);
            continue;
        }

        $check(
            $profileRow['name'] === $profileSpec['name'],
            sprintf('synthetic %s profile name is unexpected', $label)
        );

        $actual = json_decode((string)$profileRow['profile'], true);
        $check(is_array($actual), sprintf('synthetic %s profile is not valid JSON', $label));

        if (!is_array($actual) || $profileSpec['copyAdminProfile']) {
            continue;
        }

        /** @var array<string, mixed> $expectedProfile */
        $expectedProfile = json_decode(
            restrictedProfileModel()->toJson(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        foreach ($expectedProfile as $permission => $expectedValue) {
            $check(
                ($actual[$permission] ?? null) === $expectedValue,
                sprintf(
                    'synthetic %s profile permission %s must be %s',
                    $label,
                    $permission,
                    $expectedValue ? 'true' : 'false'
                )
            );
        }
    }

    $restrictedLinks = (int)fetchValue(
        $pdo,
        'SELECT COUNT(*) FROM AccountToUserGroup
         WHERE userGroupId = :groupId AND accountId IN (:parentId, :childId) AND isEdit = 0',
        [
            'groupId' => $spec['restrictedGroupId'],
            'parentId' => $spec['parentAccountId'],
            'childId' => $spec['childAccountId'],
        ]
    );
    $check($restrictedLinks === 2, 'synthetic restricted account view links are missing');

    if ($errors !== []) {
        throw new RuntimeException(implode('; ', $errors));
    }

    echo "E2E fixture verification OK: login hashes, master password metadata, "
        . "vaults, account ciphertext, restricted profile and configuration\n";
}

$root = dirname(__DIR__, 3);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', $root);
}

if (!defined('APP_MODULE')) {
    define('APP_MODULE', 'cli');
}

/** @var ContainerInterface $container */
$container = require $root . '/lib/Base.php';

$options = getopt('', ['reset', 'verify']);
$reset = array_key_exists('reset', $options);
$verify = array_key_exists('verify', $options);

if ($reset === $verify) {
    fwrite(STDERR, "Specify exactly one of --reset or --verify\n");
    exit(2);
}

try {
    $spec = buildFixtureSpec(loadFixtureRaw());
    $pdo = connectDatabase();
    $configFileService = $container->get(ConfigFileService::class);
    $hashService = $container->get(HashService::class);
    $crypt = $container->get(CryptInterface::class);

    $requestLock = $container->get(MasterPasswordRequestLock::class);
    $requestLock->acquire();

    try {
        if ($verify) {
            verifyFixture(
                $pdo,
                $configFileService,
                $hashService,
                $crypt,
                $container->get(UserService::class),
                $spec
            );
        } else {
            resetFixture(
                $pdo,
                $container->get(QueryFactory::class),
                $configFileService,
                $hashService,
                $container->get(CryptPKIHandler::class),
                $container->get(UserMasterPassService::class),
                $container->get(AccountCryptService::class),
                $spec
            );
        }
    } finally {
        $requestLock->release();
    }
} catch (Throwable $e) {
    fwrite(STDERR, sprintf("E2E fixture task failed: %s\n", $e->getMessage()));
    exit(1);
}
