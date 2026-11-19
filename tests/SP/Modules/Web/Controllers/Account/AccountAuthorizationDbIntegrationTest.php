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
use SP\Domain\Core\Crypt\Vault;
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
 * DB-backed characterization tests for Web Account authorization boundaries
 * using real Acl, AccountAcl (without file cache), AccountFilter, SessionContext,
 * and Vault/Crypt against MariaDB.
 */
#[Group('integration')]
final class AccountAuthorizationDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';
    private const DENIED_ACCOUNT = "You don't have permission to access this account";

    #[Test]
    public function readOnlyAccountToUserAndGroupAreBlockedFromEditFormButCanMutateViaSaveEndpoints(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccViewHistory(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccDelete(true);

        $roUser = $this->seedUser($pdo, 'web_ro_user', 5, $profile);
        $userAccountId = $this->seedAccount($pdo, 'Admin RO User Target', 1, 1, 'ro-user-login');
        $groupAccountId = $this->seedAccount($pdo, 'Admin RO Group Target', 1, 1, 'ro-group-login');

        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$userAccountId, $roUser->id]);
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 0)')
            ->execute([$groupAccountId, 5]);

        // Seed a history row for userAccountId to characterize saveEditRestore authorization vs persistence
        $historyId = $this->seedAccountHistory($pdo, $userAccountId, 'Admin RO History Snapshot', 1, 1);

        // 1. View form renders (AccountAcl resultView=true), while Edit and Delete GET forms are denied (resultEdit=false)
        $viewRes = $this->dispatchAsUser('get', 'account/view/' . $userAccountId, $roUser, $profile);
        $this->assertResponseWasDispatched($viewRes);
        self::assertStringContainsString('Admin RO User Target', $viewRes['body']);

        $editFormUserRes = $this->dispatchAsUser('get', 'account/edit/' . $userAccountId, $roUser, $profile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $editFormUserRes['body']);

        $deleteFormUserRes = $this->dispatchAsUser('get', 'account/delete/' . $userAccountId, $roUser, $profile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $deleteFormUserRes['body']);

        $editFormGroupRes = $this->dispatchAsUser('get', 'account/edit/' . $groupAccountId, $roUser, $profile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $editFormGroupRes['body']);

        // 2. Current runtime: SaveEdit / SaveEditPass / SaveDelete only check profile ACL + isVisible() (AccountFilter),
        //    which ignores AccountToUser.isEdit=0 and AccountToUserGroup.isEdit=0!
        $saveEditUser = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $userAccountId,
            $roUser,
            $profile,
            [],
            [
                'name' => 'Mutated By RO User',
                'login' => 'mutated-user',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '1',
                'notes' => 'mutated notes',
            ]
        );
        $this->assertJsonOk($saveEditUser, 'Account updated');
        self::assertSame(
            'Mutated By RO User',
            $this->fetchValue('SELECT name FROM Account WHERE id = ' . $userAccountId)
        );

        $saveEditGroup = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $groupAccountId,
            $roUser,
            $profile,
            [],
            [
                'name' => 'Mutated By RO Group',
                'login' => 'mutated-group',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '1',
                'notes' => 'mutated notes',
            ]
        );
        $this->assertJsonOk($saveEditGroup, 'Account updated');
        self::assertSame(
            'Mutated By RO Group',
            $this->fetchValue('SELECT name FROM Account WHERE id = ' . $groupAccountId)
        );

        $oldPassHash = sha1((string)$this->fetchValue('SELECT pass FROM Account WHERE id = ' . $userAccountId));
        $saveEditPass = $this->dispatchAsUser(
            'post',
            'account/saveEditPass/' . $userAccountId,
            $roUser,
            $profile,
            [],
            [
                'password' => 'ro-new-password',
                'password_repeat' => 'ro-new-password',
                'owner_id' => '1',
            ]
        );
        $this->assertJsonOk($saveEditPass, 'Password updated');
        $newPassHash = sha1((string)$this->fetchValue('SELECT pass FROM Account WHERE id = ' . $userAccountId));
        self::assertNotSame($oldPassHash, $newPassHash);

        // SaveEditRestore bypasses isEdit=0 check as well and reaches restoreModified() -> fails on omitted pass/key
        $restoreRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $historyId . '/' . $userAccountId,
            $roUser,
            $profile
        );
        $restoreJson = $this->decodeJson($restoreRes['body']);
        self::assertSame('ERROR', $restoreJson['status'] ?? null);
        self::assertStringNotContainsString(self::DENIED_ACCOUNT, (string)($restoreJson['description'] ?? ''));

        $saveDeleteUser = $this->dispatchAsUser('post', 'account/saveDelete/' . $userAccountId, $roUser, $profile);
        $this->assertJsonOk($saveDeleteUser, 'Account removed');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $userAccountId));

        $saveDeleteGroup = $this->dispatchAsUser('post', 'account/saveDelete/' . $groupAccountId, $roUser, $profile);
        $this->assertJsonOk($saveDeleteGroup, 'Account removed');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $groupAccountId));
    }

    #[Test]
    public function accountAclAndFilterPrecedenceAndFullGroupAccessBehavior(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true);

        // User primary group = 5, secondary group = 4 & 6
        $user = $this->seedUser($pdo, 'web_prec_user', 5, $profile);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$user->id, 4]);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$user->id, 6]);

        // Case 1: AccountToUser(isEdit=0) vs AccountToUserGroup(isEdit=1) + secondary UserToUserGroup(6) main group
        // AccountAcl short-circuits on AccountToUser (resultEdit=false), while AccountFilter grants visibility.
        $precAccountId = $this->seedAccount($pdo, 'Precedence Account', 1, 6, 'prec-login');
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$precAccountId, $user->id]);
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 1)')
            ->execute([$precAccountId, 5]);

        $precView = $this->dispatchAsUser('get', 'account/view/' . $precAccountId, $user, $profile);
        $this->assertResponseWasDispatched($precView);
        self::assertStringContainsString('Precedence Account', $precView['body']);

        $precEditForm = $this->dispatchAsUser('get', 'account/edit/' . $precAccountId, $user, $profile);
        self::assertStringContainsString(self::DENIED_ACCOUNT, $precEditForm['body']);

        $precSaveEdit = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $precAccountId,
            $user,
            $profile,
            [],
            [
                'name' => 'Precedence Mutated',
                'login' => 'prec-login',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '6',
            ]
        );
        $this->assertJsonOk($precSaveEdit, 'Account updated');

        // Case 2: Secondary AccountToUserGroup(4, isEdit=1) via UserToUserGroup(4) with isAccountFullGroupAccess=false vs true
        $secGroupAccountId = $this->seedAccount($pdo, 'Secondary Group FullAccess Account', 1, 1, 'secgrp-login');
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 1)')
            ->execute([$secGroupAccountId, 4]);

        // With isAccountFullGroupAccess = false: both Edit form (AccountAcl) and SaveEdit (AccountFilter) deny
        $deniedEditForm = $this->dispatchAsUser(
            'get',
            'account/edit/' . $secGroupAccountId,
            $user,
            $profile,
            [],
            [],
            ['isAccountFullGroupAccess' => false]
        );
        self::assertStringContainsString(self::DENIED_ACCOUNT, $deniedEditForm['body']);

        $deniedSave = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $secGroupAccountId,
            $user,
            $profile,
            [],
            [
                'name' => 'Should Not Update',
                'login' => 'x',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '1',
            ],
            ['isAccountFullGroupAccess' => false]
        );
        $this->assertJsonDenied($deniedSave, self::DENIED_ACCOUNT);

        // With isAccountFullGroupAccess = true: both Edit form and SaveEdit succeed
        $allowedEditForm = $this->dispatchAsUser(
            'get',
            'account/edit/' . $secGroupAccountId,
            $user,
            $profile,
            [],
            [],
            ['isAccountFullGroupAccess' => true]
        );
        $this->assertResponseWasDispatched($allowedEditForm);
        self::assertStringContainsString('Secondary Group FullAccess Account', $allowedEditForm['body']);

        $allowedSave = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $secGroupAccountId,
            $user,
            $profile,
            [],
            [
                'name' => 'Updated Via FullGroupAccess',
                'login' => 'secgrp-login',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '1',
            ],
            ['isAccountFullGroupAccess' => true]
        );
        $this->assertJsonOk($allowedSave, 'Account updated');

        // Case 3: Direct main-group secondary membership (Account.userGroupId=6 in UserToUserGroup)
        // grants view and edit even with isAccountFullGroupAccess=false
        $mainGrpSecAccountId = $this->seedAccount($pdo, 'Main Group Via Secondary Membership', 1, 6, 'maingrp-sec');
        $mainGrpEditForm = $this->dispatchAsUser(
            'get',
            'account/edit/' . $mainGrpSecAccountId,
            $user,
            $profile,
            [],
            [],
            ['isAccountFullGroupAccess' => false]
        );
        $this->assertResponseWasDispatched($mainGrpEditForm);
        self::assertStringContainsString('Main Group Via Secondary Membership', $mainGrpEditForm['body']);
    }

    #[Test]
    public function privateAndPrivateGroupBypassWebViewEditDeleteButEnforceFilterOnPassAndSaveMutations(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccDelete(true);

        $privAccountId = $this->seedAccount($pdo, 'Admin Private Secret', 1, 6, 'priv-secret-login', 1, 0);
        $privGrpAccountId = $this->seedAccount($pdo, 'Group6 PrivateGroup Secret', 1, 6, 'privgrp-secret-login', 0, 1);

        $sharedUser = $this->seedUser($pdo, 'web_shared_priv', 5, $profile);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')
            ->execute([$privAccountId, $sharedUser->id]);
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')
            ->execute([$privGrpAccountId, $sharedUser->id]);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$sharedUser->id, 6]);

        $adminAppUser = $this->seedUser($pdo, 'web_admin_app', 5, $profile, true, false);
        $adminAccUser = $this->seedUser($pdo, 'web_admin_acc', 5, $profile, false, true);

        foreach ([
            'shared user' => $sharedUser,
            'isAdminApp' => $adminAppUser,
            'isAdminAcc' => $adminAccUser,
        ] as $label => $actor) {
            foreach ([
                'isPrivate=1' => ['id' => $privAccountId, 'name' => 'Admin Private Secret', 'login' => 'priv-secret-login'],
                'isPrivateGroup=1' => ['id' => $privGrpAccountId, 'name' => 'Group6 PrivateGroup Secret', 'login' => 'privgrp-secret-login'],
            ] as $accLabel => $acc) {
                $id = $acc['id'];

                // Web view/edit/delete load via unfiltered getByIdEnriched() + AccountAcl (which does NOT check isPrivate/isPrivateGroup)
                $viewRes = $this->dispatchAsUser('get', 'account/view/' . $id, $actor, $profile);
                $this->assertResponseWasDispatched($viewRes);
                self::assertStringContainsString($acc['name'], $viewRes['body'], "$label viewing $accLabel");
                self::assertStringContainsString($acc['login'], $viewRes['body'], "$label login leak on $accLabel");

                $editRes = $this->dispatchAsUser('get', 'account/edit/' . $id, $actor, $profile);
                $this->assertResponseWasDispatched($editRes);
                self::assertStringContainsString($acc['name'], $editRes['body'], "$label edit form on $accLabel");

                $delRes = $this->dispatchAsUser('get', 'account/delete/' . $id, $actor, $profile);
                $this->assertResponseWasDispatched($delRes);
                self::assertStringContainsString($acc['name'], $delRes['body'], "$label delete form on $accLabel");

                // ViewPass / SaveEdit / SaveDelete use AccountFilter (enforcing isPrivate and primary-group isPrivateGroup)
                $viewPassRes = $this->dispatchAsUser('post', 'account/viewPass/' . $id, $actor, $profile);
                $viewPassJson = $this->decodeJson($viewPassRes['body']);
                self::assertSame('ERROR', $viewPassJson['status'] ?? null, "$label viewPass on $accLabel");

                $saveEditRes = $this->dispatchAsUser(
                    'post',
                    'account/saveEdit/' . $id,
                    $actor,
                    $profile,
                    [],
                    [
                        'name' => 'ShouldFail',
                        'login' => 'x',
                        'client_id' => '1',
                        'category_id' => '1',
                        'owner_id' => '1',
                        'main_usergroup_id' => '6',
                    ]
                );
                $this->assertJsonDenied($saveEditRes, self::DENIED_ACCOUNT);

                $saveDeleteRes = $this->dispatchAsUser('post', 'account/saveDelete/' . $id, $actor, $profile);
                $this->assertJsonDenied($saveDeleteRes, self::DENIED_ACCOUNT);
            }
        }

        // Verify same primary userGroupId user CAN access isPrivateGroup=1 without AccountToUser
        $samePrimaryGroupUser = $this->seedUser($pdo, 'web_same_primary_grp', 6, $profile);
        $sameGrpPass = $this->dispatchAsUser('post', 'account/viewPass/' . $privGrpAccountId, $samePrimaryGroupUser, $profile);
        $this->assertJsonOk($sameGrpPass);
    }

    #[Test]
    public function globalSearchMatchesHiddenFieldsWhileRedactingRowDetailsAndExcludingPrivateAccounts(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccGlobalSearch(true);

        $searcher = $this->seedUser($pdo, 'web_gsearch_user', 5, $profile);

        $nonPrivId = $this->seedAccount(
            $pdo,
            'Hidden Inaccessible Account',
            1,
            1,
            'gsearch-secret-login',
            0,
            0,
            'https://gsearch-secret.example.test',
            'gsearch-secret-notes-token'
        );
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$nonPrivId]);

        $this->seedAccount(
            $pdo,
            'Hidden Private Should Exclude',
            1,
            1,
            'gsearch-priv-login',
            1,
            0,
            'https://gsearch-priv.example.test',
            'gsearch-secret-notes-token'
        );
        $this->seedAccount(
            $pdo,
            'Hidden PrivateGroup Should Exclude',
            1,
            1,
            'gsearch-privgrp-login',
            0,
            1,
            'https://gsearch-privgrp.example.test',
            'gsearch-secret-notes-token'
        );

        foreach (['gsearch-secret-notes-token', 'gsearch-secret-login', 'gsearch-secret.example.test'] as $term) {
            $res = $this->dispatchAsUser(
                'post',
                'account/search',
                $searcher,
                $profile,
                [],
                [
                    'search' => $term,
                    'gsearch' => '1',
                    'isAjax' => '1',
                    'start' => '0',
                    'skey' => '0',
                    'sorder' => '0',
                ],
                ['isGlobalSearch' => true]
            );

            $json = $this->assertJsonOk($res);
            $html = (string)($json['data']['html'] ?? '');

            // Matches inaccessible non-private account on hidden login/url/notes
            self::assertStringContainsString('Hidden Inaccessible Account', $html, "Term: $term");
            self::assertStringContainsString('Google', $html);
            self::assertStringContainsString('Web', $html);
            // Current runtime: AccountToTagRepository maps Simple::class which ItemDataTrait filters out (tags-box stays empty)
            self::assertStringNotContainsString('data-tag-id="1"', $html);

            // Redacts login, url, and view/viewPass action routes while rendering requestAccess
            self::assertStringNotContainsString('gsearch-secret-login', $html);
            self::assertStringNotContainsString('https://gsearch-secret.example.test', $html);
            self::assertStringContainsString('data-action-route="account/requestAccess"', $html);
            self::assertStringNotContainsString('data-action-route="account/view"', $html);
            self::assertStringNotContainsString('data-action-route="account/viewPass"', $html);
            self::assertStringNotContainsString('data-action-route="account/edit"', $html);

            // Excludes isPrivate=1 and isPrivateGroup=1 accounts
            self::assertStringNotContainsString('Hidden Private Should Exclude', $html);
            self::assertStringNotContainsString('Hidden PrivateGroup Should Exclude', $html);
        }
    }

    #[Test]
    public function linkedParentIdHasNoAuthorizationCheckAndViewPassDecryptsChildPasswordOnly(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true);

        $user = $this->seedUser($pdo, 'web_child_creator', 5, $profile);

        // Inaccessible isPrivate=1 parent owned by admin (encrypted pass in fixture = decryptable by admin, not accessible to $user)
        $privateParentId = $this->seedAccount($pdo, 'Private Parent Account', 1, 1, 'parent-login', 1, 0);

        // Non-admin user creates a child linked to inaccessible private parent without supplying password
        $createRes = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => 'Child Linked To Private Parent',
                'login' => 'child-login',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$privateParentId,
                'password' => '',
                'password_repeat' => '',
            ]
        );
        $createJson = $this->assertJsonOk($createRes, 'Account created');
        $childId = (int)($createJson['data']['itemId'] ?? 0);
        self::assertGreaterThan(0, $childId);
        self::assertSame($privateParentId, (int)$this->fetchValue('SELECT parentId FROM Account WHERE id = ' . $childId));

        // Direct viewPass on the private parent itself is denied
        $parentPassRes = $this->dispatchAsUser('post', 'account/viewPass/' . $privateParentId, $user, $profile);
        $parentPassJson = $this->decodeJson($parentPassRes['body']);
        self::assertSame('ERROR', $parentPassJson['status'] ?? null);

        // viewPass/{child}/{parent} succeeds without checking $parentId access, sets Linked Account icon,
        // and decrypts child's own password (empty), NOT parent's password
        $childLinkedPassRes = $this->dispatchAsUser(
            'post',
            'account/viewPass/' . $childId . '/' . $privateParentId,
            $user,
            $profile
        );
        $childLinkedJson = $this->assertJsonOk($childLinkedPassRes);
        $linkedHtml = (string)($childLinkedJson['data']['html'] ?? '');
        self::assertStringContainsString('child-login', $linkedHtml);
        self::assertStringContainsString('Linked Account', $linkedHtml);
        self::assertStringNotContainsString('parent-secret-pass', $linkedHtml);

        $childUnlinkedPassRes = $this->dispatchAsUser(
            'post',
            'account/viewPass/' . $childId . '/0',
            $user,
            $profile
        );
        $childUnlinkedJson = $this->assertJsonOk($childUnlinkedPassRes);
        self::assertStringNotContainsString('Linked Account', (string)($childUnlinkedJson['data']['html'] ?? ''));
    }

    /**
     * @param array<string, mixed> $paramsGet
     * @param array<string, mixed> $paramsPost
     * @param array<string, mixed> $configOverrides
     * @return array{body: string, code: int}
     */
    private function dispatchAsUser(
        string      $method,
        string      $route,
        UserDto     $user,
        ProfileData $profile,
        array       $paramsGet = [],
        array       $paramsPost = [],
        array       $configOverrides = []
    ): array {
        $context = $this->buildSessionContext($user, $profile);
        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            array_merge($this->getConfigData(), $configOverrides)
        );
        $configFileService = $this->createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        return $this->dispatch(
            $method,
            $route,
            $paramsGet,
            $paramsPost,
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
    }

    private function buildSessionContext(UserDto $user, ProfileData $profile): SessionContext
    {
        $sidStartTime = time();
        $vault = self::createStub(\SP\Domain\Core\Crypt\VaultInterface::class);
        $vault->method('getData')->willReturn(self::MASTER_PASS);
        $vault->method('reKey')->willReturnSelf();
        $accountsCache = null;
        $transient = ['_masterpass' => self::MASTER_PASS];

        $context = self::createStub(SessionContext::class);
        $context->method('isLoggedIn')->willReturn(true);
        $context->method('getAuthCompleted')->willReturn(true);
        $context->method('getUserData')->willReturn($user);
        $context->method('getUserProfile')->willReturn($profile);
        $context->method('getCSRF')->willReturn(null);
        $context->method('getSearchFilters')->willReturn(null);
        $context->method('getSidStartTime')->willReturn($sidStartTime);
        $context->method('getVault')->willReturn($vault);
        $context->method('getAccountsCache')->willReturnCallback(static function () use (&$accountsCache) {
            return $accountsCache;
        });
        $context->method('setAccountsCache')->willReturnCallback(static function (array $cache) use (&$accountsCache): void {
            $accountsCache = $cache;
        });
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

    private function seedUser(
        PDO         $pdo,
        string      $login,
        int         $userGroupId,
        ProfileData $profile,
        bool        $isAdminApp = false,
        bool        $isAdminAcc = false
    ): UserDto {
        $profileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (?, ?)');
        $profileStmt->execute(['Profile ' . $login, Serde::serialize($profile)]);
        $profileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate, lastUpdateMPass, preferences)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 0, 0, 0, 0, 1528236611, ?)'
        );
        $userStmt->execute([
            'Synthetic ' . $login,
            $userGroupId,
            $login,
            Hash::hashKey('synthetic-pass-' . $login),
            $login . '@example.test',
            $profileId,
            $isAdminApp ? 1 : 0,
            $isAdminAcc ? 1 : 0,
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
            'isAdminApp' => $isAdminApp,
            'isAdminAcc' => $isAdminAcc,
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
        string $login,
        int    $isPrivate = 0,
        int    $isPrivateGroup = 0,
        string $url = 'https://example.test',
        string $notes = 'synthetic notes',
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

    private function seedAccountHistory(
        PDO    $pdo,
        int    $accountId,
        string $name,
        int    $userId,
        int    $userGroupId
    ): int {
        $crypt = new Crypt();
        $key = $crypt->makeSecuredKey(self::MASTER_PASS);
        $encPass = $crypt->encrypt('history-pass', $key, self::MASTER_PASS);

        $stmt = $pdo->prepare(
            'INSERT INTO AccountHistory (accountId, userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, dateEdit, isModify, isDeleted, mPassHash, passDate)
             VALUES (?, ?, ?, ?, 1, ?, 1, ?, ?, ?, ?, ?, NOW(), NOW(), 1, 0, ?, UNIX_TIMESTAMP())'
        );
        $stmt->execute([
            $accountId,
            $userGroupId,
            $userId,
            $userId,
            $name,
            'hist-login',
            'https://hist.example.test',
            $encPass,
            $key,
            'hist notes',
            Hash::hashKey(self::MASTER_PASS),
        ]);

        return (int)$pdo->lastInsertId();
    }
}
