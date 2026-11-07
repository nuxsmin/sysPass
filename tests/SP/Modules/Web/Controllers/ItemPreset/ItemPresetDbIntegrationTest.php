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

namespace SP\Tests\Modules\Web\Controllers\ItemPreset;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\ItemPreset\Models\AccountPermission;
use SP\Domain\ItemPreset\Models\Password;
use SP\Domain\ItemPreset\Ports\ItemPresetInterface;
use SP\Tests\DatabaseIntegrationTestCase;

/**
 * DB-backed HTTP integration tests for account password and permission presets.
 */
#[Group('integration')]
final class ItemPresetDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_MESSAGE = 'You don\'t have permission to do this operation';

    private int $passwordPresetId;
    private int $permissionPresetId;

    /**
     * @throws ConstraintException
     * @throws Exception
     * @throws QueryException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordPresetId = $this->seedItemPreset(
            ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
            2,
            null,
            null,
            7,
            new Password(12, true, true, true, true, true, false, 30, 4, '^[a-z]+$')
        );

        $this->permissionPresetId = $this->seedItemPreset(
            ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION,
            3,
            null,
            null,
            7,
            new AccountPermission([2], [3], [1], [2])
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function passwordCreateFormRendersDefaults(): void
    {
        $result = $this->dispatch('get', 'itemPreset/create/'.ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD);

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('frmItemPreset', $html);
        self::assertStringContainsString('name="length"', $html);
        self::assertStringContainsString('name="use_numbers_enabled"', $html);
        self::assertStringContainsString('itemPreset/saveCreate', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function permissionCreateFormRendersPermissionSelectors(): void
    {
        $result = $this->dispatch('get', 'itemPreset/create/'.ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION);

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('frmItemPreset', $html);
        self::assertStringContainsString('name="users_view[]"', $html);
        self::assertStringContainsString('name="user_groups_edit[]"', $html);
        self::assertStringContainsString('itemPreset/saveCreate', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function passwordCreatePersistsDataAndHash(): void
    {
        $result = $this->dispatch(
            'post',
            'itemPreset/saveCreate',
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
                'user_id' => '1',
                'priority' => '5',
                'length' => '16',
                'use_numbers_enabled' => '1',
                'use_letters_enabled' => '1',
                'use_symbols_enabled' => '1',
                'expire_time' => '10',
            ]
        );

        $this->assertJsonOk($result, 'Value created');

        $hash = sha1('account.password'.'1'.'0'.'0'.'5');

        self::assertSame(
            $hash,
            $this->fetchValue(
                'SELECT hash FROM ItemPreset WHERE type = \'account.password\' AND userId = 1 AND priority = 5'
            )
        );

        $id = (int)$this->fetchValue(
            'SELECT id FROM ItemPreset WHERE type = \'account.password\' AND userId = 1 AND priority = 5'
        );

        $password = $this->getItemPresetModel($id)->hydrate(Password::class);

        self::assertInstanceOf(Password::class, $password);
        self::assertSame(16, $password->getLength());
        self::assertTrue($password->isUseNumbers());
        self::assertTrue($password->isUseLetters());
        self::assertTrue($password->isUseSymbols());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function permissionCreatePersistsDataAndHash(): void
    {
        $result = $this->dispatch(
            'post',
            'itemPreset/saveCreate',
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION,
                'user_id' => '1',
                'priority' => '6',
                'users_view' => ['2'],
                'users_edit' => ['3'],
                'user_groups_view' => ['1'],
                'user_groups_edit' => ['2'],
            ]
        );

        $this->assertJsonOk($result, 'Value created');

        $hash = sha1('account.permission'.'1'.'0'.'0'.'6');

        self::assertSame(
            $hash,
            $this->fetchValue(
                'SELECT hash FROM ItemPreset WHERE type = \'account.permission\' AND userId = 1 AND priority = 6'
            )
        );

        $id = (int)$this->fetchValue(
            'SELECT id FROM ItemPreset WHERE type = \'account.permission\' AND userId = 1 AND priority = 6'
        );

        $permission = $this->getItemPresetModel($id)->hydrate(AccountPermission::class);

        self::assertInstanceOf(AccountPermission::class, $permission);
        self::assertSame([2], $permission->getUsersView());
        self::assertSame([3], $permission->getUsersEdit());
        self::assertSame([1], $permission->getUserGroupsView());
        self::assertSame([2], $permission->getUserGroupsEdit());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function passwordEditFormLoadsSeededPreset(): void
    {
        $result = $this->dispatch('get', 'itemPreset/edit/'.$this->passwordPresetId);

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('name="length"', $html);
        self::assertStringContainsString('value="12"', $html);
        self::assertStringContainsString('value="7"', $html);
        self::assertStringContainsString('itemPreset/saveEdit/'.$this->passwordPresetId, $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function permissionEditFormLoadsSeededPreset(): void
    {
        $result = $this->dispatch('get', 'itemPreset/edit/'.$this->permissionPresetId);

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('name="users_view[]"', $html);
        self::assertStringContainsString('value="7"', $html);
        self::assertStringContainsString('value="2"', $html);
        self::assertStringContainsString('itemPreset/saveEdit/'.$this->permissionPresetId, $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function passwordEditPersistsDataAndHash(): void
    {
        $result = $this->dispatch(
            'post',
            'itemPreset/saveEdit/'.$this->passwordPresetId,
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
                'user_id' => '2',
                'priority' => '7',
                'length' => '20',
                'use_letters_enabled' => '1',
                'expire_time' => '15',
                'regex' => '^[A-Z]+$',
            ]
        );

        $this->assertJsonOk($result, 'Value updated');

        self::assertSame(
            sha1('account.password'.'2'.'0'.'0'.'7'),
            $this->fetchValue('SELECT hash FROM ItemPreset WHERE id = '.$this->passwordPresetId)
        );

        $password = $this->getItemPresetModel($this->passwordPresetId)->hydrate(Password::class);

        self::assertInstanceOf(Password::class, $password);
        self::assertSame(20, $password->getLength());
        self::assertTrue($password->isUseLetters());
        self::assertFalse($password->isUseNumbers());
        self::assertSame('^[A-Z]+$', $password->getRegex());
        self::assertSame(15 * Password::EXPIRE_TIME_MULTIPLIER, $password->getExpireTime());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function permissionEditPersistsDataAndHash(): void
    {
        $result = $this->dispatch(
            'post',
            'itemPreset/saveEdit/'.$this->permissionPresetId,
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION,
                'user_id' => '3',
                'priority' => '7',
                'users_view' => ['4'],
                'users_edit' => ['5'],
                'user_groups_view' => ['2'],
                'user_groups_edit' => ['3'],
            ]
        );

        $this->assertJsonOk($result, 'Value updated');

        self::assertSame(
            sha1('account.permission'.'3'.'0'.'0'.'7'),
            $this->fetchValue('SELECT hash FROM ItemPreset WHERE id = '.$this->permissionPresetId)
        );

        $permission = $this->getItemPresetModel($this->permissionPresetId)->hydrate(AccountPermission::class);

        self::assertInstanceOf(AccountPermission::class, $permission);
        self::assertSame([4], $permission->getUsersView());
        self::assertSame([5], $permission->getUsersEdit());
        self::assertSame([2], $permission->getUserGroupsView());
        self::assertSame([3], $permission->getUserGroupsEdit());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function createIsDeniedWithoutPresetCreateAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'itemPreset/create/'.ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ITEMPRESET_CREATE)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveCreateIsDeniedWithoutPresetCreateAccessAndDoesNotMutateData(): void
    {
        $before = (int)$this->fetchValue('SELECT COUNT(*) FROM ItemPreset');

        $result = $this->dispatch(
            'post',
            'itemPreset/saveCreate',
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
                'user_id' => '1',
                'priority' => '9',
                'length' => '30',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ITEMPRESET_CREATE)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);

        self::assertSame($before, (int)$this->fetchValue('SELECT COUNT(*) FROM ItemPreset'));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editIsDeniedWithoutPresetEditAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'itemPreset/edit/'.$this->passwordPresetId,
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ITEMPRESET_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function saveEditIsDeniedWithoutPresetEditAccessAndDoesNotMutateData(): void
    {
        $result = $this->dispatch(
            'post',
            'itemPreset/saveEdit/'.$this->passwordPresetId,
            [],
            [
                'type' => ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD,
                'user_id' => '2',
                'priority' => '7',
                'length' => '99',
            ],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::ITEMPRESET_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);

        $password = $this->getItemPresetModel($this->passwordPresetId)->hydrate(Password::class);

        self::assertInstanceOf(Password::class, $password);
        self::assertSame(12, $password->getLength());
    }
}
