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

namespace SP\Tests\Domain\CustomField\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\CustomField\Models\CustomFieldDefinition;
use SP\Domain\CustomField\Ports\CustomFieldDefinitionService;
use SP\Domain\CustomField\UseCases\CreateCustomFieldUseCase;
use SP\Domain\CustomField\UseCases\DeleteCustomFieldUseCase;
use SP\Domain\CustomField\UseCases\DeleteCustomFieldsUseCase;
use SP\Domain\CustomField\UseCases\Dtos\CreateCustomFieldCommand;
use SP\Domain\CustomField\UseCases\Dtos\DeleteCustomFieldCommand;
use SP\Domain\CustomField\UseCases\Dtos\DeleteCustomFieldsCommand;
use SP\Domain\CustomField\UseCases\Dtos\GetCustomFieldCommand;
use SP\Domain\CustomField\UseCases\Dtos\SearchCustomFieldsCommand;
use SP\Domain\CustomField\UseCases\Dtos\UpdateCustomFieldCommand;
use SP\Domain\CustomField\UseCases\GetCustomFieldUseCase;
use SP\Domain\CustomField\UseCases\SearchCustomFieldsUseCase;
use SP\Domain\CustomField\UseCases\UpdateCustomFieldUseCase;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the CustomField use cases.
 */
#[Group('unitary')]
final class CustomFieldUseCasesTest extends TestCase
{
    private CustomFieldDefinitionService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(CustomFieldDefinitionService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheFieldAndEmitsTheEvent(): void
    {
        $this->service->method('create')->willReturn(7);

        $result = (new CreateCustomFieldUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateCustomFieldCommand((new CustomFieldDefinition())->mutate(['name' => 'example'])));

        self::assertSame(7, $result->id);
        self::assertEvent('create.customField', "Field added\nField: example");
    }

    #[Test]
    public function updateSavesTheFieldAndEmitsTheEvent(): void
    {
        $this->service->method('getById')->willReturn((new CustomFieldDefinition())->mutate(['id' => 7, 'moduleId' => 1]));
        $this->service->expects(self::once())->method('update');

        $result = (new UpdateCustomFieldUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateCustomFieldCommand(
                (new CustomFieldDefinition())->mutate(['id' => 7, 'moduleId' => 1, 'name' => 'renamed'])
            ));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.customField', "Field updated\nField: renamed");
    }

    #[Test]
    public function updateChangesTheModuleWhenItDiffers(): void
    {
        $this->service->method('getById')->willReturn((new CustomFieldDefinition())->mutate(['id' => 7, 'moduleId' => 1]));
        $this->service->expects(self::once())->method('changeModule')->willReturn(9);

        $result = (new UpdateCustomFieldUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateCustomFieldCommand(
                (new CustomFieldDefinition())->mutate(['id' => 7, 'moduleId' => 2, 'name' => 'renamed'])
            ));

        self::assertSame(9, $result->id);
    }

    #[Test]
    public function deleteRemovesTheFieldAndEmitsTheEventWithoutMessage(): void
    {
        $this->service->expects(self::once())->method('delete')->with(7);

        $result = (new DeleteCustomFieldUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteCustomFieldCommand(7));

        self::assertSame(7, $result->id);

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('delete.customField', $event['name']);
        self::assertNull($event['message']);
    }

    #[Test]
    public function deleteBatchRemovesTheFieldsAndEmitsTheEvent(): void
    {
        $this->service->expects(self::once())->method('deleteByIdBatch')->with([3, 4]);

        $result = (new DeleteCustomFieldsUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteCustomFieldsCommand([3, 4]));

        self::assertSame([3, 4], $result->ids);
        self::assertEvent('delete.customField.selection', 'Fields deleted');
    }

    #[Test]
    public function getReturnsTheField(): void
    {
        $field = (new CustomFieldDefinition())->mutate(['id' => 7]);
        $this->service->method('getById')->willReturn($field);

        $result = (new GetCustomFieldUseCase($this->service))
            ->execute(new GetCustomFieldCommand(7));

        self::assertSame($field, $result->model);
    }

    #[Test]
    public function searchReturnsTheResult(): void
    {
        $queryResult = new QueryResult();
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchCustomFieldsUseCase($this->service))
            ->execute(new SearchCustomFieldsCommand(new ItemSearchDto()));

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
