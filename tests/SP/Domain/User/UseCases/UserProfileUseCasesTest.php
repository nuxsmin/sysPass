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
use SP\Domain\User\Models\UserProfile;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\UseCases\CreateUserProfileUseCase;
use SP\Domain\User\UseCases\DeleteUserProfileUseCase;
use SP\Domain\User\UseCases\DeleteUserProfilesUseCase;
use SP\Domain\User\UseCases\Dtos\CreateUserProfileCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserProfileCommand;
use SP\Domain\User\UseCases\Dtos\DeleteUserProfilesCommand;
use SP\Domain\User\UseCases\Dtos\GetUserProfileCommand;
use SP\Domain\User\UseCases\Dtos\SearchUserProfilesCommand;
use SP\Domain\User\UseCases\Dtos\UpdateUserProfileCommand;
use SP\Domain\User\UseCases\GetUserProfileUseCase;
use SP\Domain\User\UseCases\SearchUserProfilesUseCase;
use SP\Domain\User\UseCases\UpdateUserProfileUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the UserProfile use cases.
 */
#[Group('unitary')]
final class UserProfileUseCasesTest extends TestCase
{
    private UserProfileService&MockObject $service;
    private CustomFieldDataService&MockObject $customFieldDataService;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(UserProfileService::class);
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheProfileAndEmitsTheEvent(): void
    {
        $this->service->method('create')->willReturn(7);

        $result = (new CreateUserProfileUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateUserProfileCommand((new UserProfile())->mutate(['name' => 'example'])));

        self::assertSame(7, $result->id);
        self::assertEvent('create.userProfile', "Profile added\nName: example");
    }

    #[Test]
    public function updateSavesTheProfileAndEmitsTheEvent(): void
    {
        $this->service->expects(self::once())->method('update');

        $result = (new UpdateUserProfileUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateUserProfileCommand((new UserProfile())->mutate(['id' => 7, 'name' => 'renamed'])));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.userProfile', "Profile updated\nName: renamed");
    }

    #[Test]
    public function deleteRemovesTheProfileAndItsCustomFields(): void
    {
        $this->service->expects(self::once())->method('delete')->with(7);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([7], AclActionsInterface::PROFILE);

        $result = (new DeleteUserProfileUseCase($this->service, $this->eventEmitter, $this->customFieldDataService))
            ->execute(new DeleteUserProfileCommand(7));

        self::assertSame(7, $result->id);
        self::assertEvent('delete.userProfile', "Profile deleted\nProfile: 7");
    }

    #[Test]
    public function deleteBatchRemovesTheProfilesAndTheirCustomFields(): void
    {
        $this->service->method('deleteByIdBatch')->willReturn(2);
        $this->customFieldDataService->expects(self::once())
            ->method('delete')
            ->with([3, 4], AclActionsInterface::PROFILE);

        $result = (new DeleteUserProfilesUseCase($this->service, $this->eventEmitter, $this->customFieldDataService))
            ->execute(new DeleteUserProfilesCommand([3, 4]));

        self::assertSame([3, 4], $result->ids);
        self::assertEvent('delete.userProfile.selection', 'Profiles deleted');
    }

    #[Test]
    public function getReturnsTheProfile(): void
    {
        $profile = (new UserProfile())->mutate(['id' => 7]);
        $this->service->method('getById')->willReturn($profile);

        $result = (new GetUserProfileUseCase($this->service))
            ->execute(new GetUserProfileCommand(7));

        self::assertSame($profile, $result->model);
    }

    #[Test]
    public function searchReturnsTheResult(): void
    {
        $queryResult = new QueryResult();
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchUserProfilesUseCase($this->service))
            ->execute(new SearchUserProfilesCommand(new ItemSearchDto()));

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
