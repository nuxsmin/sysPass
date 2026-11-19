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

namespace SP\Tests\Modules\Web\Controllers\Account;

use Klein\Response;
use PDO;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Acl\Acl;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\VaultInterface;
use SP\Domain\Crypt\Services\Hash;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;

use function DI\autowire;
use function DI\get;
use function SP\Tests\getDbHandler;

/**
 * DB-backed characterization tests for Account access request
 * (account/requestAccess and account/saveRequest) boundaries and Notification
 * persistence against MariaDB.
 */
#[Group('integration')]
final class AccountRequestDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';

    #[Test]
    public function requestAccessFormRejectsAnonymousWith403AndExposesGuessedPrivateAccountsToIncompleteAndUnprivilegedUsers(): void
    {
        $pdo = getDbHandler()->getConnection();

        $emptyProfile = new ProfileData();
        $unprivilegedUser = $this->seedUser($pdo, 'req_unpriv_user', 5, $emptyProfile);

        $privAccId = $this->seedAccount($pdo, 'Secret Private Request Target', 1, 1, 1, 1, 0);
        $privGrpAccId = $this->seedAccount($pdo, 'Secret PrivateGroup Request Target', 1, 1, 1, 0, 1);

        // 1. Anonymous user hitting RequestAccessController (does not call checkLoggedIn) gets HTTP 403 UnauthorizedPageException
        //    rather than SessionTimeout redirect
        $anonRes = $this->dispatchSessionState('get', 'account/requestAccess/' . $privAccId, false, false, null, null);
        self::assertSame(403, $anonRes['code']);
        self::assertStringContainsString("You don't have permission to access this page", $anonRes['body']);
        self::assertStringNotContainsString('Secret Private Request Target', $anonRes['body']);

        // 2. Incomplete session (isLoggedIn=true, getAuthCompleted=false) passes Acl::checkUserAccess(ACCOUNT_REQUEST)
        //    and discloses guessed isPrivate=1 Account name + client name
        $incompleteRes = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $privAccId,
            true,
            false,
            $unprivilegedUser,
            $emptyProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => false]
        );
        self::assertLessThan(400, $incompleteRes['code']);
        self::assertStringContainsString('Secret Private Request Target', $incompleteRes['body']);
        self::assertStringContainsString('Google', $incompleteRes['body']);

        // 3. Completed unprivileged user (accGlobalSearch=false, isGlobalSearch=false) discloses both private & group-private names
        $privGrpRes = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $privGrpAccId,
            true,
            true,
            $unprivilegedUser,
            $emptyProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => false]
        );
        self::assertLessThan(400, $privGrpRes['code']);
        self::assertStringContainsString('Secret PrivateGroup Request Target', $privGrpRes['body']);
        self::assertStringContainsString('Google', $privGrpRes['body']);
    }

    #[Test]
    public function saveRequestEnforcesLoginButAllowsGuessedPrivateAccountsNotifiesEditorAndHasNoRateLimit(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');

        $emptyProfile = new ProfileData();
        $requester = $this->seedUser($pdo, 'req_submitter', 5, $emptyProfile);
        $lastEditor = $this->seedUser($pdo, 'req_last_editor', 5, $emptyProfile);

        // Account 1: owner = 1 (admin), userEditId = $lastEditor->id, isPrivate = 1
        $privDistinctEditorAccId = $this->seedAccount(
            $pdo,
            'Private Distinct Editor Target',
            1,
            1,
            $lastEditor->id,
            1,
            0
        );
        // Account 2: owner = 1 (admin), userEditId = 1 (same user), isPrivateGroup = 1
        $privGrpSameEditorAccId = $this->seedAccount(
            $pdo,
            'PrivateGroup Same Editor Target',
            1,
            1,
            1,
            0,
            1
        );

        // 1. Anonymous & incomplete sessions are blocked by AccountControllerBase::checkLoggedIn()
        foreach ([
            'anonymous' => [false, false, null, null],
            'incomplete' => [true, false, $requester, $emptyProfile],
        ] as $label => [$loggedIn, $authCompleted, $actor, $actorProfile]) {
            $blockedRes = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $privDistinctEditorAccId,
                $loggedIn,
                $authCompleted,
                $actor,
                $actorProfile,
                [],
                ['description' => 'Unauthorized attempt']
            );
            self::assertSame('', $blockedRes['body'], "$label saveRequest");
        }
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());

        // 2. Empty description fails validation without persisting Notification
        $blankDescRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $privDistinctEditorAccId,
            true,
            true,
            $requester,
            $emptyProfile,
            [],
            ['description' => '']
        );
        $this->assertJsonDenied($blankDescRes, 'A description is needed');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());

        // 3. Unprivileged requester (accGlobalSearch=false, isGlobalSearch=false) succeeds against guessed isPrivate=1 account
        //    and persists Notification rows for BOTH owner (userId=1) AND last editor ($lastEditor->id)
        $okDistinctRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $privDistinctEditorAccId,
            true,
            true,
            $requester,
            $emptyProfile,
            [],
            ['description' => 'Need private access for audit'],
            ['isGlobalSearch' => false]
        );
        $this->assertJsonOk($okDistinctRes, 'Request done');

        $rows = $pdo->query('SELECT userId, type, component, description FROM Notification ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
        self::assertCount(2, $rows);
        self::assertSame([1, $lastEditor->id], [(int)$rows[0]['userId'], (int)$rows[1]['userId']]);
        self::assertStringContainsString('Private Distinct Editor Target', (string)$rows[0]['description']);
        self::assertStringContainsString('Need private access for audit', (string)$rows[1]['description']);

        // 4. When owner === userEditId, SaveRequestController passes [$id, $id] without array_unique(),
        //    persisting 2 duplicate Notification rows for the same owner, and enforces NO rate limit (>3 requests succeed).
        self::truncateTable('Notification');

        for ($i = 1; $i <= 4; $i++) {
            $res = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $privGrpSameEditorAccId,
                true,
                true,
                $requester,
                $emptyProfile,
                [],
                ['description' => 'Repeated request #' . $i],
                ['isGlobalSearch' => false]
            );
            $this->assertJsonOk($res, 'Request done');
        }

        // 4 requests * 2 duplicate owner IDs = 8 Notification rows for userId=1
        self::assertSame(
            8,
            (int)$pdo->query('SELECT COUNT(*) FROM Notification WHERE userId = 1')->fetchColumn()
        );
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $configOverrides
     * @return array{body: string, code: int}
     */
    private function dispatchSessionState(
        string       $method,
        string       $route,
        bool         $loggedIn,
        bool         $authCompleted,
        ?UserDto     $user,
        ?ProfileData $profile,
        array        $paramsGet = [],
        array        $paramsPost = [],
        array        $configOverrides = []
    ): array {
        $context = $this->buildSessionContext($loggedIn, $authCompleted, $user, $profile);
        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            array_merge($this->getConfigData(), $configOverrides)
        );
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        $request = static::buildRequest(
            $method,
            'index.php',
            array_merge(['r' => $route], $paramsGet),
            $paramsPost
        );

        $container = $this->buildContainer(
            $request,
            [
                Context::class => $context,
                ConfigFileService::class => $configFileService,
                ConfigDataInterface::class => $configData,
                AclInterface::class => autowire(Acl::class)
                    ->constructorParameter('actions', get(ActionsInterface::class)),
                AccountAclService::class => autowire(AccountAcl::class)
                    ->constructorParameter('fileCache', null),
            ]
        );

        ob_start();
        try {
            static::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'code' => (int)$response->code(),
        ];
    }

    private function buildSessionContext(
        bool         $loggedIn,
        bool         $authCompleted,
        ?UserDto     $user,
        ?ProfileData $profile
    ): SessionContext {
        $sidStartTime = time();
        $vault = self::createStub(VaultInterface::class);
        $vault->method('getData')->willReturn(self::MASTER_PASS);
        $vault->method('reKey')->willReturnSelf();
        $transient = ['_masterpass' => self::MASTER_PASS];

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn($loggedIn);
        $context->method('getAuthCompleted')->willReturn($authCompleted);
        $context->method('getUserData')->willReturn($user ?? new UserDto());
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getSearchFilters')->willReturn(null);
        $context->method('getSidStartTime')->willReturn($sidStartTime);
        $context->method('getVault')->willReturn($vault);
        $context->method('getTrasientKey')->willReturnCallback(
            static fn(string $key, mixed $default = null) => $transient[$key] ?? $default
        );
        $context->method('setTrasientKey')->willReturnCallback(
            static function (string $key, mixed $value) use (&$transient) {
                $transient[$key] = $value;

                return $value;
            }
        );

        return $context;
    }

    private function seedUser(PDO $pdo, string $login, int $userGroupId, ProfileData $profile): UserDto
    {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
            sha1($login),
            Serde::serialize(new UserPreferences(['lang' => 'en_US'])),
        ]);
        $userId = (int)$pdo->lastInsertId();

        return UserDto::fromModel(UserDataGenerator::factory()->buildUserData())->mutate([
            'id' => $userId,
            'userGroupId' => $userGroupId,
            'userProfileId' => $profileId,
            'login' => $login,
            'ssoLogin' => $login,
            'name' => 'Synthetic ' . $login,
            'email' => $login . '@example.test',
            'lastUpdateMPass' => 1528236611,
            'isAdminApp' => false,
            'isAdminAcc' => false,
            'isDisabled' => false,
            'isChangePass' => false,
            'isChangedPass' => false,
            'isLdap' => false,
            'isMigrate' => false,
            'preferences' => new UserPreferences(['lang' => 'en_US']),
        ]);
    }

    private function seedAccount(
        PDO    $pdo,
        string $name,
        int    $userId,
        int    $userGroupId,
        int    $userEditId,
        int    $isPrivate,
        int    $isPrivateGroup
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt('secret-req-pass', $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, isPrivate, isPrivateGroup, passDate, parentId)
             VALUES (?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), ?, ?, UNIX_TIMESTAMP(), 0)'
        );
        $stmt->execute([
            $userGroupId,
            $userId,
            $userEditId,
            $name,
            'req-acc-login',
            'https://req-acc.example.test',
            $encPass,
            $key,
            'notes',
            $isPrivate,
            $isPrivateGroup,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
