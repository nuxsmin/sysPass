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

namespace SP\Tests\Domain\Plugin\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Plugin\Ports\PluginDataService;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Domain\Plugin\UseCases\DeletePluginUseCase;
use SP\Domain\Plugin\UseCases\DeletePluginsUseCase;
use SP\Domain\Plugin\UseCases\Dtos\DeletePluginCommand;
use SP\Domain\Plugin\UseCases\Dtos\DeletePluginsCommand;
use SP\Domain\Plugin\UseCases\Dtos\ResetPluginCommand;
use SP\Domain\Plugin\UseCases\Dtos\SearchPluginsCommand;
use SP\Domain\Plugin\UseCases\Dtos\TogglePluginCommand;
use SP\Domain\Plugin\UseCases\ResetPluginUseCase;
use SP\Domain\Plugin\UseCases\SearchPluginsUseCase;
use SP\Domain\Plugin\UseCases\TogglePluginUseCase;
use SP\Tests\Generators\PluginGenerator;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the plugin use cases.
 */
#[Group('unitary')]
final class PluginUseCasesTest extends TestCase
{
    private PluginManagerService&MockObject $manager;
    private PluginDataService&MockObject $data;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = $this->createMock(PluginManagerService::class);
        $this->data = $this->createMock(PluginDataService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function enableEmitsTheEnableEvent(): void
    {
        $result = (new TogglePluginUseCase($this->manager, $this->eventEmitter))
            ->execute(new TogglePluginCommand(7, true));

        self::assertSame(7, $result->id);
        self::assertTrue($result->enabled);
        self::assertEvent('edit.plugin.enable', 'Plugin enabled');
    }

    #[Test]
    public function disableEmitsTheDisableEvent(): void
    {
        $result = (new TogglePluginUseCase($this->manager, $this->eventEmitter))
            ->execute(new TogglePluginCommand(7, false));

        self::assertFalse($result->enabled);
        self::assertEvent('edit.plugin.disable', 'Plugin disabled');
    }

    #[Test]
    public function resetDeletesThePluginData(): void
    {
        $plugin = PluginGenerator::factory()->buildPlugin();
        $this->manager->method('getById')->willReturn($plugin);

        $result = (new ResetPluginUseCase($this->manager, $this->data, $this->eventEmitter))
            ->execute(new ResetPluginCommand(7));

        self::assertSame(7, $result->id);
        self::assertEvent('edit.plugin.reset', 'Plugin reset');
    }

    #[Test]
    public function deleteSingleEmitsTheEvent(): void
    {
        $result = (new DeletePluginUseCase($this->manager, $this->eventEmitter))
            ->execute(new DeletePluginCommand(7));

        self::assertSame(7, $result->id);
        self::assertSame('delete.plugin', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function deleteBatchEmitsTheSelectionEvent(): void
    {
        $result = (new DeletePluginsUseCase($this->manager, $this->eventEmitter))
            ->execute(new DeletePluginsCommand([1, 2]));

        self::assertSame([1, 2], $result->ids);
        self::assertSame('delete.plugin.selection', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function searchReturnsThePlugins(): void
    {
        $this->manager->method('search')->willReturn(QueryResult::withTotalNumRows([], 3));

        $result = (new SearchPluginsUseCase($this->manager))
            ->execute(new SearchPluginsCommand(new ItemSearchDto()));

        self::assertSame([], $result->plugins);
        self::assertSame(3, $result->total);
    }

    private function assertEvent(string $name, string $text): void
    {
        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame($name, $event['name']);
        self::assertSame($text, (new EventMessageFormatter())->composeText($event['message']));
    }
}
