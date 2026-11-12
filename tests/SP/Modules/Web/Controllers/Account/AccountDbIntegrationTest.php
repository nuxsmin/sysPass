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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Tests\DatabaseIntegrationTestCase;
use SP\Tests\InjectCrypt;
use SP\Tests\InjectVault;

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
        $accountAcl->method('getAcl')->willReturnCallback(
            static fn(int $actionId): AccountPermission => (new AccountPermission($actionId))
                ->setResultView(true)
                ->setShowView(true)
        );

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
}
