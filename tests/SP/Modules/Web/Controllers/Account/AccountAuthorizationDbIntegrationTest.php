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

    protected function getConfigData(): array
    {
        return array_merge(
            parent::getConfigData(),
            [
                'isPublinksEnabled' => true,
                'getPublinksMaxTime' => 600,
                'getPublinksMaxViews' => 3,
            ]
        );
    }

    #[Test]
    public function accountCreationRejectsClientUsedOnlyByInaccessibleAccounts(): void
    {
        $pdo = getDbHandler()->getConnection();
        $profile = (new ProfileData())->setAccView(true)->setAccAdd(true)->setAccEdit(true);
        $user = $this->seedUser($pdo, 'web_client_visibility', 5, $profile);
        $pdo->exec("INSERT INTO Client (name, hash, description, isGlobal) VALUES ('Hidden Synthetic Client', 'hidden-client-hash', '', 0)");
        $clientId = (int)$pdo->lastInsertId();
        $hiddenId = $this->seedAccount($pdo, 'Hidden Client Owner', 1, 1, 'hidden-client-owner');
        $pdo->prepare('UPDATE Account SET clientId = ? WHERE id = ?')->execute([$clientId, $hiddenId]);

        $response = $this->dispatchAsUser('post', 'account/saveCreate', $user, $profile, [], [
            'name' => 'Denied Client Selection', 'login' => 'denied-client-selection',
            'client_id' => (string)$clientId, 'category_id' => '1',
            'password' => 'synthetic-password', 'password_repeat' => 'synthetic-password',
        ]);

        self::assertSame('ERROR', $this->decodeJson($response['body'])['status'] ?? null);
        self::assertSame(0, (int)$pdo->query("SELECT COUNT(*) FROM Account WHERE name = 'Denied Client Selection'")->fetchColumn());
        $missing = $this->dispatchAsUser('post', 'account/saveCreate', $user, $profile, [], [
            'name' => 'Missing Client Selection', 'login' => 'missing-client-selection',
            'client_id' => '999999', 'category_id' => '1',
            'password' => 'synthetic-password', 'password_repeat' => 'synthetic-password',
        ]);
        self::assertSame($this->decodeJson($missing['body'])['description'] ?? null, $this->decodeJson($response['body'])['description'] ?? null);

        $managerProfile = (new ProfileData())->setAccView(true)->setAccAdd(true)->setMgmCustomers(true);
        $manager = $this->seedUser($pdo, 'web_client_manager', 6, $managerProfile);
        $managerCreate = $this->dispatchAsUser('post', 'account/saveCreate', $manager, $managerProfile, [], [
            'name' => 'Manager Client Selection', 'login' => 'manager-client-selection',
            'client_id' => (string)$clientId, 'category_id' => '1',
            'password' => 'synthetic-password', 'password_repeat' => 'synthetic-password',
        ]);
        $this->assertJsonOk($managerCreate, 'Account created');

        $pdo->exec("INSERT INTO Client (name, hash, description, isGlobal) VALUES ('Unused Synthetic Client', 'unused-client-hash', '', 0)");
        $unusedId = (int)$pdo->lastInsertId();
        $allowed = $this->dispatchAsUser('post', 'account/saveCreate', $user, $profile, [], [
            'name' => 'Allowed Unused Client', 'login' => 'allowed-unused-client',
            'client_id' => (string)$unusedId, 'category_id' => '1',
            'password' => 'synthetic-password', 'password_repeat' => 'synthetic-password',
        ]);
        $created = $this->assertJsonOk($allowed, 'Account created');
        $accountId = (int)$created['data']['itemId'];
        self::assertSame($unusedId, (int)$pdo->query('SELECT clientId FROM Account WHERE id = ' . $accountId)->fetchColumn());

        $deniedEdit = $this->dispatchAsUser('post', 'account/saveEdit/' . $accountId, $user, $profile, [], [
            'name' => 'Denied Client Change', 'client_id' => (string)$clientId, 'category_id' => '1',
        ]);
        self::assertSame('ERROR', $this->decodeJson($deniedEdit['body'])['status'] ?? null);
        self::assertSame($unusedId, (int)$pdo->query('SELECT clientId FROM Account WHERE id = ' . $accountId)->fetchColumn());
        $sameClient = $this->dispatchAsUser('post', 'account/saveEdit/' . $accountId, $user, $profile, [], [
            'name' => 'Retained Current Client', 'client_id' => (string)$unusedId, 'category_id' => '1',
        ]);
        $this->assertJsonOk($sameClient, 'Account updated');

        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')->execute([$hiddenId, $user->id]);
        $shared = $this->dispatchAsUser('post', 'account/saveCreate', $user, $profile, [], [
            'name' => 'Allowed Shared Client', 'login' => 'allowed-shared-client',
            'client_id' => (string)$clientId, 'category_id' => '1',
            'password' => 'synthetic-password', 'password_repeat' => 'synthetic-password',
        ]);
        $this->assertJsonOk($shared, 'Account created');
    }

    #[Test]
    public function readOnlyAccountToUserAndGroupAreDeniedOnEditFormAndAllSaveMutations(): void
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

        // 2. SaveEdit / SaveEditPass / SaveEditRestore / SaveDelete must also deny read-only grants
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
        $this->assertJsonDenied($saveEditUser, self::DENIED_ACCOUNT);
        self::assertSame(
            'Admin RO User Target',
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
        $this->assertJsonDenied($saveEditGroup, self::DENIED_ACCOUNT);
        self::assertSame(
            'Admin RO Group Target',
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
        $this->assertJsonDenied($saveEditPass, self::DENIED_ACCOUNT);
        $newPassHash = sha1((string)$this->fetchValue('SELECT pass FROM Account WHERE id = ' . $userAccountId));
        self::assertSame($oldPassHash, $newPassHash);

        $restoreRes = $this->dispatchAsUser(
            'post',
            'account/saveEditRestore/' . $historyId . '/' . $userAccountId,
            $roUser,
            $profile
        );
        $this->assertJsonDenied($restoreRes, self::DENIED_ACCOUNT);

        $saveDeleteUser = $this->dispatchAsUser('post', 'account/saveDelete/' . $userAccountId, $roUser, $profile);
        $this->assertJsonDenied($saveDeleteUser, self::DENIED_ACCOUNT);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $userAccountId));

        $saveDeleteGroup = $this->dispatchAsUser('post', 'account/saveDelete/' . $groupAccountId, $roUser, $profile);
        $this->assertJsonDenied($saveDeleteGroup, self::DENIED_ACCOUNT);
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM Account WHERE id = ' . $groupAccountId));
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

        // Case 1: Secondary UserToUserGroup(6) main group outranks AccountToUser(isEdit=0)
        $precAccountId = $this->seedAccount($pdo, 'Precedence Account', 1, 6, 'prec-login');
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$precAccountId, $user->id]);
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 1)')
            ->execute([$precAccountId, 5]);

        $precView = $this->dispatchAsUser('get', 'account/view/' . $precAccountId, $user, $profile);
        $this->assertResponseWasDispatched($precView);
        self::assertStringContainsString('Precedence Account', $precView['body']);

        $precEditForm = $this->dispatchAsUser('get', 'account/edit/' . $precAccountId, $user, $profile);
        $this->assertResponseWasDispatched($precEditForm);
        self::assertStringContainsString('Precedence Account', $precEditForm['body']);
        self::assertStringContainsString('account/saveEdit', $precEditForm['body']);

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
    public function privateAndPrivateGroupEnforceOwnerAndPrimaryGroupAcrossAllWebEndpoints(): void
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

                $viewRes = $this->dispatchAsUser('get', 'account/view/' . $id, $actor, $profile);
                self::assertStringContainsString(self::DENIED_ACCOUNT, $viewRes['body'], "$label viewing $accLabel");
                self::assertStringNotContainsString($acc['name'], $viewRes['body'], "$label name leak on $accLabel");
                self::assertStringNotContainsString($acc['login'], $viewRes['body'], "$label login leak on $accLabel");

                $editRes = $this->dispatchAsUser('get', 'account/edit/' . $id, $actor, $profile);
                self::assertStringContainsString(self::DENIED_ACCOUNT, $editRes['body'], "$label edit form on $accLabel");
                self::assertStringNotContainsString($acc['name'], $editRes['body'], "$label edit name leak on $accLabel");

                $delRes = $this->dispatchAsUser('get', 'account/delete/' . $id, $actor, $profile);
                self::assertStringContainsString(self::DENIED_ACCOUNT, $delRes['body'], "$label delete form on $accLabel");
                self::assertStringNotContainsString($acc['name'], $delRes['body'], "$label delete name leak on $accLabel");

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
        $sameGrpView = $this->dispatchAsUser('get', 'account/view/' . $privGrpAccountId, $samePrimaryGroupUser, $profile);
        $this->assertResponseWasDispatched($sameGrpView);
        self::assertStringContainsString('Group6 PrivateGroup Secret', $sameGrpView['body']);

        $sameGrpEdit = $this->dispatchAsUser('get', 'account/edit/' . $privGrpAccountId, $samePrimaryGroupUser, $profile);
        $this->assertResponseWasDispatched($sameGrpEdit);
        self::assertStringContainsString('account/saveEdit', $sameGrpEdit['body']);

        $sameGrpPass = $this->dispatchAsUser('post', 'account/viewPass/' . $privGrpAccountId, $samePrimaryGroupUser, $profile);
        $this->assertJsonOk($sameGrpPass);
    }

    #[Test]
    public function multiSecondaryGroupGrantAllowsEditWhenSecondGrantHasEditFlag(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true);

        $user = $this->seedUser($pdo, 'web_multi_grp_user', 5, $profile);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$user->id, 4]);
        $pdo->prepare('INSERT INTO UserToUserGroup (userId, userGroupId) VALUES (?, ?)')
            ->execute([$user->id, 6]);

        $accountId = $this->seedAccount($pdo, 'Multi Group Edit Target', 1, 1, 'multi-grp-login');
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 0)')
            ->execute([$accountId, 4]);
        $pdo->prepare('INSERT INTO AccountToUserGroup (accountId, userGroupId, isEdit) VALUES (?, ?, 1)')
            ->execute([$accountId, 6]);

        $editForm = $this->dispatchAsUser(
            'get',
            'account/edit/' . $accountId,
            $user,
            $profile,
            [],
            [],
            ['isAccountFullGroupAccess' => true]
        );
        $this->assertResponseWasDispatched($editForm);
        self::assertStringContainsString('account/saveEdit', $editForm['body']);

        $saveEdit = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $accountId,
            $user,
            $profile,
            [],
            [
                'name' => 'Multi Group Updated',
                'login' => 'multi-grp-login',
                'client_id' => '1',
                'category_id' => '1',
            ],
            ['isAccountFullGroupAccess' => true]
        );
        $this->assertJsonOk($saveEdit, 'Account updated');
        self::assertSame('Multi Group Updated', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accountId));
    }

    #[Test]
    public function nonOwnerAndAccAdminCannotChangePrivacyFlagsOnEdit(): void
    {
        $pdo = getDbHandler()->getConnection();

        $ownerProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccPrivate(true)
            ->setAccPrivateGroup(true);

        $owner = $this->seedUser($pdo, 'web_priv_owner', 5, $ownerProfile);
        $sharedEditor = $this->seedUser($pdo, 'web_priv_shared_editor', 6, $ownerProfile);
        $accAdmin = $this->seedUser($pdo, 'web_priv_acc_admin', 6, $ownerProfile, false, true);

        $accountId = $this->seedAccount($pdo, 'Privacy Lock Target', $owner->id, 5, 'priv-lock');
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 1)')
            ->execute([$accountId, $sharedEditor->id]);

        foreach ([$sharedEditor, $accAdmin] as $actor) {
            $res = $this->dispatchAsUser(
                'post',
                'account/saveEdit/' . $accountId,
                $actor,
                $ownerProfile,
                [],
                [
                    'name' => 'Should Not Set Private',
                    'client_id' => '1',
                    'category_id' => '1',
                    'private_enabled' => '1',
                ]
            );
            $json = $this->decodeJson($res['body']);
            self::assertSame('ERROR', $json['status'] ?? null);
            self::assertStringNotContainsString('Integrity constraint', $res['body']);
            self::assertSame(0, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $accountId));
            self::assertSame('Privacy Lock Target', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $accountId));

            $resGrp = $this->dispatchAsUser(
                'post',
                'account/saveEdit/' . $accountId,
                $actor,
                $ownerProfile,
                [],
                [
                    'name' => 'Should Not Set PrivateGroup',
                    'client_id' => '1',
                    'category_id' => '1',
                    'private_group_enabled' => '1',
                ]
            );
            $jsonGrp = $this->decodeJson($resGrp['body']);
            self::assertSame('ERROR', $jsonGrp['status'] ?? null);
            self::assertStringNotContainsString('Integrity constraint', $resGrp['body']);
            self::assertSame(0, (int)$this->fetchValue('SELECT isPrivateGroup FROM Account WHERE id = ' . $accountId));
        }

        // Owner CAN toggle isPrivate and isPrivateGroup on their own account
        $ownerSetRes = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $accountId,
            $owner,
            $ownerProfile,
            [],
            [
                'name' => 'Owner Set Private',
                'client_id' => '1',
                'category_id' => '1',
                'private_enabled' => '1',
                'private_group_enabled' => '1',
            ]
        );
        $this->assertJsonOk($ownerSetRes, 'Account updated');
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $accountId));
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivateGroup FROM Account WHERE id = ' . $accountId));

        // Owner submitting with hidden false input ('0') clears isPrivate & isPrivateGroup
        $ownerClearRes = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $accountId,
            $owner,
            $ownerProfile,
            [],
            [
                'name' => 'Owner Cleared Private',
                'client_id' => '1',
                'category_id' => '1',
                'private_enabled' => '0',
                'private_group_enabled' => '0',
            ]
        );
        $this->assertJsonOk($ownerClearRes, 'Account updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = ' . $accountId));
        self::assertSame(0, (int)$this->fetchValue('SELECT isPrivateGroup FROM Account WHERE id = ' . $accountId));
    }

    #[Test]
    public function globalSearchMatchesNameClientCategoryWhileRedactingDetailsExcludingPrivateAndPreventingHiddenFilterLeaks(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccGlobalSearch(true);

        $searcher = $this->seedUser($pdo, 'web_gsearch_user', 5, $profile);

        // Accessible account with tags, notes, login, url
        $accessibleId = $this->seedAccount(
            $pdo,
            'Visible Search Account',
            $searcher->id,
            5,
            'visible-secret-login',
            0,
            0,
            'https://visible-secret.example.test',
            'visible-secret-notes-token'
        );
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 2)')->execute([$accessibleId]);

        // Inaccessible non-private account with parentId > 0, tag 1, file attachment, and expired password
        $parentId = $this->seedAccount($pdo, 'Hidden Parent Source', 1, 1, 'hidden-parent-login');
        $nonPrivId = $this->seedAccount(
            $pdo,
            'Hidden Inaccessible Account',
            1,
            1,
            'gsearch-secret-login',
            0,
            0,
            'https://gsearch-secret.example.test',
            'gsearch-secret-notes-token',
            'parent-secret-pass',
            $parentId,
            time() - 600
        );
        $pdo->prepare('INSERT INTO AccountToTag (accountId, tagId) VALUES (?, 1)')->execute([$nonPrivId]);
        $pdo->prepare(
            "INSERT INTO AccountFile (accountId, name, size, content, extension, type, thumb)
             VALUES (?, 'secret-attachment.pdf', 4, 'test', 'pdf', 'application/pdf', '')"
        )->execute([$nonPrivId]);

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

        // 1. Name, client:, category:, name_regex: match inaccessible non-private account, with strict redaction
        foreach (['Hidden Inaccessible', 'client:Google', 'category:Web'] as $term) {
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

            self::assertStringContainsString('Hidden Inaccessible Account', $html, "Term: $term");
            self::assertStringNotContainsString('gsearch-secret-login', $html);
            self::assertStringNotContainsString('https://gsearch-secret.example.test', $html);
            self::assertStringNotContainsString('data-parent-id="' . $parentId . '"', $html);

            $cards = explode('<div class="account-label round shadow">', $html);
            $hiddenCard = null;
            foreach ($cards as $card) {
                if (str_contains($card, 'Hidden Inaccessible Account')) {
                    $hiddenCard = $card;
                    break;
                }
            }
            self::assertNotNull($hiddenCard, "Missing card for Hidden Inaccessible Account on term: $term");
            self::assertStringContainsString('Google', $hiddenCard);
            self::assertStringContainsString('Web', $hiddenCard);
            self::assertStringNotContainsString('data-tag-id="1"', $hiddenCard);
            self::assertStringContainsString('data-action-route="account/requestAccess"', $hiddenCard);
            self::assertStringNotContainsString('data-action-route="account/view"', $hiddenCard);
            self::assertStringNotContainsString('data-action-route="account/viewPass"', $hiddenCard);
            self::assertStringNotContainsString('data-action-route="account/edit"', $hiddenCard);

            // Private / group-private excluded
            self::assertStringNotContainsString('Hidden Private Should Exclude', $html);
            self::assertStringNotContainsString('Hidden PrivateGroup Should Exclude', $html);
        }

        // 2. Hidden-field text queries & sensitive SQL filters do NOT reveal inaccessible accounts
        foreach ([
            ['search' => 'gsearch-secret-notes-token'],
            ['search' => 'gsearch-secret-login'],
            ['search' => 'gsearch-secret.example.test'],
            ['search' => 'file:secret-attachment.pdf'],
            ['search' => 'owner:admin'],
            ['search' => 'user:admin'],
            ['search' => 'group:Admins'],
            ['search' => 'maingroup:Admins'],
            ['search' => 'is:expired'],
            ['search' => 'Hidden Inaccessible', 'tags' => ['1']],
        ] as $postOverrides) {
            $res = $this->dispatchAsUser(
                'post',
                'account/search',
                $searcher,
                $profile,
                [],
                array_merge([
                    'gsearch' => '1',
                    'isAjax' => '1',
                    'start' => '0',
                    'skey' => '0',
                    'sorder' => '0',
                ], $postOverrides),
                ['isGlobalSearch' => true]
            );

            $json = $this->assertJsonOk($res);
            $html = (string)($json['data']['html'] ?? '');
            self::assertStringNotContainsString(
                'Hidden Inaccessible Account',
                $html,
                'Filter should not match inaccessible account: ' . json_encode($postOverrides)
            );
        }

        // 3. Accessible account still matches hidden notes & tags in global search, and renders its tags
        $visRes = $this->dispatchAsUser(
            'post',
            'account/search',
            $searcher,
            $profile,
            [],
            [
                'search' => 'visible-secret-notes-token',
                'tags' => ['2'],
                'gsearch' => '1',
                'isAjax' => '1',
                'start' => '0',
                'skey' => '3',
                'sorder' => '0',
            ],
            ['isGlobalSearch' => true]
        );
        $visJson = $this->assertJsonOk($visRes);
        $visHtml = (string)($visJson['data']['html'] ?? '');
        self::assertStringContainsString('Visible Search Account', $visHtml);
        self::assertStringContainsString('data-tag-id="2"', $visHtml);

        // 4. Sorting by login (skey=3) or url (skey=4) in global search executes safely without SQL error
        $sortRes = $this->dispatchAsUser(
            'post',
            'account/search',
            $searcher,
            $profile,
            [],
            [
                'search' => 'Hidden Inaccessible',
                'gsearch' => '1',
                'isAjax' => '1',
                'start' => '0',
                'skey' => '3',
                'sorder' => '0',
            ],
            ['isGlobalSearch' => true]
        );
        $sortJson = $this->assertJsonOk($sortRes);
        self::assertStringContainsString('Hidden Inaccessible Account', (string)($sortJson['data']['html'] ?? ''));
    }

    #[Test]
    public function linkedParentEnforcesAuthorizationInheritanceValidationAndRevokesPublicLinks(): void
    {
        $pdo = getDbHandler()->getConnection();

        $profile = (new ProfileData())
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);

        $user = $this->seedUser($pdo, 'web_child_creator', 5, $profile);

        $privateParentId = $this->seedAccount($pdo, 'Private Parent Account', 1, 1, 'priv-parent-login', 1, 0);
        $sharedParentId = $this->seedAccount(
            $pdo,
            'Shared Parent Account',
            1,
            1,
            'shared-parent-login',
            0,
            0,
            'https://parent.test',
            'parent notes',
            'parent-secret-pass'
        );
        $otherOwnedId = $this->seedAccount($pdo, 'Other Owned Account', $user->id, 5, 'other-owned-login');

        // 1. Rejects creating child linked to inaccessible parent
        $deniedCreate = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => 'Denied Private Child',
                'login' => 'child-login',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$privateParentId,
            ]
        );
        $this->assertJsonDenied($deniedCreate, self::DENIED_ACCOUNT);

        // 2. Rejects whitespace-only name and notes > 5000 chars
        $blankNameRes = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => "  \t  ",
                'login' => 'blank-name',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'valid-pass-1',
                'password_repeat' => 'valid-pass-1',
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($blankNameRes['body'])['status'] ?? null);

        $longNotesRes = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => 'Long Notes',
                'login' => 'long-notes',
                'client_id' => '1',
                'category_id' => '1',
                'notes' => str_repeat('n', 5001),
                'password' => 'valid-pass-1',
                'password_repeat' => 'valid-pass-1',
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($longNotesRes['body'])['status'] ?? null);

        // 3. Grant view access to sharedParentId -> passwordless linked create succeeds & trims padded name
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$sharedParentId, $user->id]);

        $createRes = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => '  Child Linked Shared  ',
                'login' => 'child-login',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$sharedParentId,
                'password' => '',
                'password_repeat' => '',
            ]
        );
        $createJson = $this->assertJsonOk($createRes, 'Account created');
        $childId = (int)($createJson['data']['itemId'] ?? 0);
        self::assertGreaterThan(0, $childId);
        self::assertSame('Child Linked Shared', $this->fetchValue('SELECT name FROM Account WHERE id = ' . $childId));
        self::assertSame($sharedParentId, (int)$this->fetchValue('SELECT parentId FROM Account WHERE id = ' . $childId));

        // 4. Rejects chained parent on create and cycle/self-link on edit
        $chainedRes = $this->dispatchAsUser(
            'post',
            'account/saveCreate',
            $user,
            $profile,
            [],
            [
                'name' => 'Grandchild Account',
                'login' => 'grandchild-login',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$childId,
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($chainedRes['body'])['status'] ?? null);

        $selfLinkRes = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $otherOwnedId,
            $user,
            $profile,
            [],
            [
                'name' => 'Other Owned Account',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$otherOwnedId,
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($selfLinkRes['body'])['status'] ?? null);

        // Make user admin owner of sharedParentId momentarily? No, user cannot edit sharedParentId, so test cycle with user's own parent->child
        $userParentId = $this->seedAccount($pdo, 'User Owned Parent', $user->id, 5, 'u-parent');
        $userChildId = $this->seedAccount($pdo, 'User Owned Child', $user->id, 5, 'u-child', 0, 0, 'https://example.test', 'notes', 'p', $userParentId);
        self::assertGreaterThan(0, $userChildId);
        $cycleRes = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $userParentId,
            $user,
            $profile,
            [],
            [
                'name' => 'User Owned Parent',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => (string)$userChildId,
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($cycleRes['body'])['status'] ?? null);

        // 5. viewPass & copyPass reveal inherited parent password regardless of URL parentId param
        $childPassRes = $this->dispatchAsUser('post', 'account/viewPass/' . $childId . '/0', $user, $profile);
        $childPassJson = $this->assertJsonOk($childPassRes);
        $passHtml = (string)($childPassJson['data']['html'] ?? '');
        self::assertStringContainsString('child-login', $passHtml);
        self::assertStringContainsString('parent-secret-pass', $passHtml);
        self::assertStringContainsString('Linked Account', $passHtml);

        $spoofedParentPassRes = $this->dispatchAsUser('post', 'account/viewPass/' . $childId . '/999999', $user, $profile);
        $spoofedJson = $this->assertJsonOk($spoofedParentPassRes);
        self::assertStringContainsString('parent-secret-pass', (string)($spoofedJson['data']['html'] ?? ''));

        $copyPassRes = $this->dispatchAsUser('post', 'account/copyPass/' . $childId, $user, $profile);
        $copyJson = $this->assertJsonOk($copyPassRes);
        self::assertSame('parent-secret-pass', $copyJson['data']['accpass'] ?? null);

        // 6. Create PublicLink on linked child
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$childId, $user->id]); // already owner, no-op or skip; grant publicLink ACL via profile override:
        $linkProfile = (clone $profile)->setAccPublicLinks(true);
        $createLinkRes = $this->dispatchAsUser(
            'post',
            'publicLink/saveCreateFromAccount/' . $childId . '/0',
            $user,
            $linkProfile
        );
        $this->assertJsonOk($createLinkRes);
        $childLinkId = (int)$this->fetchValue('SELECT id FROM PublicLink WHERE itemId = ' . $childId);
        $childLinkHash = (string)$this->fetchValue('SELECT hash FROM PublicLink WHERE id = ' . $childLinkId);
        self::assertNotSame('', $childLinkHash);

        // 7. Revoke user's access to parent -> viewPass, copyPass, publicLink/refresh fail closed,
        //    while already issued account/viewLink/{hash} remains usable
        $pdo->prepare('DELETE FROM AccountToUser WHERE accountId = ? AND userId = ?')
            ->execute([$sharedParentId, $user->id]);

        $lostViewPass = $this->dispatchAsUser('post', 'account/viewPass/' . $childId, $user, $profile);
        self::assertSame('ERROR', $this->decodeJson($lostViewPass['body'])['status'] ?? null);
        self::assertStringNotContainsString('parent-secret-pass', $lostViewPass['body']);

        $lostCopyPass = $this->dispatchAsUser('post', 'account/copyPass/' . $childId, $user, $profile);
        self::assertSame('ERROR', $this->decodeJson($lostCopyPass['body'])['status'] ?? null);
        self::assertStringNotContainsString('parent-secret-pass', $lostCopyPass['body']);

        $lostRefresh = $this->dispatchAsUser('post', 'publicLink/refresh/' . $childLinkId, $user, $linkProfile);
        self::assertSame('ERROR', $this->decodeJson($lostRefresh['body'])['status'] ?? null);

        $anonContext = self::createStub(SessionContext::class);
        $anonContext->method('isLoggedIn')->willReturn(false);
        $anonContext->method('getAuthCompleted')->willReturn(false);
        $anonContext->method('getUserData')->willReturn(new UserDto());
        $anonContext->method('getUserProfile')->willReturn(null);
        $anonConfigData = self::createConfiguredStub(ConfigDataInterface::class, $this->getConfigData());
        $anonConfigService = $this->createStub(ConfigFileService::class);
        $anonConfigService->method('getConfigData')->willReturn($anonConfigData);

        $anonLinkRes = $this->dispatch(
            'get',
            'account/viewLink/' . $childLinkHash,
            [],
            [],
            [
                Context::class => $anonContext,
                ConfigFileService::class => $anonConfigService,
                ConfigDataInterface::class => $anonConfigData,
            ]
        );
        self::assertStringContainsString('parent-secret-pass', $anonLinkRes['body']);

        // 8. Re-grant parent access & admin updates parent password -> child's revealed pass updates & child PublicLink is revoked
        $pdo->prepare('INSERT INTO AccountToUser (accountId, userId, isEdit) VALUES (?, ?, 0)')
            ->execute([$sharedParentId, $user->id]);
        $adminProfile = (new ProfileData())
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true);
        $adminUser = UserDto::fromModel(UserDataGenerator::factory()->buildUserData()->mutate([
            'id' => 1,
            'userGroupId' => 1,
            'isAdminApp' => true,
            'isAdminAcc' => true,
        ]));
        $parentEditPassRes = $this->dispatchAsUser(
            'post',
            'account/saveEditPass/' . $sharedParentId,
            $adminUser,
            $adminProfile,
            [],
            [
                'password' => 'parent-rotated-pass',
                'password_repeat' => 'parent-rotated-pass',
            ]
        );
        $this->assertJsonOk($parentEditPassRes);
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId));

        $afterRotatePass = $this->dispatchAsUser('post', 'account/viewPass/' . $childId, $user, $profile);
        $this->assertJsonOk($afterRotatePass);
        self::assertStringContainsString('parent-rotated-pass', $afterRotatePass['body']);

        // 9. Re-issue child PublicLink, then test unlink requirements & link revocation
        $this->assertJsonOk(
            $this->dispatchAsUser('post', 'publicLink/saveCreateFromAccount/' . $childId . '/0', $user, $linkProfile)
        );
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId));

        $noEditPassProfile = (clone $profile)->setAccEditPass(false);
        $unlinkNoPerm = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $childId,
            $user,
            $noEditPassProfile,
            [],
            [
                'name' => 'Child Linked Shared',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => '0',
                'password' => 'independent-pass',
                'password_repeat' => 'independent-pass',
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($unlinkNoPerm['body'])['status'] ?? null);
        self::assertSame($sharedParentId, (int)$this->fetchValue('SELECT parentId FROM Account WHERE id = ' . $childId));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId));

        $unlinkEmptyPass = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $childId,
            $user,
            $profile,
            [],
            [
                'name' => 'Child Linked Shared',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => '0',
                'password' => '',
                'password_repeat' => '',
            ]
        );
        self::assertSame('ERROR', $this->decodeJson($unlinkEmptyPass['body'])['status'] ?? null);
        self::assertSame($sharedParentId, (int)$this->fetchValue('SELECT parentId FROM Account WHERE id = ' . $childId));

        $unlinkOk = $this->dispatchAsUser(
            'post',
            'account/saveEdit/' . $childId,
            $user,
            $profile,
            [],
            [
                'name' => 'Child Unlinked',
                'client_id' => '1',
                'category_id' => '1',
                'parent_account_id' => '0',
                'password' => 'independent-pass',
                'password_repeat' => 'independent-pass',
            ]
        );
        $this->assertJsonOk($unlinkOk, 'Account updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT parentId FROM Account WHERE id = ' . $childId));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = ' . $childId));

        $independentPassRes = $this->dispatchAsUser('post', 'account/viewPass/' . $childId, $user, $profile);
        $this->assertJsonOk($independentPassRes);
        self::assertStringContainsString('independent-pass', $independentPassRes['body']);
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
