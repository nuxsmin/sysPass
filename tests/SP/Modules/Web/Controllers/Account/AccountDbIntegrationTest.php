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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Common\Adapters\Serde;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Crypt\Crypt;
use SP\Domain\Core\Crypt\Vault;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserPreferences;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\InjectCrypt;
use SP\Tests\InjectVault;

use function SP\Tests\getDbHandler;

/**
 * DB-backed HTTP integration tests for the account area.
 */
#[Group('integration')]
#[InjectVault]
final class AccountDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_PAGE = 'You don\'t have permission to access this page';
    private const DENIED_OP = 'You don\'t have permission to do this operation';

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function viewRendersFixtureAccountAndCustomFields(): void
    {
        $result = $this->dispatch('get', 'account/view/1');

        $this->assertResponseWasDispatched($result);
        self::assertStringContainsString('Google', $result['body']);
        self::assertStringContainsString('google.com', $result['body']);
        self::assertStringContainsString('Prueba', $result['body']);
        self::assertStringContainsString('some_data', $result['body']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function editRendersFixtureAccountForm(): void
    {
        $result = $this->dispatch('get', 'account/edit/1');

        $this->assertResponseWasDispatched($result);
        self::assertStringContainsString('Google', $result['body']);
        self::assertStringContainsString('account/saveEdit', $result['body']);
        self::assertStringContainsString('Prueba', $result['body']);
    }

    #[Test]
    #[InjectCrypt]
    public function createFiltersClientsWithoutClientManagementAccess(): void
    {
        $result = $this->dispatch('get', 'account/create', [], [], [
            AclInterface::class => $this->denyAclFor(AclActionsInterface::CLIENT),
        ]);

        $this->assertResponseWasDispatched($result);
        self::assertSame(1, preg_match('/<select\b[^>]*id="client_id"[^>]*>([\s\S]*?)<\/select>/', $result['body'], $matches));
        self::assertStringContainsString('Microsoft', $matches[1]);
        self::assertStringNotContainsString('Google', $matches[1]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveEditPersistsAccountChanges(): void
    {
        $result = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Google DB',
                'login' => 'db-login',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '1',
                'url' => 'https://db.example.com',
                'notes' => 'Updated notes',
                'tags_update' => '1',
                'tags' => ['1', '2', '3'],
            ]
        );

        $json = $this->assertJsonOk($result, 'Account updated');
        self::assertSame(1, $json['data']['itemId'] ?? null);

        self::assertSame('Google DB', $this->fetchValue('SELECT name FROM Account WHERE id = 1'));
        self::assertSame('db-login', $this->fetchValue('SELECT login FROM Account WHERE id = 1'));
        self::assertSame('https://db.example.com', $this->fetchValue('SELECT url FROM Account WHERE id = 1'));
        self::assertSame('Updated notes', $this->fetchValue('SELECT notes FROM Account WHERE id = 1'));
        self::assertSame(
            1,
            (int)$this->fetchValue(
                'SELECT COUNT(*) FROM AccountToTag WHERE accountId = 1 AND tagId = 3'
            )
        );
    }

    /**
     * An admin may leave the Owner/Main Group selects on their empty default.
     * Creation must then fall back to the current user and group.
     *
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveCreateDefaultsOwnerAndGroupToCurrentUserWhenNotSelected(): void
    {
        $result = $this->dispatch(
            'post',
            'account/saveCreate',
            [],
            [
                'name' => 'Account Without Owner',
                'login' => 'no-owner',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
                'owner_id' => '',
                'main_usergroup_id' => '',
            ]
        );

        $json = $this->assertJsonOk($result, 'Account created');
        $itemId = (int)($json['data']['itemId'] ?? 0);

        self::assertGreaterThan(0, $itemId);
        self::assertSame(1, (int)$this->fetchValue('SELECT userId FROM Account WHERE id = ' . $itemId));
        self::assertSame(1, (int)$this->fetchValue('SELECT userGroupId FROM Account WHERE id = ' . $itemId));
    }

    /**
     * A freshly created account has no edit date or URL. Searching for it must
     * not pass those null values to string-only helpers.
     *
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function searchAfterCreateHandlesAccountWithoutDateEdit(): void
    {
        $name = 'Account Without Edit Date';

        $create = $this->dispatch(
            'post',
            'account/saveCreate',
            [],
            [
                'name' => $name,
                'login' => 'no-date-edit',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
                'owner_id' => '',
                'main_usergroup_id' => '',
            ]
        );

        $json = $this->assertJsonOk($create, 'Account created');
        $itemId = (int)($json['data']['itemId'] ?? 0);

        self::assertGreaterThan(0, $itemId);
        self::assertNull($this->fetchValue('SELECT dateEdit FROM Account WHERE id = ' . $itemId));
        self::assertNull($this->fetchValue('SELECT url FROM Account WHERE id = ' . $itemId));

        $edit = $this->dispatch('get', 'account/edit/' . $itemId);

        $this->assertResponseWasDispatched($edit);
        self::assertStringNotContainsString('must be of type', $edit['body']);
        self::assertStringContainsString($name, $edit['body']);

        $accountAcl = self::createStub(AccountAclService::class);
        $accountAcl->method('getForSearch')->willReturn([
            $itemId => (new AccountPermission(AclActionsInterface::ACCOUNT_SEARCH))
                ->setResultView(true)
                ->setShowView(true),
        ]);

        $result = $this->dispatch(
            'post',
            'account/search',
            [],
            [
                'search' => $name,
                'isAjax' => '1',
                'start' => '0',
                'skey' => '0',
                'sorder' => '0',
                'searchfav' => '',
            ],
            [AccountAclService::class => $accountAcl]
        );

        $this->assertResponseWasDispatched($result);
        self::assertStringNotContainsString('must be of type', $result['body']);
        self::assertStringContainsString($name, $result['body']);
        self::assertStringContainsString('btn-favorite-' . $itemId, $result['body']);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveEditPassPersistsPasswordAndRunsEventClosure(): void
    {
        $passDate = (int)$this->fetchValue('SELECT passDate FROM Account WHERE id = 1');

        $result = $this->dispatch(
            'post',
            'account/saveEditPass/1',
            [],
            [
                'password' => 'new-password',
                'password_repeat' => 'new-password',
                'owner_id' => '1',
            ]
        );

        $json = $this->assertJsonOk($result, 'Password updated');
        self::assertSame(1, $json['data']['itemId'] ?? null);

        self::assertSame('some_data', $this->fetchValue('SELECT pass FROM Account WHERE id = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT userEditId FROM Account WHERE id = 1'));
        self::assertGreaterThan(
            $passDate,
            (int)$this->fetchValue('SELECT passDate FROM Account WHERE id = 1')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function viewIsDeniedWith403AndDoesNotMutateData(): void
    {
        $countView = (int)$this->fetchValue('SELECT countView FROM Account WHERE id = 1');

        $result = $this->dispatch(
            'get',
            'account/view/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_VIEW)]
        );

        self::assertSame(403, $result['code'], $result['body']);
        self::assertStringContainsString(self::DENIED_PAGE, $result['body']);

        self::assertSame(
            $countView,
            (int)$this->fetchValue('SELECT countView FROM Account WHERE id = 1')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editIsDeniedWith403AndDoesNotMutateData(): void
    {
        $name = $this->fetchValue('SELECT name FROM Account WHERE id = 1');
        $pass = $this->fetchValue('SELECT pass FROM Account WHERE id = 1');

        $result = $this->dispatch(
            'get',
            'account/edit/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_EDIT)]
        );

        self::assertSame(403, $result['code'], $result['body']);
        self::assertStringContainsString(self::DENIED_PAGE, $result['body']);

        self::assertSame($name, $this->fetchValue('SELECT name FROM Account WHERE id = 1'));
        self::assertSame($pass, $this->fetchValue('SELECT pass FROM Account WHERE id = 1'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveEditIsDeniedWithoutAccountEditAccessAndDoesNotMutateData(): void
    {
        $result = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Hacked',
                'login' => 'hacked',
                'client_id' => '1',
                'category_id' => '1',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_OP);

        self::assertSame('Google', $this->fetchValue('SELECT name FROM Account WHERE id = 1'));
        self::assertSame('admin', $this->fetchValue('SELECT login FROM Account WHERE id = 1'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveCreateIsDeniedWithoutAccountCreateAccessAndDoesNotCreateAccount(): void
    {
        $result = $this->dispatch(
            'post',
            'account/saveCreate',
            [],
            [
                'name' => 'Denied Create Attempt',
                'login' => 'denied-create',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
                'owner_id' => '',
                'main_usergroup_id' => '',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_CREATE)]
        );

        $this->assertJsonDenied($result, self::DENIED_OP);

        self::assertSame(
            0,
            (int)$this->fetchValue("SELECT COUNT(*) FROM Account WHERE name = 'Denied Create Attempt'")
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveCopyIsDeniedWithoutAccountCreateAccessAndDoesNotCreateAccount(): void
    {
        $result = $this->dispatch(
            'post',
            'account/saveCopy',
            [],
            [
                'name' => 'Denied Copy Attempt',
                'login' => 'denied-copy',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
                'owner_id' => '',
                'main_usergroup_id' => '',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_CREATE)]
        );

        $this->assertJsonDenied($result, self::DENIED_OP);

        self::assertSame(
            0,
            (int)$this->fetchValue("SELECT COUNT(*) FROM Account WHERE name = 'Denied Copy Attempt'")
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    #[InjectCrypt]
    public function saveEditPassIsDeniedWithoutAccountEditPassAccessAndDoesNotMutateData(): void
    {
        $pass = $this->fetchValue('SELECT pass FROM Account WHERE id = 1');

        $result = $this->dispatch(
            'post',
            'account/saveEditPass/1',
            [],
            [
                'password' => 'hacked-password',
                'password_repeat' => 'hacked-password',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ACCOUNT_EDIT_PASS)]
        );

        $this->assertJsonDenied($result, self::DENIED_OP);

        self::assertSame($pass, $this->fetchValue('SELECT pass FROM Account WHERE id = 1'));
    }

    #[Test]
    #[InjectCrypt]
    public function saveCreateRejectsInvalidFieldsAndIncompatiblePrivacyAndRollsBackCustomFields(): void
    {
        $initialCount = (int)$this->fetchValue('SELECT COUNT(*) FROM Account');

        $invalidCases = [
            'whitespace name' => [
                'name' => '   ',
                'login' => 'valid-login',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'oversized name' => [
                'name' => str_repeat('N', 101),
                'login' => 'valid-login',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'oversized login' => [
                'name' => 'Valid Account',
                'login' => str_repeat('L', 51),
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'oversized url' => [
                'name' => 'Valid Account',
                'login' => 'valid-login',
                'url' => 'https://example.test/' . str_repeat('u', 240),
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'nonexistent client' => [
                'name' => 'Missing Client Account',
                'login' => 'valid-login',
                'client_id' => '99999',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'nonexistent category' => [
                'name' => 'Missing Category Account',
                'login' => 'valid-login',
                'client_id' => '1',
                'category_id' => '99999',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            'mismatched private and privateGroup owner group' => [
                'name' => 'Incompatible Privacy',
                'login' => 'valid-login',
                'client_id' => '1',
                'category_id' => '1',
                'owner_id' => '1',
                'main_usergroup_id' => '2',
                'private_enabled' => 'on',
                'private_group_enabled' => 'on',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
        ];

        foreach ($invalidCases as $label => $post) {
            $res = $this->dispatch('post', 'account/saveCreate', [], $post);
            $json = $this->decodeJson($res['body']);
            self::assertSame('ERROR', $json['status'] ?? null, "$label: " . $res['body']);
        }

        // Atomic rollback when a custom field definition does not exist
        $cfFailRes = $this->dispatch(
            'post',
            'account/saveCreate',
            [],
            [
                'name' => 'Atomic Rollback Account',
                'login' => 'atomic-rollback',
                'client_id' => '1',
                'category_id' => '1',
                'password' => 'secret',
                'password_repeat' => 'secret',
                'customfield' => [99999 => 'boom'],
            ]
        );
        $cfFailJson = $this->decodeJson($cfFailRes['body']);
        self::assertSame('ERROR', $cfFailJson['status'] ?? null, $cfFailRes['body']);

        // Unprivileged user without accPrivate/accPrivateGroup cannot create with private flags
        $pdo = getDbHandler()->getConnection();
        $limitedProfile = (new ProfileData())
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccEdit(true)
            ->setAccPrivate(false)
            ->setAccPrivateGroup(false);

        $limitedProfileStmt = $pdo->prepare('INSERT INTO UserProfile (name, profile) VALUES (:name, :profile)');
        $limitedProfileStmt->execute([
            'name' => 'Limited Priv Profile',
            'profile' => Serde::serialize($limitedProfile),
        ]);
        $limitedProfileId = (int)$pdo->lastInsertId();

        $userStmt = $pdo->prepare(
            'INSERT INTO User (name, userGroupId, login, pass, email, userProfileId, isAdminApp, isAdminAcc, isDisabled, hashSalt, isChangePass, isChangedPass, isLdap, isMigrate)
             VALUES (:name, 5, :login, :pass, :email, :profileId, 0, 0, 0, :salt, 0, 0, 0, 0)'
        );
        $userStmt->execute([
            'name' => 'No Priv User',
            'login' => 'no_priv_creator',
            'pass' => 'x',
            'email' => 'no_priv_creator@example.test',
            'profileId' => $limitedProfileId,
            'salt' => sha1('no_priv_creator'),
        ]);
        $limitedUserId = (int)$pdo->lastInsertId();

        $limitedUserDto = UserDto::fromModel(UserDataGenerator::factory()->buildUserData())
            ->mutate([
                'id' => $limitedUserId,
                'userGroupId' => 5,
                'userProfileId' => $limitedProfileId,
                'login' => 'no_priv_creator',
                'name' => 'No Priv User',
                'lastUpdateMPass' => time(),
                'isAdminApp' => false,
                'isAdminAcc' => false,
                'preferences' => new UserPreferences(['lang' => 'en_US']),
            ]);

        $limitedVault = self::createStub(\SP\Domain\Core\Crypt\VaultInterface::class);
        $limitedVault->method('getData')->willReturn('12345678900');
        $limitedContext = self::createStub(SessionContext::class);
        $limitedContext->method('isLoggedIn')->willReturn(true);
        $limitedContext->method('getAuthCompleted')->willReturn(true);
        $limitedContext->method('getUserData')->willReturn($limitedUserDto);
        $limitedContext->method('getUserProfile')->willReturn($limitedProfile);
        $limitedContext->method('getCSRF')->willReturn(null);
        $limitedContext->method('getSidStartTime')->willReturn(time());
        $limitedContext->method('getVault')->willReturn($limitedVault);
        $limitedContext->method('getTrasientKey')->willReturnCallback(
            static fn(string $key, mixed $default = null) => $key === Context::MASTER_PASSWORD_KEY ? '12345678900' : $default
        );

        $privDenyRes = $this->dispatch(
            'post',
            'account/saveCreate',
            [],
            [
                'name' => 'Unauthorized Private Create',
                'login' => 'unauth-priv',
                'client_id' => '1',
                'category_id' => '1',
                'private_enabled' => 'on',
                'password' => 'secret',
                'password_repeat' => 'secret',
            ],
            [
                Context::class => $limitedContext,
                SessionContext::class => $limitedContext,
            ]
        );
        $privDenyJson = $this->decodeJson($privDenyRes['body']);
        self::assertSame('ERROR', $privDenyJson['status'] ?? null, $privDenyRes['body']);
        self::assertStringContainsString('permission', (string)($privDenyJson['description'] ?? ''), $privDenyRes['body']);

        self::assertSame($initialCount, (int)$this->fetchValue('SELECT COUNT(*) FROM Account'));
        self::assertSame(
            0,
            (int)$this->fetchValue('SELECT COUNT(*) FROM CustomFieldData WHERE definitionId = 99999')
        );
    }

    #[Test]
    #[InjectCrypt]
    public function saveEditPreservesOmittedFieldsClearsSingleCustomFieldSkipsNoOpAndSelectivelyRevokesPublicLinks(): void
    {
        $pdo = getDbHandler()->getConnection();

        $pdo->exec(
            "INSERT INTO CustomFieldDefinition (id, name, moduleId, required, help, showInList, typeId, isEncrypted)
             VALUES (10, 'Optional Field', 1, 0, 'help', 0, 1, 0)"
        );
        $pdo->exec(
            "INSERT INTO CustomFieldData (moduleId, itemId, definitionId, data, `key`)
             VALUES (1, 1, 10, 'opt-val', '')"
        );

        $pdo->exec(
            "UPDATE Account SET isPrivate = 1, login = 'orig-login', url = 'https://orig.example.test', notes = 'orig-notes' WHERE id = 1"
        );
        $pdo->exec('DELETE FROM PublicLink WHERE itemId = 1');
        $pdo->exec(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire)
             VALUES (1, 'hash-account-1', 'data', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600)"
        );

        $initialHistoryCount = (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = 1');

        // 1. Partial POST omitting url, notes, tags, private_enabled, and customfield[1] preserves them
        //    while clearing only customfield[10] = '' deletes definitionId=10 and keeps definitionId=1
        $partialRes = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Google Partial',
                'login' => 'orig-login',
                'client_id' => '1',
                'category_id' => '1',
                'customfield' => [10 => ''],
            ]
        );
        $this->assertJsonOk($partialRes, 'Account updated');

        self::assertSame('https://orig.example.test', $this->fetchValue('SELECT url FROM Account WHERE id = 1'));
        self::assertSame('orig-notes', $this->fetchValue('SELECT notes FROM Account WHERE id = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = 1'));
        self::assertGreaterThan(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToTag WHERE accountId = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM CustomFieldData WHERE itemId = 1 AND moduleId = 1 AND definitionId = 1'));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM CustomFieldData WHERE itemId = 1 AND moduleId = 1 AND definitionId = 10'));
        // Name is a content field, so PublicLink was revoked
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = 1'));

        // Re-seed PublicLink for no-op and permissions-only checks
        $pdo->exec(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire)
             VALUES (1, 'hash-account-1-v2', 'data', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600)"
        );
        $historyAfterPartial = (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = 1');
        self::assertSame($initialHistoryCount + 1, $historyAfterPartial);

        // 2. Identical no-op saveEdit creates 0 new history rows and keeps PublicLink
        $noopRes = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Google Partial',
                'login' => 'orig-login',
                'client_id' => '1',
                'category_id' => '1',
            ]
        );
        $this->assertJsonOk($noopRes, 'Account updated');
        self::assertSame($historyAfterPartial, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = 1'));

        // 3. Privacy/permission-only saveEdit creates history and does NOT revoke PublicLink
        $permOnlyRes = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Google Partial',
                'login' => 'orig-login',
                'client_id' => '1',
                'category_id' => '1',
                'private_enabled' => '0',
                'other_users_view_update' => '1',
                'other_users_view' => ['2'],
            ]
        );
        $this->assertJsonOk($permOnlyRes, 'Account updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT isPrivate FROM Account WHERE id = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToUser WHERE accountId = 1 AND userId = 2'));
        self::assertSame($historyAfterPartial + 1, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountHistory WHERE accountId = 1'));
        self::assertSame(1, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = 1'));

        // 4. Explicit clearing via tags_update=1 + empty tags removes all tags & revokes PublicLink
        $clearTagsRes = $this->dispatch(
            'post',
            'account/saveEdit/1',
            [],
            [
                'name' => 'Google Partial',
                'login' => 'orig-login',
                'client_id' => '1',
                'category_id' => '1',
                'tags_update' => '1',
            ]
        );
        $this->assertJsonOk($clearTagsRes, 'Account updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM AccountToTag WHERE accountId = 1'));
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = 1'));

        // 5. saveEditPass revokes PublicLink as a content/secret mutation
        $pdo->exec(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire)
             VALUES (1, 'hash-account-1-v3', 'data', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600)"
        );
        $passRes = $this->dispatch(
            'post',
            'account/saveEditPass/1',
            [],
            [
                'password' => 'rotated-secret',
                'password_repeat' => 'rotated-secret',
            ]
        );
        $this->assertJsonOk($passRes, 'Password updated');
        self::assertSame(0, (int)$this->fetchValue('SELECT COUNT(*) FROM PublicLink WHERE itemId = 1'));
    }

    #[Test]
    #[InjectCrypt]
    public function copyFormRendersCreatorDefaultsWithoutSourceHistoryLinksOrPublicLink(): void
    {
        $pdo = getDbHandler()->getConnection();
        $pdo->exec('UPDATE Account SET isPrivate = 1, isPrivateGroup = 0 WHERE id = 1');
        $pdo->exec(
            "INSERT INTO Account (userGroupId, userId, userEditId, clientId, name, categoryId, login, url, pass, `key`, notes, dateAdd, parentId)
             VALUES (1, 1, 1, 1, 'Linked Child', 1, 'child', 'https://child.test', 'p', 'k', 'n', NOW(), 1)"
        );
        $pdo->exec('DELETE FROM PublicLink WHERE itemId = 1');
        $pdo->exec(
            "INSERT INTO PublicLink (itemId, hash, data, userId, typeId, notify, dateAdd, dateExpire)
             VALUES (1, 'copy-source-publink-hash', 'data', 1, 1, 0, UNIX_TIMESTAMP(), UNIX_TIMESTAMP() + 3600)"
        );

        $configData = self::createConfiguredStub(
            ConfigDataInterface::class,
            array_merge($this->getConfigData(), ['isPublinksEnabled' => true])
        );
        $configFileService = self::createStub(ConfigFileService::class);
        $configFileService->method('getConfigData')->willReturn($configData);

        $result = $this->dispatch(
            'get',
            'account/copy/1',
            [],
            [],
            [
                ConfigDataInterface::class => $configData,
                ConfigFileService::class => $configFileService,
            ]
        );

        $this->assertResponseWasDispatched($result);
        self::assertStringContainsString('data-action-route="account/saveCopy"', $result['body']);
        self::assertStringContainsString('data-item-id=""', $result['body']);
        self::assertStringNotContainsString('copy-source-publink-hash', $result['body']);
        self::assertStringNotContainsString('account/viewHistory', $result['body']);
        self::assertStringNotContainsString('Linked Child', $result['body']);
        self::assertStringContainsString('<input type="hidden" name="private_enabled" value="0">', $result['body']);
    }
}
