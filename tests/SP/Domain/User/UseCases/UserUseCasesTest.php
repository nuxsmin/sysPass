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
use SP\Domain\User\Models\User;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\UseCases\CreateUserUseCase;
use SP\Domain\User\UseCases\DeleteUserUseCase;
use SP\Domain\User\UseCases\DeleteUsersUseCase;
use SP\Domain\User\UseCases\Dtos\CreateUserCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUsersCommand;
use SP\Domain\User\UseCases\Dtos\GetUserCommand;
use SP\Domain\User\UseCases\Dtos\SearchUsersCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserPassCommand;
use SP\Domain\User\UseCases\GetUserUseCase;
use SP\Domain\User\UseCases\SearchUsersUseCase;
use SP\Domain\User\UseCases\UpdateUserPassUseCase;
use SP\Domain\User\UseCases\UpdateUserUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the User use cases.
 */
#[Group('unitary')]
final class UserUseCasesTest extends TestCase
{
    private UserService&MockObject $service;
    private CustomFieldDataService&MockObject $customFieldDataService;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(UserService::class);
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheUserAndEmitsTheEvent(): void
    {
        $this->service->method('create')->willReturn(7);

        $result = (new CreateUserUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateUserCommand((new User())->mutate(['name' => 'example'])));

        self::assertSame(7, $result->id);
        self::assertEvent('create.user', "User added\nUser: example");
    }

    #[Test]
    public function updateSavesTheUserAndEmitsTheEvent(): void
    {
        $this->service->expects(self::once())->method('update');

        $result = (new UpdateUserUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateUserCommand((new User())->mutate(['id' => 7, 'name' => 'renamed'])));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.user', "User updated\nUser: renamed");
    }

    #[Test]
    public function updatePassSavesThePasswordAndEmitsTheEvent(): void
    {
        $this->service->expects(self::once())
            ->method('updatePass')
            ->with(7, 'secret');

        $result = (new UpdateUserPassUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateUserPassCommand(7, 'secret'));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.user.pass', "Password updated\nUser: 7");
    }

    #[Test]
    public function deleteRemovesTheUserAndItsCustomFields(): void
    {
        $this->service->expects(self::once())->method('delete')->with(7);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([7], AclActionsInterface::USER);

        $result = (new DeleteUserUseCase($this->service, $this->customFieldDataService, $this->eventEmitter))
            ->execute(new DeleteUserCommand(7));

        self::assertSame(7, $result->id);
        self::assertEvent('delete.user', "User deleted\nUser: 7");
    }

    #[Test]
    public function deleteBatchRemovesTheUsersAndTheirCustomFields(): void
    {
        $this->service->method('deleteByIdBatch')->willReturn(2);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([3, 4], AclActionsInterface::USER);

        $result = (new DeleteUsersUseCase($this->service, $this->customFieldDataService, $this->eventEmitter))
            ->execute(new DeleteUsersCommand([3, 4]));

        self::assertSame([3, 4], $result->ids);
        self::assertEvent('delete.user.selection', 'Users deleted');
    }

    #[Test]
    public function getReturnsTheUser(): void
    {
        $user = (new User())->mutate(['id' => 7]);
        $this->service->method('getById')->willReturn($user);

        $result = (new GetUserUseCase($this->service))
            ->execute(new GetUserCommand(7));

        self::assertSame($user, $result->model);
    }

    #[Test]
    public function searchReturnsTheResult(): void
    {
        $queryResult = new QueryResult();
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchUsersUseCase($this->service))
            ->execute(new SearchUsersCommand(new ItemSearchDto()));

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