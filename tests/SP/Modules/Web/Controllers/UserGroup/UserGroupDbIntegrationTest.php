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

namespace SP\Tests\Modules\Web\Controllers\UserGroup;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Tests\DatabaseIntegrationTestCase;

/**
 * DB-backed HTTP integration tests for the user group area.
 */
#[Group('integration')]
final class UserGroupDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const DENIED_MESSAGE = 'You don\'t have permission to do this operation';

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function viewRendersFixtureGroupAndUsage(): void
    {
        $result = $this->dispatch('get', 'userGroup/view/1');

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('Admins', $html);
        self::assertStringContainsString('sysPass Admins', $html);
        self::assertStringContainsString('sysPass demo (demo)', $html);
        self::assertStringContainsString('frmGroups', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editRendersFixtureGroupForm(): void
    {
        $result = $this->dispatch('get', 'userGroup/edit/1');

        $json = $this->assertJsonOk($result);
        $html = $json['data']['html'] ?? '';

        self::assertStringContainsString('Admins', $html);
        self::assertStringContainsString('sysPass Admins', $html);
        self::assertStringContainsString('userGroup/saveEdit/1', $html);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws QueryException
     */
    #[Test]
    public function saveEditPersistsGroupChanges(): void
    {
        $result = $this->dispatch(
            'post',
            'userGroup/saveEdit/1',
            [],
            [
                'name' => 'DB Group',
                'description' => 'Updated by integration test',
                'users' => ['1'],
            ]
        );

        $this->assertJsonOk($result, 'Group updated');

        self::assertSame('DB Group', $this->fetchValue('SELECT name FROM UserGroup WHERE id = 1'));
        self::assertSame(
            'Updated by integration test',
            $this->fetchValue('SELECT description FROM UserGroup WHERE id = 1')
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function viewIsDeniedWithoutGroupViewAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'userGroup/view/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::GROUP_VIEW)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function editIsDeniedWithoutGroupEditAccess(): void
    {
        $result = $this->dispatch(
            'get',
            'userGroup/edit/1',
            [],
            [],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::GROUP_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function saveEditIsDeniedWithoutGroupEditAccessAndDoesNotMutateData(): void
    {
        $result = $this->dispatch(
            'post',
            'userGroup/saveEdit/1',
            [],
            ['name' => 'Hacked', 'description' => 'Hacked'],
            [AclInterface::class => $this->denyAclFor(AclActionsInterface::GROUP_EDIT)]
        );

        $this->assertJsonDenied($result, self::DENIED_MESSAGE);

        self::assertSame('Admins', $this->fetchValue('SELECT name FROM UserGroup WHERE id = 1'));
        self::assertSame('sysPass Admins', $this->fetchValue('SELECT description FROM UserGroup WHERE id = 1'));
    }
}
