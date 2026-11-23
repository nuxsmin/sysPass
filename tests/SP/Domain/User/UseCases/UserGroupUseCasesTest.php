<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\User\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\User\Models\UserGroup;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\UseCases\CreateUserGroupUseCase;
use SP\Domain\User\UseCases\DeleteUserGroupUseCase;
use SP\Domain\User\UseCases\DeleteUserGroupsUseCase;
use SP\Domain\User\UseCases\Dtos\CreateUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserGroupsCommand;
use SP\Domain\User\UseCases\Dtos\GetUserGroupCommand;
use SP\Domain\User\UseCases\Dtos\SearchUserGroupsCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserGroupCommand;
use SP\Domain\User\UseCases\GetUserGroupUseCase;
use SP\Domain\User\UseCases\SearchUserGroupsUseCase;
use SP\Domain\User\UseCases\UpdateUserGroupUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the UserGroup use cases.
 */
#[Group('unitary')]
final class UserGroupUseCasesTest extends TestCase
{
    private UserGroupService&MockObject $service;
    private CustomFieldDataService&MockObject $customFieldDataService;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(UserGroupService::class);
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheGroupAndEmitsTheEvent(): void
    {
        $this->service->method('create')->willReturn(7);

        $result = (new CreateUserGroupUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateUserGroupCommand((new UserGroup())->mutate(['name' => 'example'])));

        self::assertSame(7, $result->id);
        self::assertEvent('create.userGroup', "Group added\nName: example");
    }

    #[Test]
    public function updateSavesTheGroupAndEmitsTheEvent(): void
    {
        $this->service->expects(self::once())->method('update');

        $result = (new UpdateUserGroupUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateUserGroupCommand((new UserGroup())->mutate(['id' => 7, 'name' => 'renamed'])));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.userGroup', "Group updated\nName: renamed");
    }

    #[Test]
    public function deleteRemovesTheGroupAndItsCustomFields(): void
    {
        $this->service->expects(self::once())->method('delete')->with(7);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([7], AclActionsInterface::GROUP);

        $result = (new DeleteUserGroupUseCase($this->service, $this->eventEmitter, $this->customFieldDataService))
            ->execute(new DeleteUserGroupCommand(7));

        self::assertSame(7, $result->id);
        self::assertEvent('delete.userGroup', "Group deleted\nGroup: 7");
    }

    #[Test]
    public function deleteBatchRemovesTheGroupsAndTheirCustomFields(): void
    {
        $this->service->method('deleteByIdBatch')->willReturn(2);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([3, 4], AclActionsInterface::GROUP);

        $result = (new DeleteUserGroupsUseCase($this->service, $this->eventEmitter, $this->customFieldDataService))
            ->execute(new DeleteUserGroupsCommand([3, 4]));

        self::assertSame([3, 4], $result->ids);
        self::assertEvent('delete.userGroup.selection', 'Groups deleted');
    }

    #[Test]
    public function getReturnsTheGroup(): void
    {
        $group = (new UserGroup())->mutate(['id' => 7]);
        $this->service->method('getById')->willReturn($group);

        $result = (new GetUserGroupUseCase($this->service))
            ->execute(new GetUserGroupCommand(7));

        self::assertSame($group, $result->model);
    }

    #[Test]
    public function searchReturnsTheResult(): void
    {
        $queryResult = new QueryResult();
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchUserGroupsUseCase($this->service))
            ->execute(new SearchUserGroupsCommand(new ItemSearchDto()));

        self::assertSame($queryResult, $result->result);
    }

    private function assertEvent(string $name, string $text): void
    {
        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame($name, $event['name']);
        self::assertSame($text, (new EventMessageFormatter())->composeText($event['message']));
    }
}
