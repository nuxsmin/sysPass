<?php
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

declare(strict_types=1);

namespace SP\Tests\Modules\Api;

use DI\ContainerBuilder;
use Klein\Request;
use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Api\Ports\ApiRequestService;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Auth\Services\AuthToken as AuthTokenService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Bootstrap\BootstrapInterface;
use SP\Domain\Core\Bootstrap\ModuleInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Api\Adapters\ApiRequestAdapter;
use SP\Modules\Api\Bootstrap;
use SP\Tests\DatabaseTrait;

use function DI\create;
use function SP\Tests\getDbHandler;

defined('APP_MODULE') || define('APP_MODULE', 'api');

/**
 * DB-backed characterization tests for JSON-RPC Account authorization,
 * privacy, linked parentId, and search visibility boundaries.
 */
#[Group('integration')]
final class AccountBoundaryTest extends TestCase
{
    use DatabaseTrait;

    private const TOKEN_PASS = 'synthetic-api-token-pass-2026';
    private const MASTER_PASS = '12345678900';

    private static ?array $moduleDefinitions = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (self::$moduleDefinitions === null) {
            self::$moduleDefinitions = FileSystem::require(
                FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'api', 'module.php')
            );
        }
    }

    protected function setUp(): void
    {
        self::loadFixtures();
        self::truncateTable('AuthToken');
        self::truncateTable('Track');

        parent::setUp();
    }

    #[Test]
    public function readOnlyAccountToUserAndGroupAreIgnoredByApiEditEditPassAndDelete(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccDelete(true);

        $roUserId = $this->insertUserWithGroup($pdo, 'api_ro_user', 5, $profile);
        $userAccId = $this->seedAccount($pdo, 'API RO User Target', 1, 1, 'api-ro-user-login');
        $groupAccId = $this->seedAccount($pdo, 'API RO Group Target', 1, 1, 'api-ro-group-login');

        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$userAccId, $roUserId]);
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 0)')
            ->execute([$groupAccId, 5]);

        $editToken = $this->seedTokenForUser($roUserId, AclActionsInterface::ACCOUNT_EDIT);
        $editPassToken = $this->seedTokenForUser($roUserId, AclActionsInterface::ACCOUNT_EDIT_PASS);
        $deleteToken = $this->seedTokenForUser($roUserId, AclActionsInterface::ACCOUNT_DELETE);

        $editUserRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editToken->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $userAccId,
                'name' => 'API Mutated User Target',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-mutated-user',
                'notes' => 'api ro notes',
                'userId' => 1,
                'userGroupId' => 1,
            ],
            'id' => 101,
        ]);
        self::assertSame(0, $editUserRes['json']['result']['resultCode'] ?? null);
        self::assertSame('API Mutated User Target', $pdo->query('SELECT name FROM Account WHERE id = ' . $userAccId)->fetchColumn());

        $editGroupRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editToken->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $groupAccId,
                'name' => 'API Mutated Group Target',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-mutated-group',
                'notes' => 'api ro notes',
                'userId' => 1,
                'userGroupId' => 1,
            ],
            'id' => 102,
        ]);
        self::assertSame(0, $editGroupRes['json']['result']['resultCode'] ?? null);
        self::assertSame('API Mutated Group Target', $pdo->query('SELECT name FROM Account WHERE id = ' . $groupAccId)->fetchColumn());

        $oldPass = (string)$pdo->query('SELECT pass FROM Account WHERE id = ' . $userAccId)->fetchColumn();
        $editPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/editPass',
            'params' => [
                'authToken' => $editPassToken->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $userAccId,
                'pass' => 'api-ro-new-pass',
            ],
            'id' => 103,
        ]);
        self::assertSame(0, $editPassRes['json']['result']['resultCode'] ?? null);
        $newPass = (string)$pdo->query('SELECT pass FROM Account WHERE id = ' . $userAccId)->fetchColumn();
        self::assertNotSame($oldPass, $newPass);

        $delUserRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'id' => $userAccId,
            ],
            'id' => 104,
        ]);
        self::assertSame(0, $delUserRes['json']['result']['resultCode'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE id = ' . $userAccId)->fetchColumn());

        $delGroupRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'id' => $groupAccId,
            ],
            'id' => 105,
        ]);
        self::assertSame(0, $delGroupRes['json']['result']['resultCode'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE id = ' . $groupAccId)->fetchColumn());
    }

    #[Test]
    public function privateAndPrivateGroupEnforceOwnerAndPrimaryGroupAcrossApiAccountEndpoints(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccDelete(true)
            ->setAccGlobalSearch(true);

        $privAccId = $this->seedAccount($pdo, 'API Private Account', 1, 6, 'api-priv-login', 1, 0);
        $privGrpAccId = $this->seedAccount($pdo, 'API PrivateGroup Account', 1, 6, 'api-privgrp-login', 0, 1);

        $sharedUserId = $this->insertUserWithGroup($pdo, 'api_shared_priv', 5, $profile);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')
            ->execute([$privAccId, $sharedUserId]);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')
            ->execute([$privGrpAccId, $sharedUserId]);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$sharedUserId, 6]);

        $adminAppId = $this->insertUserWithGroup($pdo, 'api_admin_app', 5, $profile, true, false);
        $samePrimaryGrpUserId = $this->insertUserWithGroup($pdo, 'api_same_primary_grp', 6, $profile);

        foreach (['sharedUser' => $sharedUserId, 'isAdminApp' => $adminAppId] as $actorLabel => $actorId) {
            $viewTok = $this->seedTokenForUser($actorId, AclActionsInterface::ACCOUNT_VIEW);
            $passTok = $this->seedTokenForUser($actorId, AclActionsInterface::ACCOUNT_VIEW_PASS);
            $editTok = $this->seedTokenForUser($actorId, AclActionsInterface::ACCOUNT_EDIT);
            $delTok = $this->seedTokenForUser($actorId, AclActionsInterface::ACCOUNT_DELETE);
            $searchTok = $this->seedTokenForUser($actorId, AclActionsInterface::ACCOUNT_SEARCH);

            foreach ([$privAccId, $privGrpAccId] as $targetId) {
                $viewRes = $this->dispatchJsonRpc([
                    'jsonrpc' => '2.0',
                    'method' => 'account/view',
                    'params' => ['authToken' => $viewTok->getToken(), 'id' => $targetId],
                    'id' => 201,
                ], true);
                self::assertSame(
                    "You don't have permission to access this account",
                    $viewRes['json']['error']['message'] ?? null,
                    "$actorLabel view on $targetId"
                );

                $passRes = $this->dispatchJsonRpc([
                    'jsonrpc' => '2.0',
                    'method' => 'account/viewPass',
                    'params' => [
                        'authToken' => $passTok->getToken(),
                        'tokenPass' => self::TOKEN_PASS,
                        'id' => $targetId,
                    ],
                    'id' => 202,
                ], true);
                self::assertSame('Account not found', $passRes['json']['error']['message'] ?? null);

                $editRes = $this->dispatchJsonRpc([
                    'jsonrpc' => '2.0',
                    'method' => 'account/edit',
                    'params' => [
                        'authToken' => $editTok->getToken(),
                        'tokenPass' => self::TOKEN_PASS,
                        'id' => $targetId,
                        'name' => 'Should Not Update',
                        'clientId' => 1,
                        'categoryId' => 1,
                    ],
                    'id' => 203,
                ], true);
                self::assertSame("You don't have permission to access this account", $editRes['json']['error']['message'] ?? null);

                $delRes = $this->dispatchJsonRpc([
                    'jsonrpc' => '2.0',
                    'method' => 'account/delete',
                    'params' => ['authToken' => $delTok->getToken(), 'id' => $targetId],
                    'id' => 204,
                ], true);
                self::assertSame("You don't have permission to access this account", $delRes['json']['error']['message'] ?? null);
                self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE id = ' . $targetId)->fetchColumn());
            }

            $searchRes = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0',
                'method' => 'account/search',
                'params' => ['authToken' => $searchTok->getToken(), 'text' => 'API Private', 'globalSearch' => 1],
                'id' => 205,
            ], true);
            self::assertSame(0, $searchRes['json']['result']['resultCode'] ?? null);
            self::assertSame([], $searchRes['json']['result']['result'] ?? null);
        }

        // Same primary userGroupId (6) accesses isPrivateGroup=1 without AccountToUser, but is still denied isPrivate=1
        $grpViewTok = $this->seedTokenForUser($samePrimaryGrpUserId, AclActionsInterface::ACCOUNT_VIEW);
        $grpPassTok = $this->seedTokenForUser($samePrimaryGrpUserId, AclActionsInterface::ACCOUNT_VIEW_PASS);
        $grpSearchTok = $this->seedTokenForUser($samePrimaryGrpUserId, AclActionsInterface::ACCOUNT_SEARCH);

        $grpViewRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/view',
            'params' => ['authToken' => $grpViewTok->getToken(), 'id' => $privGrpAccId],
            'id' => 206,
        ]);
        self::assertSame(0, $grpViewRes['json']['result']['resultCode'] ?? null);
        self::assertSame('API PrivateGroup Account', $grpViewRes['json']['result']['result']['data']['name'] ?? null);

        $grpPrivDeniedRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/view',
            'params' => ['authToken' => $grpViewTok->getToken(), 'id' => $privAccId],
            'id' => 209,
        ]);
        self::assertSame("You don't have permission to access this account", $grpPrivDeniedRes['json']['error']['message'] ?? null);

        $grpPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $grpPassTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $privGrpAccId,
            ],
            'id' => 207,
        ]);
        self::assertSame(0, $grpPassRes['json']['result']['resultCode'] ?? null);
        self::assertSame('parent-secret-pass', $grpPassRes['json']['result']['result']['password'] ?? null);

        $grpSearchRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => ['authToken' => $grpSearchTok->getToken(), 'text' => 'API Private'],
            'id' => 208,
        ]);
        self::assertSame(0, $grpSearchRes['json']['result']['resultCode'] ?? null);
        self::assertCount(1, $grpSearchRes['json']['result']['result'] ?? []);
        self::assertSame('API PrivateGroup Account', $grpSearchRes['json']['result']['result'][0]['name'] ?? null);
    }

    #[Test]
    public function parentIdIsUncheckedOnCreateEditViewPassAndApiSearchIgnoresGlobalSearch(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccGlobalSearch(true);

        $userId = $this->insertUserWithGroup($pdo, 'api_child_user', 5, $profile);
        $privateParentId = $this->seedAccount($pdo, 'API Private Parent', 1, 1, 'api-parent-login', 1, 0);
        $hiddenNonPrivId = $this->seedAccount(
            $pdo,
            'API Hidden NonPrivate',
            1,
            1,
            'api-hidden-login',
            0,
            0,
            'https://api-hidden.example.test',
            'api-hidden-notes-token'
        );
        self::assertGreaterThan(0, $hiddenNonPrivId);

        $createTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_CREATE);
        $editTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_EDIT);
        $passTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_VIEW_PASS);
        $searchTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_SEARCH);

        // 1. Create child linked to inaccessible isPrivate=1 parent succeeds without checking parent access
        $createRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => 'API Child Linked Account',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-child-login',
                'url' => 'https://api-child.example.test',
                'notes' => 'api-child-notes',
                'parentId' => $privateParentId,
                'pass' => 'child-own-api-pass',
            ],
            'id' => 301,
        ]);
        self::assertSame(0, $createRes['json']['result']['resultCode'] ?? null);
        $childId = (int)($createRes['json']['result']['itemId'] ?? 0);
        self::assertGreaterThan(0, $childId);
        self::assertSame($privateParentId, (int)$pdo->query('SELECT parentId FROM Account WHERE id = ' . $childId)->fetchColumn());

        // 2. viewPass on child returns child's own password, NOT parent's password
        $childPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $passTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 302,
        ]);
        self::assertSame(0, $childPassRes['json']['result']['resultCode'] ?? null);
        self::assertSame('child-own-api-pass', $childPassRes['json']['result']['result']['password'] ?? null);
        self::assertNotSame('parent-secret-pass', $childPassRes['json']['result']['result']['password'] ?? null);

        // 3. Edit an owned unlinked account to link privateParentId also succeeds without parent authorization check
        $ownedId = $this->seedAccount($pdo, 'API Owned Account', $userId, 5, 'api-owned-login');
        $editRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $ownedId,
                'name' => 'API Owned Linked Account',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-owned-login',
                'notes' => 'api owned notes',
                'parentId' => $privateParentId,
                'userId' => $userId,
                'userGroupId' => 5,
            ],
            'id' => 303,
        ]);
        self::assertSame(0, $editRes['json']['result']['resultCode'] ?? null);
        self::assertSame($privateParentId, (int)$pdo->query('SELECT parentId FROM Account WHERE id = ' . $ownedId)->fetchColumn());

        // 4. account/search ignores globalSearch even when ConfigData::isGlobalSearch() is true,
        //    so hidden non-private account returns 0 results while owned account returns login/url/notes
        $hiddenSearchRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'api-hidden-notes-token',
                'globalSearch' => 1,
                'gsearch' => 1,
            ],
            'id' => 304,
        ], true);
        self::assertSame(0, $hiddenSearchRes['json']['result']['resultCode'] ?? null);
        self::assertSame([], $hiddenSearchRes['json']['result']['result'] ?? null);

        $visibleSearchRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'api-child-notes',
            ],
            'id' => 305,
        ]);
        self::assertSame(0, $visibleSearchRes['json']['result']['resultCode'] ?? null);
        self::assertCount(1, $visibleSearchRes['json']['result']['result'] ?? []);
        self::assertSame('api-child-login', $visibleSearchRes['json']['result']['result'][0]['login'] ?? null);
        self::assertSame('https://api-child.example.test', $visibleSearchRes['json']['result']['result'][0]['url'] ?? null);
        self::assertSame('api-child-notes', $visibleSearchRes['json']['result']['result'][0]['notes'] ?? null);
    }

    private function seedTokenForUser(int $userId, int $actionId): AuthTokenModel
    {
        $pdo = getDbHandler()->getConnection();
        $token = bin2hex(random_bytes(32));
        $vault = Vault::factory(new Crypt())
            ->saveData(self::MASTER_PASS, sha1(self::TOKEN_PASS . $token))
            ->getSerialized();
        $hash = Hash::hashKey(self::TOKEN_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO AuthToken (userId, actionId, createdBy, token, hash, vault, startDate)
             VALUES (:userId, :actionId, 1, :token, :hash, :vault, UNIX_TIMESTAMP())'
        );
        $stmt->execute([
            'userId' => $userId,
            'actionId' => $actionId,
            'token' => $token,
            'hash' => $hash,
            'vault' => $vault,
        ]);

        return new AuthTokenModel([
            'id' => (int)$pdo->lastInsertId(),
            'userId' => $userId,
            'actionId' => $actionId,
            'createdBy' => 1,
            'token' => $token,
        ]);
    }

    private function insertUserWithGroup(
        PDO         $pdo,
        string      $login,
        int         $userGroupId,
        ProfileData $profile,
        bool        $isAdminApp = false,
        bool        $isAdminAcc = false
    ): int {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (:name, :profile)');
        $profileStmt->execute([
            'name' => 'API Profile ' . $login,
            'profile' => Serde::serialize($profile),
        ]);
        $profileId = (int)$pdo->lastInsertId();

        $stmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, :userGroupId, :login, :pass, :email, :profileId, :isAdminApp, :isAdminAcc, 0, :salt, 0, 0, 0, 0)'
        );
        $stmt->execute([
            'name' => 'Synthetic ' . $login,
            'userGroupId' => $userGroupId,
            'login' => $login,
            'pass' => Hash::hashKey('synthetic-pass-' . $login),
            'email' => $login . '@example.test',
            'profileId' => $profileId,
            'isAdminApp' => $isAdminApp ? 1 : 0,
            'isAdminAcc' => $isAdminAcc ? 1 : 0,
            'salt' => sha1($login),
        ]);

        return (int)$pdo->lastInsertId();
    }

    private function seedAccount(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        string $login,
        int    $isPrivate = 0,
        int    $isPrivateGroup = 0,
        string $url = 'https://example.test',
        string $notes = 'synthetic api notes',
        string $plainPass = 'parent-secret-pass'
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt($plainPass, $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userId,
            $name,
            $login,
            $url,
            $encPass,
            $key,
            $notes,
            $isPrivate,
            $isPrivateGroup,
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{code: int, body: string, json: array<string, mixed>}
     */
    private function dispatchJsonRpc(array $payload, bool $globalSearchEnabled = false): array
    {
        $rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
        $databaseConnectionData = DatabaseConnectionData::getFromEnvironment();
        $requestFile = tempnam(TMP_PATH, 'api-account-boundary-');
        if ($requestFile === false) {
            throw new RuntimeException('Unable to create temporary API request file');
        }

        try {
            file_put_contents($requestFile, $rawBody);

            $dic = (new ContainerBuilder())
                ->addDefinitions(array_merge(
                    CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'api'),
                    DomainDefinitions::getDefinitions(),
                    self::$moduleDefinitions,
                    [
                        DatabaseConnectionData::class => $databaseConnectionData,
                        ConfigDataInterface::class => static function (ConfigFileService $config) use (
                            $databaseConnectionData,
                            $globalSearchEnabled
                        ) {
                            $configData = $config->getConfigData()
                                ->setDbHost($databaseConnectionData->getDbHost())
                                ->setDbName($databaseConnectionData->getDbName())
                                ->setDbUser($databaseConnectionData->getDbUser())
                                ->setDbPass($databaseConnectionData->getDbPass())
                                ->setGlobalSearch($globalSearchEnabled)
                                ->setInstalled(true);
                            $config->save($configData, false);

                            return $configData;
                        },
                        ApiRequestService::class => static fn() => ApiRequestAdapter::buildFromRequest($requestFile),
                        Request::class => new Request(
                            [],
                            [],
                            [],
                            [
                                'HTTP_HOST' => 'localhost:8080',
                                'HTTP_ACCEPT' => 'application/json',
                                'HTTP_USER_AGENT' => 'AccountBoundaryTest/1.0',
                                'REQUEST_URI' => '/api.php',
                                'REQUEST_METHOD' => 'POST',
                                'HTTP_CONTENT_TYPE' => 'application/json',
                                'REMOTE_ADDR' => '127.0.0.1',
                            ],
                            [],
                            null
                        ),
                        Response::class => create(Response::class),
                    ]
                ))
                ->build();

            ob_start();
            try {
                Bootstrap::run($dic->get(BootstrapInterface::class), $dic->get(ModuleInterface::class));
            } finally {
                $echoed = (string)ob_get_clean();
            }

            /** @var Response $response */
            $response = $dic->get(Response::class);
            $body = (string)$response->body();
            $effectiveBody = $body !== '' ? $body : $echoed;

            return [
                'code' => (int)$response->code(),
                'body' => $effectiveBody,
                'json' => json_decode($effectiveBody, true, 512, JSON_THROW_ON_ERROR),
            ];
        } finally {
            @unlink($requestFile);
        }
    }
}
