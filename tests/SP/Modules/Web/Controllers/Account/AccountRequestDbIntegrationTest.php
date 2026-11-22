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
 * DB-backed target tests for Account access request (APP-ACCOUNT-REQUEST:
 * account/requestAccess and account/saveRequest) against MariaDB.
 */
#[Group('integration')]
final class AccountRequestDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS    = '12345678900';
    private const DENIED_ACCOUNT = "You don't have permission to access this account";

    #[Test]
    public function anonymousAndIncompleteSessionsAreRejectedOnRequestFormAndSaveRequest(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');
        self::truncateTable('Track');

        $emptyProfile = new ProfileData();
        $incompleteUser = $this->seedUser($pdo, 'req_gate_incomplete', 5, $emptyProfile);
        $accountId = $this->seedAccount($pdo, 'Gate Request Target', 1, 1, 1, 0, 0);

        foreach ([
            'anonymous' => [false, false, null, null],
            'incomplete' => [true, false, $incompleteUser, $emptyProfile],
        ] as $label => [$loggedIn, $authCompleted, $actor, $actorProfile]) {
            $formRes = $this->dispatchSessionState(
                'get',
                'account/requestAccess/' . $accountId,
                $loggedIn,
                $authCompleted,
                $actor,
                $actorProfile,
                ['isAjax' => '1']
            );
            self::assertSame('', $formRes['body'], "$label GET requestAccess body");
            self::assertStringNotContainsString('Gate Request Target', $formRes['body']);

            $saveRes = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $accountId,
                $loggedIn,
                $authCompleted,
                $actor,
                $actorProfile,
                [],
                ['description' => 'Unauthorized request attempt']
            );
            self::assertSame('', $saveRes['body'], "$label POST saveRequest body");
        }

        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Track')->fetchColumn());
    }

    #[Test]
    public function unprivilegedGuessedPrivatePrivateGroupAndMissingAccountsAreGenericallyDeniedWithoutOracle(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');
        self::truncateTable('Track');

        $noGlobalProfile = new ProfileData();
        $unprivUser = $this->seedUser($pdo, 'req_unpriv_user', 5, $noGlobalProfile);

        $globalProfile = (new ProfileData())->setAccGlobalSearch(true);
        $globalUser = $this->seedUser($pdo, 'req_global_probe', 5, $globalProfile);

        $sharedAccId = $this->seedAccount($pdo, 'NonPrivate Request Target', 1, 1, 1, 0, 0);
        $privAccId = $this->seedAccount($pdo, 'Secret Private Request Target', 1, 1, 1, 1, 0);
        $privGrpAccId = $this->seedAccount($pdo, 'Secret PrivateGroup Request Target', 1, 1, 1, 0, 1);
        $missingId = 999999;

        // 1. Missing ID baseline response for GET and POST
        $missingForm = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $missingId,
            true,
            true,
            $globalUser,
            $globalProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => true]
        );
        self::assertSame(403, $missingForm['code']);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $missingForm['body']);

        $missingSave = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $missingId,
            true,
            true,
            $globalUser,
            $globalProfile,
            [],
            ['description' => 'Probe missing'],
            ['isGlobalSearch' => true]
        );
        self::assertSame(403, $missingSave['code']);
        $this->assertJsonDenied($missingSave, self::DENIED_ACCOUNT);

        // 2. Unprivileged user without global search cannot request non-private account lacking grants
        $unprivForm = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $sharedAccId,
            true,
            true,
            $unprivUser,
            $noGlobalProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => false]
        );
        self::assertSame($missingForm['code'], $unprivForm['code']);
        self::assertSame($missingForm['body'], $unprivForm['body']);
        self::assertStringNotContainsString('NonPrivate Request Target', $unprivForm['body']);

        $unprivSave = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $sharedAccId,
            true,
            true,
            $unprivUser,
            $noGlobalProfile,
            [],
            ['description' => 'Unprivileged probe'],
            ['isGlobalSearch' => false]
        );
        self::assertSame($missingSave['code'], $unprivSave['code']);
        self::assertSame($missingSave['body'], $unprivSave['body']);

        // 3. Global-search user cannot request guessed isPrivate=1 or isPrivateGroup=1 accounts
        foreach ([
            [$privAccId, 'Secret Private Request Target'],
            [$privGrpAccId, 'Secret PrivateGroup Request Target'],
        ] as [$targetId, $secretName]) {
            $formRes = $this->dispatchSessionState(
                'get',
                'account/requestAccess/' . $targetId,
                true,
                true,
                $globalUser,
                $globalProfile,
                ['isAjax' => '1'],
                [],
                ['isGlobalSearch' => true]
            );
            self::assertSame($missingForm['code'], $formRes['code']);
            self::assertSame($missingForm['body'], $formRes['body']);
            self::assertStringNotContainsString($secretName, $formRes['body']);
            self::assertStringNotContainsString('Google', $formRes['body']);

            $saveRes = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $targetId,
                true,
                true,
                $globalUser,
                $globalProfile,
                [],
                ['description' => 'Probing private target'],
                ['isGlobalSearch' => true]
            );
            self::assertSame($missingSave['code'], $saveRes['code']);
            self::assertSame($missingSave['body'], $saveRes['body']);
        }

        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Track')->fetchColumn());
    }

    #[Test]
    public function readOnlyAndGlobalSearchUsersNotifyOnlyOwnerRequireNonEmptyReasonAndLeaveGrantsUnchanged(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');
        self::truncateTable('Track');

        $roProfile = (new ProfileData())->setAccView(true);
        $roUser = $this->seedUser($pdo, 'req_ro_user', 5, $roProfile);

        $globalProfile = (new ProfileData())->setAccGlobalSearch(true);
        $globalUser = $this->seedUser($pdo, 'req_gsearch_user', 5, $globalProfile);

        $owner = $this->seedUser($pdo, 'req_acc_owner', 6, new ProfileData());
        $lastEditor = $this->seedUser($pdo, 'req_last_editor', 6, new ProfileData());

        $roAccountId = $this->seedAccount(
            $pdo,
            'RO Requestable Account',
            $owner->id,
            6,
            $lastEditor->id,
            0,
            0
        );
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$roAccountId, $roUser->id]);

        $globalAccountId = $this->seedAccount(
            $pdo,
            'Global Searchable Account',
            $owner->id,
            6,
            $owner->id,
            0,
            0
        );

        // 1. Whitespace-only & empty reasons rejected on eligible account without persisting Notification or Track
        foreach (['', "   \t\n  "] as $blankDescription) {
            $blankRes = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $roAccountId,
                true,
                true,
                $roUser,
                $roProfile,
                [],
                ['description' => $blankDescription],
                ['isGlobalSearch' => false]
            );
            $this->assertJsonDenied($blankRes, 'A description is needed');
        }
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Track')->fetchColumn());

        // 2. Read-only user opens form & submits request even when global search is disabled;
        //    only the owner ($owner->id) is notified — NEVER $lastEditor->id
        $roFormRes = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $roAccountId,
            true,
            true,
            $roUser,
            $roProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => false]
        );
        self::assertSame(200, $roFormRes['code']);
        self::assertStringContainsString('RO Requestable Account', $roFormRes['body']);
        self::assertStringContainsString('Google', $roFormRes['body']);
        self::assertStringNotContainsString('secret-req-pass', $roFormRes['body']);
        self::assertStringNotContainsString('req-acc-login', $roFormRes['body']);

        $roSaveRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $roAccountId,
            true,
            true,
            $roUser,
            $roProfile,
            [],
            ['description' => 'Please grant edit access for maintenance'],
            ['isGlobalSearch' => false]
        );
        $this->assertJsonOk($roSaveRes, 'Request done');
        self::assertStringNotContainsString('secret-req-pass', $roSaveRes['body']);

        $rows = $pdo->query('SELECT userId, type, component, description FROM Notification ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
        self::assertCount(1, $rows);
        self::assertSame($owner->id, (int)$rows[0]['userId']);
        self::assertStringContainsString('RO Requestable Account', (string)$rows[0]['description']);
        self::assertStringContainsString('Please grant edit access for maintenance', (string)$rows[0]['description']);
        self::assertStringNotContainsString('secret-req-pass', (string)$rows[0]['description']);
        self::assertStringNotContainsString('req-acc-login', (string)$rows[0]['description']);

        // Read-only grant must not have been upgraded
        $grantIsEdit = $pdo->prepare('SELECT isEdit FROM AccountToUser WHERE accountId = ? AND userId = ?');
        $grantIsEdit->execute([$roAccountId, $roUser->id]);
        self::assertSame(0, (int)$grantIsEdit->fetchColumn());

        // 3. Global-search user requests access to discovered non-private account without disclosure or duplicate notifications
        self::truncateTable('Notification');

        $globalFormRes = $this->dispatchSessionState(
            'get',
            'account/requestAccess/' . $globalAccountId,
            true,
            true,
            $globalUser,
            $globalProfile,
            ['isAjax' => '1'],
            [],
            ['isGlobalSearch' => true]
        );
        self::assertSame(200, $globalFormRes['code']);
        self::assertStringContainsString('Global Searchable Account', $globalFormRes['body']);
        self::assertStringContainsString('Google', $globalFormRes['body']);
        self::assertStringNotContainsString('secret-req-pass', $globalFormRes['body']);
        self::assertStringNotContainsString('req-acc-login', $globalFormRes['body']);
        self::assertStringNotContainsString('https://req-acc.example.test', $globalFormRes['body']);

        $globalSaveRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $globalAccountId,
            true,
            true,
            $globalUser,
            $globalProfile,
            [],
            ['description' => 'Need access for incident triage'],
            ['isGlobalSearch' => true]
        );
        $this->assertJsonOk($globalSaveRes, 'Request done');

        $globalRows = $pdo->query('SELECT userId, description FROM Notification ORDER BY id ASC')
            ->fetchAll(PDO::FETCH_ASSOC);
        self::assertCount(1, $globalRows);
        self::assertSame($owner->id, (int)$globalRows[0]['userId']);
        self::assertStringNotContainsString('secret-req-pass', (string)$globalRows[0]['description']);

        // No grant was implicitly created for the global-search requester
        $createdGrants = $pdo->prepare('SELECT COUNT(*) FROM AccountToUser WHERE accountId = ? AND userId = ?');
        $createdGrants->execute([$globalAccountId, $globalUser->id]);
        self::assertSame(0, (int)$createdGrants->fetchColumn());
    }

    #[Test]
    public function rateLimitAllowsAtMostThreeRequestsPerUserAndAccountInRolling24Hours(): void
    {
        $pdo = getDbHandler()->getConnection();
        self::truncateTable('Notification');
        self::truncateTable('Track');

        $globalProfile = (new ProfileData())->setAccGlobalSearch(true);
        $userA = $this->seedUser($pdo, 'req_rl_user_a', 5, $globalProfile);
        $userB = $this->seedUser($pdo, 'req_rl_user_b', 5, $globalProfile);
        $owner = $this->seedUser($pdo, 'req_rl_owner', 6, new ProfileData());

        $accountX = $this->seedAccount($pdo, 'RateLimit Target X', $owner->id, 6, $owner->id, 0, 0);
        $accountY = $this->seedAccount($pdo, 'RateLimit Target Y', $owner->id, 6, $owner->id, 0, 0);

        // Seed 3 expired tracks (>24h ago) for (userA, accountX) — must be ignored by rolling 24h window
        $expiredStmt = $pdo->prepare(
            'INSERT INTO Track (userId, source, time) VALUES (?, ?, ?)'
        );
        for ($e = 1; $e <= 3; $e++) {
            $expiredStmt->execute([$userA->id, 'account.request:' . $accountX, time() - 86401 - $e]);
        }

        // First 3 requests for (userA, accountX) succeed
        for ($i = 1; $i <= 3; $i++) {
            $res = $this->dispatchSessionState(
                'post',
                'account/saveRequest/' . $accountX,
                true,
                true,
                $userA,
                $globalProfile,
                [],
                ['description' => 'Request #' . $i],
                ['isGlobalSearch' => true]
            );
            $this->assertJsonOk($res, 'Request done');
        }

        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());

        // 4th request within 24h is rejected with generic limit message and creates no notification or track
        $fourthRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $accountX,
            true,
            true,
            $userA,
            $globalProfile,
            [],
            ['description' => 'Request #4 blocked'],
            ['isGlobalSearch' => true]
        );
        $this->assertJsonDenied($fourthRes, 'Maximum number of requests exceeded');
        self::assertStringNotContainsString('RateLimit Target X', $fourthRes['body']);
        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());

        // Deleting Notifications does NOT reset the Track-based rate limit
        self::truncateTable('Notification');
        $afterDeleteRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $accountX,
            true,
            true,
            $userA,
            $globalProfile,
            [],
            ['description' => 'After notification delete'],
            ['isGlobalSearch' => true]
        );
        $this->assertJsonDenied($afterDeleteRes, 'Maximum number of requests exceeded');
        self::assertSame(0, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());

        // Independent per account: (userA, accountY) still succeeds
        $otherAccountRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $accountY,
            true,
            true,
            $userA,
            $globalProfile,
            [],
            ['description' => 'Different account request'],
            ['isGlobalSearch' => true]
        );
        $this->assertJsonOk($otherAccountRes, 'Request done');

        // Independent per user: (userB, accountX) still succeeds
        $otherUserRes = $this->dispatchSessionState(
            'post',
            'account/saveRequest/' . $accountX,
            true,
            true,
            $userB,
            $globalProfile,
            [],
            ['description' => 'Different user request'],
            ['isGlobalSearch' => true]
        );
        $this->assertJsonOk($otherUserRes, 'Request done');
        self::assertSame(2, (int)$pdo->query('SELECT COUNT(*) FROM Notification')->fetchColumn());
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
