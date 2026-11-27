<?php
/**
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
    public function createRejectsClientUsedOnlyByInaccessibleAccounts(): void
    {
        $pdo = getDbHandler()->getConnection();
        $profile = (new ProfileData())->setAccView(true)->setAccAdd(true)->setAccEdit(true);
        $userId = $this->insertUserWithGroup($pdo, 'api_client_visibility', 5, $profile);
        $pdo->exec("INSERT INTO Client (name, hash, description, isGlobal) VALUES ('API Hidden Synthetic Client', 'api-hidden-client-hash', '', 0)");
        $clientId = (int)$pdo->lastInsertId();
        $hiddenId = $this->seedAccount($pdo, 'API Hidden Client Owner', 1, 1, 'api-hidden-client-owner');
        $pdo->prepare('UPDATE Account SET clientId = ? WHERE id = ?')->execute([$clientId, $hiddenId]);
        $token = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_CREATE);

        $response = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/create', 'id' => 741,
            'params' => [
                'authToken' => $token->getToken(), 'tokenPass' => self::TOKEN_PASS,
                'name' => 'API Denied Client Selection', 'login' => 'api-denied-client',
                'clientId' => $clientId, 'categoryId' => 1, 'pass' => 'synthetic-password',
            ],
        ]);

        self::assertArrayHasKey('error', $response['json']);
        self::assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM Account WHERE name = 'API Denied Client Selection'")->fetchColumn());

        $ownedId = $this->seedAccount($pdo, 'API Client Selection Target', $userId, 5, 'api-client-target');
        $editToken = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_EDIT);
        $edit = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/edit', 'id' => 742,
            'params' => [
                'authToken' => $editToken->getToken(), 'tokenPass' => self::TOKEN_PASS,
                'id' => $ownedId, 'name' => 'API Client Selection Target',
                'clientId' => $clientId, 'categoryId' => 1,
            ],
        ]);
        self::assertArrayHasKey('error', $edit['json']);
        self::assertSame(1, (int)$pdo->query('SELECT clientId FROM Account WHERE id = ' . $ownedId)->fetchColumn());

        $pdo->prepare('UPDATE Client SET isGlobal = 1 WHERE id = ?')->execute([$clientId]);
        $allowed = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/create', 'id' => 743,
            'params' => [
                'authToken' => $token->getToken(), 'tokenPass' => self::TOKEN_PASS,
                'name' => 'API Allowed Global Client', 'login' => 'api-allowed-client',
                'clientId' => $clientId, 'categoryId' => 1, 'pass' => 'synthetic-password',
            ],
        ]);
        self::assertSame(0, $allowed['json']['result']['resultCode'] ?? null);
    }

    #[Test]
    public function bothPrivacyFlagsRequireOwnerAndPrimaryGroupForApiAccess(): void
    {
        $pdo = getDbHandler()->getConnection();
        $profile = (new ProfileData())->setAccView(true)->setAccEdit(true)->setAccGlobalSearch(true);
        $ownerId = $this->insertUserWithGroup($pdo, 'api_both_private_owner', 5, $profile);
        $peerId = $this->insertUserWithGroup($pdo, 'api_both_private_peer', 6, $profile);
        $matching = $this->seedAccount($pdo, 'API Both Matching', $ownerId, 5, 'both-matching', 1, 1);
        $mismatch = $this->seedAccount($pdo, 'API Both Mismatch', $ownerId, 6, 'both-mismatch', 1, 1);
        foreach ([$matching, $mismatch] as $id) {
            $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$id]);
        }
        $ownerView = $this->seedTokenForUser($ownerId, AclActionsInterface::ACCOUNT_VIEW);
        $peerView = $this->seedTokenForUser($peerId, AclActionsInterface::ACCOUNT_VIEW);
        $ownerEdit = $this->seedTokenForUser($ownerId, AclActionsInterface::ACCOUNT_EDIT);
        $ownerSearch = $this->seedTokenForUser($ownerId, AclActionsInterface::ACCOUNT_SEARCH);

        $allowed = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/view', 'id' => 801,
            'params' => ['authToken' => $ownerView->getToken(), 'id' => $matching],
        ]);
        self::assertSame(0, $allowed['json']['result']['resultCode'] ?? null);
        $search = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/search', 'id' => 804,
            'params' => ['authToken' => $ownerSearch->getToken(), 'text' => 'API Both'],
        ]);
        self::assertSame(0, $search['json']['result']['resultCode'] ?? null);
        self::assertCount(1, $search['json']['result']['result'] ?? []);
        self::assertSame('API Both Matching', $search['json']['result']['result'][0]['name'] ?? null);
        $privateSearch = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/search', 'id' => 805,
            'params' => ['authToken' => $ownerSearch->getToken(), 'text' => 'is:private'],
        ]);
        self::assertSame(0, $privateSearch['json']['result']['resultCode'] ?? null);
        $privateNames = array_column($privateSearch['json']['result']['result'] ?? [], 'name');
        self::assertContains('API Both Matching', $privateNames);
        self::assertNotContains('API Both Mismatch', $privateNames);

        foreach (['is:private client:Google category:Web op:or', 'is:private client:Google category:Web op:and'] as $term) {
            foreach ([false, true] as $global) {
                $filtered = $this->dispatchJsonRpc([
                    'jsonrpc' => '2.0', 'method' => 'account/search', 'id' => 806,
                    'params' => ['authToken' => $ownerSearch->getToken(), 'text' => $term,
                        'tagsId' => [1], 'globalSearch' => (int)$global, 'categoryId' => 1, 'clientId' => 1,
                        'op' => str_ends_with($term, 'op:or') ? 'or' : 'and'],
                ], $global);
                self::assertSame(0, $filtered['json']['result']['resultCode'] ?? null);
                $names = array_column($filtered['json']['result']['result'] ?? [], 'name');
                self::assertContains('API Both Matching', $names, $term);
                self::assertNotContains('API Both Mismatch', $names, $term);
            }
        }

        foreach ([$ownerView, $peerView] as $token) {
            $denied = $this->dispatchJsonRpc([
                'jsonrpc' => '2.0', 'method' => 'account/view', 'id' => 802,
                'params' => ['authToken' => $token->getToken(), 'id' => $mismatch],
            ], true);
            self::assertSame("You don't have permission to access this account", $denied['json']['error']['message'] ?? null);
        }
        $edit = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0', 'method' => 'account/edit', 'id' => 803,
            'params' => ['authToken' => $ownerEdit->getToken(), 'tokenPass' => self::TOKEN_PASS,
                'id' => $mismatch, 'name' => 'Denied Both Flags', 'clientId' => 1, 'categoryId' => 1],
        ], true);
        self::assertSame("You don't have permission to access this account", $edit['json']['error']['message'] ?? null);
        self::assertSame('API Both Mismatch', $pdo->query('SELECT name FROM Account WHERE id = ' . $mismatch)->fetchColumn());
    }

    #[Test]
    public function readOnlyAccountToUserAndGroupAreRejectedByApiEditEditPassAndDelete(): void
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
        self::assertSame("You don't have permission to access this account", $editUserRes['json']['error']['message'] ?? null);
        self::assertSame('API RO User Target', $pdo->query('SELECT name FROM Account WHERE id = ' . $userAccId)->fetchColumn());

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
        self::assertSame("You don't have permission to access this account", $editGroupRes['json']['error']['message'] ?? null);
        self::assertSame('API RO Group Target', $pdo->query('SELECT name FROM Account WHERE id = ' . $groupAccId)->fetchColumn());

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
        self::assertSame("You don't have permission to access this account", $editPassRes['json']['error']['message'] ?? null);
        $newPass = (string)$pdo->query('SELECT pass FROM Account WHERE id = ' . $userAccId)->fetchColumn();
        self::assertSame($oldPass, $newPass);

        $delUserRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'id' => $userAccId,
            ],
            'id' => 104,
        ]);
        self::assertSame("You don't have permission to access this account", $delUserRes['json']['error']['message'] ?? null);
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE id = ' . $userAccId)->fetchColumn());

        $delGroupRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'id' => $groupAccId,
            ],
            'id' => 105,
        ]);
        self::assertSame("You don't have permission to access this account", $delGroupRes['json']['error']['message'] ?? null);
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM Account WHERE id = ' . $groupAccId)->fetchColumn());
    }

    #[Test]
    public function apiEditPreservesOmittedFieldsClearsExplicitTagsRevokesPublicLinksAndRejectsInvalidMutations(): void
    {
        $pdo = getDbHandler()->getConnection();

        $editorProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccPrivate(true)
            ->setAccPrivateGroup(true);

        $ownerId = $this->insertUserWithGroup($pdo, 'api_patch_owner', 5, $editorProfile);
        $sharedEditorId = $this->insertUserWithGroup($pdo, 'api_patch_shared', 5, $editorProfile, false, true);

        $targetId = $this->seedAccount(
            $pdo,
            'API Patch Target',
            $ownerId,
            5,
            'orig-api-login',
            0,
            0,
            'https://orig-api.test',
            'orig-api-notes'
        );
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1), (?, 2)')->execute([$targetId, $targetId]);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')->execute([$targetId, $sharedEditorId]);
        $pdo->prepare(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire)
             VALUES (?, 'api-target-publink', 'data', ?, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600)"
        )->execute([$targetId, $ownerId]);

        $ownerEditTok = $this->seedTokenForUser($ownerId, AclActionsInterface::ACCOUNT_EDIT);
        $sharedEditTok = $this->seedTokenForUser($sharedEditorId, AclActionsInterface::ACCOUNT_EDIT);

        // Non-owner shared editor (even isAdminAcc) cannot enable privacy
        $nonOwnerPrivRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $sharedEditTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $targetId,
                'name' => 'API Patch Target',
                'clientId' => 1,
                'categoryId' => 1,
                'private' => 1,
            ],
            'id' => 111,
        ]);
        self::assertArrayHasKey('error', $nonOwnerPrivRes['json']);
        self::assertSame(0, (int)$pdo->query('SELECT isPrivate FROM Account WHERE id = ' . $targetId)->fetchColumn());

        // Validation: whitespace-only name or oversized login rejected
        $blankNameRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $ownerEditTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $targetId,
                'name' => '   ',
                'clientId' => 1,
                'categoryId' => 1,
            ],
            'id' => 112,
        ]);
        self::assertArrayHasKey('error', $blankNameRes['json']);
        self::assertSame('API Patch Target', $pdo->query('SELECT name FROM Account WHERE id = ' . $targetId)->fetchColumn());

        // Owner sets private=1 while omitting login/url/notes/tagsId -> preserved, PublicLink preserved
        $privOnlyRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $ownerEditTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $targetId,
                'name' => 'API Patch Target',
                'clientId' => 1,
                'categoryId' => 1,
                'private' => 1,
            ],
            'id' => 113,
        ]);
        self::assertSame(0, $privOnlyRes['json']['result']['resultCode'] ?? null);
        self::assertSame('orig-api-login', $pdo->query('SELECT login FROM Account WHERE id = ' . $targetId)->fetchColumn());
        self::assertSame('https://orig-api.test', $pdo->query('SELECT url FROM Account WHERE id = ' . $targetId)->fetchColumn());
        self::assertSame('orig-api-notes', $pdo->query('SELECT notes FROM Account WHERE id = ' . $targetId)->fetchColumn());
        self::assertSame(1, (int)$pdo->query('SELECT isPrivate FROM Account WHERE id = ' . $targetId)->fetchColumn());
        self::assertSame(2, (int)$pdo->query('SELECT COUNT(*) FROM AccountToTag WHERE accountId = ' . $targetId)->fetchColumn());
        self::assertSame(1, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $targetId)->fetchColumn());

        // Owner clears tagsId: [] -> tags deleted & PublicLink revoked, while private/login/url/notes still preserved
        $clearTagsRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $ownerEditTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $targetId,
                'name' => 'API Patch Target',
                'clientId' => 1,
                'categoryId' => 1,
                'tagsId' => [],
            ],
            'id' => 114,
        ]);
        self::assertSame(0, $clearTagsRes['json']['result']['resultCode'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM AccountToTag WHERE accountId = ' . $targetId)->fetchColumn());
        self::assertSame(1, (int)$pdo->query('SELECT isPrivate FROM Account WHERE id = ' . $targetId)->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $targetId)->fetchColumn());
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
    public function globalSearchRedactsAndPreventsLeaksAndLinkedParentEnforcesAuthorizationInheritanceAndRevokesLinks(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccGlobalSearch(true);

        $userId = $this->insertUserWithGroup($pdo, 'api_child_user', 5, $profile);
        $privateParentId = $this->seedAccount($pdo, 'API Private Parent', 1, 1, 'api-parent-login', 1, 0);
        $sharedParentId = $this->seedAccount(
            $pdo,
            'API Shared Parent',
            1,
            1,
            'api-shared-login',
            0,
            0,
            'https://api-parent.example.test',
            'parent notes',
            'parent-secret-pass'
        );
        $grandParentId = $this->seedAccount($pdo, 'API GrandParent', 1, 1, 'api-gp-login');
        $hiddenNonPrivId = $this->seedAccount(
            $pdo,
            'API Hidden NonPrivate',
            1,
            1,
            'api-hidden-login',
            0,
            0,
            'https://api-hidden.example.test',
            'api-hidden-notes-token',
            'hidden-pass',
            $grandParentId
        );
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$hiddenNonPrivId]);

        $ownedId = $this->seedAccount($pdo, 'API Owned Account', $userId, 5, 'api-owned-login');

        $createTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_CREATE);
        $editTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_EDIT);
        $passTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_VIEW_PASS);
        $searchTok = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_SEARCH);

        // 1. Create child linked to inaccessible parent fails closed
        $deniedCreate = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => 'API Denied Child',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-child-login',
                'parentId' => $privateParentId,
            ],
            'id' => 301,
        ]);
        self::assertArrayHasKey('error', $deniedCreate['json']);

        // 2. Rejects whitespace-only name & notes > 5000 chars
        $blankNameRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => " \t ",
                'clientId' => 1,
                'categoryId' => 1,
                'pass' => 'valid-api-pass',
            ],
            'id' => 302,
        ]);
        self::assertArrayHasKey('error', $blankNameRes['json']);

        $longNotesRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => 'Long Notes API',
                'clientId' => 1,
                'categoryId' => 1,
                'notes' => str_repeat('z', 5001),
                'pass' => 'valid-api-pass',
            ],
            'id' => 303,
        ]);
        self::assertArrayHasKey('error', $longNotesRes['json']);

        // 3. Grant view access to sharedParentId -> passwordless linked create succeeds & trims name
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$sharedParentId, $userId]);

        $createRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => '  API Child Linked Account  ',
                'clientId' => 1,
                'categoryId' => 1,
                'login' => 'api-child-login',
                'url' => 'https://api-child.example.test',
                'notes' => 'api-child-notes',
                'parentId' => $sharedParentId,
            ],
            'id' => 304,
        ]);
        self::assertSame(0, $createRes['json']['result']['resultCode'] ?? null);
        $childId = (int)($createRes['json']['result']['itemId'] ?? 0);
        self::assertGreaterThan(0, $childId);
        self::assertSame('API Child Linked Account', $pdo->query('SELECT name FROM Account WHERE id = ' . $childId)->fetchColumn());
        self::assertSame($sharedParentId, (int)$pdo->query('SELECT parentId FROM Account WHERE id = ' . $childId)->fetchColumn());

        // 4. Rejects chained parent, self-link, and cycle on edit
        $chainedRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/create',
            'params' => [
                'authToken' => $createTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'name' => 'API Grandchild',
                'clientId' => 1,
                'categoryId' => 1,
                'parentId' => $childId,
            ],
            'id' => 305,
        ]);
        self::assertArrayHasKey('error', $chainedRes['json']);

        $selfEditRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $ownedId,
                'name' => 'API Owned Account',
                'clientId' => 1,
                'categoryId' => 1,
                'parentId' => $ownedId,
            ],
            'id' => 306,
        ]);
        self::assertArrayHasKey('error', $selfEditRes['json']);

        $userParentId = $this->seedAccount($pdo, 'API User Parent', $userId, 5, 'api-u-parent');
        $userChildId = $this->seedAccount($pdo, 'API User Child', $userId, 5, 'api-u-child', 0, 0, 'https://example.test', 'n', 'p', $userParentId);
        $cycleEditRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $userParentId,
                'name' => 'API User Parent',
                'clientId' => 1,
                'categoryId' => 1,
                'parentId' => $userChildId,
            ],
            'id' => 307,
        ]);
        self::assertArrayHasKey('error', $cycleEditRes['json']);

        // 5. viewPass on linked child inherits parent's password; fails closed if parent access is revoked
        $childPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $passTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 308,
        ]);
        self::assertSame(0, $childPassRes['json']['result']['resultCode'] ?? null);
        self::assertSame('parent-secret-pass', $childPassRes['json']['result']['result']['password'] ?? null);

        $pdo->prepare('DELETE FROM AccountToUser WHERE accountId = ? AND userId = ?')
            ->execute([$sharedParentId, $userId]);

        $deniedPassRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $passTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 309,
        ]);
        self::assertArrayHasKey('error', $deniedPassRes['json']);
        self::assertStringNotContainsString('parent-secret-pass', $deniedPassRes['body']);

        // 6. Re-grant parent access, seed a PublicLink for child, rotate parent pass -> revokes child PublicLink
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$sharedParentId, $userId]);
        $pdo->prepare(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire, maxCountViews, countViews, useInfo)
             VALUES (?, 'api-child-link-hash', 'data', ?, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 600, 10, 0, '')"
        )->execute([$childId, $userId]);

        $adminProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);
        $adminUserId = $this->insertUserWithGroup($pdo, 'api_admin_rotator', 1, $adminProfile, true, true);
        $adminEditPassTok = $this->seedTokenForUser($adminUserId, AclActionsInterface::ACCOUNT_EDIT_PASS);

        $rotateRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/editPass',
            'params' => [
                'authToken' => $adminEditPassTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $sharedParentId,
                'pass' => 'api-parent-rotated',
            ],
            'id' => 310,
        ]);
        self::assertSame(0, $rotateRes['json']['result']['resultCode'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId)->fetchColumn());

        $afterRotatePass = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $passTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 311,
        ]);
        self::assertSame('api-parent-rotated', $afterRotatePass['json']['result']['result']['password'] ?? null);

        // 7. Unlinking child requires ACCOUNT_EDIT_PASS + explicit pass when child has no own password, and works when both are satisfied
        $unlinkEmptyRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
                'name' => 'API Child Linked Account',
                'clientId' => 1,
                'categoryId' => 1,
                'parentId' => 0,
            ],
            'id' => 312,
        ]);
        self::assertArrayHasKey('error', $unlinkEmptyRes['json']);
        self::assertSame($sharedParentId, (int)$pdo->query('SELECT parentId FROM Account WHERE id = ' . $childId)->fetchColumn());

        $unlinkOkRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/edit',
            'params' => [
                'authToken' => $editTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
                'name' => 'API Child Unlinked',
                'clientId' => 1,
                'categoryId' => 1,
                'parentId' => 0,
                'pass' => 'child-independent-api-pass',
            ],
            'id' => 313,
        ]);
        self::assertSame(0, $unlinkOkRes['json']['result']['resultCode'] ?? null);
        self::assertSame(0, (int)$pdo->query('SELECT parentId FROM Account WHERE id = ' . $childId)->fetchColumn());

        $independentPass = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/viewPass',
            'params' => [
                'authToken' => $passTok->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 314,
        ]);
        self::assertSame('child-independent-api-pass', $independentPass['json']['result']['result']['password'] ?? null);

        // 8. Global search via API matches inaccessible non-private account by name with redacted fields,
        //    blocks hidden-field/tag queries on inaccessible accounts, and returns full fields on visible accounts
        $globalNameRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'API Hidden NonPrivate',
                'globalSearch' => 1,
            ],
            'id' => 315,
        ], true);
        self::assertSame(0, $globalNameRes['json']['result']['resultCode'] ?? null);
        $globalItems = $globalNameRes['json']['result']['result'] ?? [];
        self::assertCount(1, $globalItems);
        self::assertSame('API Hidden NonPrivate', $globalItems[0]['name'] ?? null);
        self::assertSame('Google', $globalItems[0]['clientName'] ?? null);
        self::assertSame('Web', $globalItems[0]['categoryName'] ?? null);
        self::assertSame('', $globalItems[0]['login'] ?? '');
        self::assertSame('', $globalItems[0]['url'] ?? '');
        self::assertSame('', $globalItems[0]['notes'] ?? '');
        self::assertSame(0, (int)($globalItems[0]['parentId'] ?? 0));

        $hiddenNotesSearch = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'api-hidden-notes-token',
                'globalSearch' => 1,
            ],
            'id' => 316,
        ], true);
        self::assertSame([], $hiddenNotesSearch['json']['result']['result'] ?? null);

        $hiddenTagSearch = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'API Hidden NonPrivate',
                'tagsId' => [1],
                'globalSearch' => 1,
            ],
            'id' => 317,
        ], true);
        self::assertSame([], $hiddenTagSearch['json']['result']['result'] ?? null);

        $visibleSearchRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/search',
            'params' => [
                'authToken' => $searchTok->getToken(),
                'text' => 'api-child-notes',
            ],
            'id' => 318,
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
        string $plainPass = 'parent-secret-pass',
        int    $parentId = 0,
        int    $passDateChange = 0
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt($plainPass, $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, passDateChange, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), ?, ?)'
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
            $passDateChange,
            $parentId,
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
                            $version = \SP\Domain\Common\Providers\Version::getVersionStringNormalized();
                            $configData = $config->getConfigData()
                                ->setDbHost($databaseConnectionData->getDbHost())
                                ->setDbName($databaseConnectionData->getDbName())
                                ->setDbUser($databaseConnectionData->getDbUser())
                                ->setDbPass($databaseConnectionData->getDbPass())
                                ->setAppVersion($version)
                                ->setDatabaseVersion($version)
                                ->setConfigVersion($version)
                                ->setUpgradeKey(null)
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

    #[Test]
    public function apiDeleteRejectsLinkedParentAndPerformsAtomicSnapshotAndCleanup(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccDelete(true)
            ->setAccViewPass(true);
        $userId = $this->insertUserWithGroup($pdo, 'api_del_owner', 5, $profile);
        $deleteToken = $this->seedTokenForUser($userId, AclActionsInterface::ACCOUNT_DELETE);

        $parentId = $this->seedAccount($pdo, 'API Parent Del', $userId, 5, 'api-parent');
        $childId = $this->seedAccount($pdo, 'API Child Del', $userId, 5, 'api-child', 0, 0, 'https://example.test', 'notes', 'p', $parentId);

        // 1. Reject deleting parent while child exists
        $parentRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $parentId,
            ],
            'id' => 401,
        ]);
        self::assertArrayHasKey('error', $parentRes['json']);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $parentId));

        // 2. Attach child associations & delete child cleanly
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$childId]);
        $pdo->prepare('INSERT INTO CustomFieldData (itemId, moduleId, definitionId, data, `key`) VALUES (?, 1, 1, ?, NULL)')
            ->execute([$childId, 'api-cf-val']);
        $pdo->prepare(
            'INSERT INTO PublicLink (itemId, hash, data, userId, typeId, dateAdd, dateExpire, countViews, maxCountViews, totalCountViews, notify)
             VALUES (?, ?, ?, ?, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 600, 0, 5, 0, 0)'
        )->execute([$childId, 'api-child-link', 'payload', $userId]);

        $childRes = $this->dispatchJsonRpc([
            'jsonrpc' => '2.0',
            'method' => 'account/delete',
            'params' => [
                'authToken' => $deleteToken->getToken(),
                'tokenPass' => self::TOKEN_PASS,
                'id' => $childId,
            ],
            'id' => 402,
        ]);
        self::assertArrayHasKey('result', $childRes['json']);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $childId));
        $snapId = (int)$this->fetchValue('SELECT id FROM AccountHistory WHERE accountId = ' . $childId . ' AND isDeleted = 1');
        self::assertGreaterThan(0, $snapId);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToTag WHERE accountId = ' . $childId));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM CustomFieldData WHERE moduleId = 1 AND itemId = ' . $childId));
        self::assertSame(
            'api-cf-val',
            $this->fetchValue(
                'SELECT data FROM CustomFieldData WHERE moduleId = ' . AclActionsInterface::ACCOUNT_HISTORY_VIEW . ' AND itemId = ' . $snapId
            )
        );
    }
}
