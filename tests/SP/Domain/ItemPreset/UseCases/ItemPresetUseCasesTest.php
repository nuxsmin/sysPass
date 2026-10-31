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

namespace SP\Tests\Domain\ItemPreset\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\ItemPreset\Models\ItemPreset;
use SP\Domain\ItemPreset\Ports\ItemPresetService;
use SP\Domain\ItemPreset\UseCases\CreateItemPresetUseCase;
use SP\Domain\ItemPreset\UseCases\DeleteItemPresetUseCase;
use SP\Domain\ItemPreset\UseCases\DeleteItemPresetsUseCase;
use SP\Domain\ItemPreset\UseCases\Dtos\CreateItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\DeleteItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\DeleteItemPresetsCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\GetItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\SearchItemPresetsCommand;
use SP\Domain\ItemPreset\UseCases\Dtos\UpdateItemPresetCommand;
use SP\Domain\ItemPreset\UseCases\GetItemPresetUseCase;
use SP\Domain\ItemPreset\UseCases\SearchItemPresetsUseCase;
use SP\Domain\ItemPreset\UseCases\UpdateItemPresetUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the ItemPreset use cases.
 */
#[Group('unitary')]
final class ItemPresetUseCasesTest extends TestCase
{
    private ItemPresetService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(ItemPresetService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheValueAndEmitsTheEvent(): void
    {
        $this->service->method('create')->willReturn(7);

        $result = (new CreateItemPresetUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateItemPresetCommand((new ItemPreset())->mutate(['type' => 'account_password'])));

        self::assertSame(7, $result->id);
        self::assertEvent('create.itemPreset', "Value created\nType: account_password\nID: 7");
    }

    #[Test]
    public function updateSavesTheValueAndEmitsTheEvent(): void
    {
        $this->service->method('update')->willReturn(7);

        $result = (new UpdateItemPresetUseCase($this->service, $this->eventEmitter))
            ->execute(new UpdateItemPresetCommand((new ItemPreset())->mutate(['id' => 7, 'type' => 'account_password'])));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.itemPreset', "Value updated\nType: account_password\nID: 7");
    }

    #[Test]
    public function deleteRemovesTheValueAndEmitsTheEvent(): void
    {
        $this->service->method('delete')->willReturnSelf();

        $result = (new DeleteItemPresetUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteItemPresetCommand(7));

        self::assertSame(7, $result->id);
        self::assertEvent('delete.itemPreset', "Value deleted\nID: 7");
    }

    #[Test]
    public function deleteBatchRemovesTheValuesAndEmitsTheEvent(): void
    {
        $this->service->method('deleteByIdBatch')->willReturn(2);

        $result = (new DeleteItemPresetsUseCase($this->service, $this->eventEmitter))
            ->execute(new DeleteItemPresetsCommand([3, 4]));

        self::assertSame([3, 4], $result->ids);
        self::assertEvent('delete.itemPreset.selection', 'Values deleted');
    }

    #[Test]
    public function getReturnsTheValue(): void
    {
        $itemPreset = (new ItemPreset())->mutate(['id' => 7]);
        $this->service->method('getById')->willReturn($itemPreset);

        $result = (new GetItemPresetUseCase($this->service))
            ->execute(new GetItemPresetCommand(7));

        self::assertSame($itemPreset, $result->model);
    }

    #[Test]
    public function searchReturnsTheResult(): void
    {
        $queryResult = new QueryResult();
        $this->service->method('search')->willReturn($queryResult);

        $result = (new SearchItemPresetsUseCase($this->service))
            ->execute(new SearchItemPresetsCommand(new ItemSearchDto()));

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
