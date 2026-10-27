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

namespace SP\Tests\Domain\Account\UseCases;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Account\Ports\PublicLinkService;
use SP\Domain\Account\UseCases\CreateAccountPublicLinkUseCase;
use SP\Domain\Account\UseCases\CreatePublicLinkUseCase;
use SP\Domain\Account\UseCases\DeletePublicLinkUseCase;
use SP\Domain\Account\UseCases\DeletePublicLinksUseCase;
use SP\Domain\Account\UseCases\Dtos\CreateAccountPublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\CreatePublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\DeletePublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\DeletePublicLinksCommand;
use SP\Domain\Account\UseCases\Dtos\GetPublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\RefreshPublicLinkCommand;
use SP\Domain\Account\UseCases\Dtos\SearchPublicLinksCommand;
use SP\Domain\Account\UseCases\GetPublicLinkUseCase;
use SP\Domain\Account\UseCases\RefreshPublicLinkUseCase;
use SP\Domain\Account\UseCases\SearchPublicLinksUseCase;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Tests\Generators\PublicLinkDataGenerator;
use SP\Tests\Stubs\InMemoryEventEmitter;

/**
 * Unit tests for the public link use cases.
 */
#[Group('unitary')]
final class PublicLinkUseCasesTest extends TestCase
{
    private PublicLinkService&MockObject $service;
    private InMemoryEventEmitter $eventEmitter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = $this->createMock(PublicLinkService::class);
        $this->eventEmitter = new InMemoryEventEmitter();
    }

    #[Test]
    public function createStoresTheLink(): void
    {
        $this->service->method('create')->willReturn(4);

        $result = (new CreatePublicLinkUseCase($this->service, $this->eventEmitter))
            ->execute(new CreatePublicLinkCommand(PublicLinkDataGenerator::factory()->buildPublicLink()));

        self::assertSame(4, $result->id);
        self::assertSame('create.publicLink', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function createFromAccountStoresTheLink(): void
    {
        $this->service->method('create')->willReturn(4);

        $result = (new CreateAccountPublicLinkUseCase($this->service, $this->eventEmitter))
            ->execute(new CreateAccountPublicLinkCommand(10, true));

        self::assertSame(4, $result->id);
        self::assertSame('create.publicLink.account', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function deleteEmitsTheEvent(): void
    {
        $this->service->method('delete')->willReturnSelf();

        $result = (new DeletePublicLinkUseCase($this->service, $this->eventEmitter))
            ->execute(new DeletePublicLinkCommand(4));

        self::assertSame(4, $result->id);
        self::assertEvent('delete.publicLink', "Link deleted\nLink: 4");
    }

    #[Test]
    public function deleteBatchEmitsTheSelectionEvent(): void
    {
        $this->service->method('deleteByIdBatch')->willReturn(2);

        $result = (new DeletePublicLinksUseCase($this->service, $this->eventEmitter))
            ->execute(new DeletePublicLinksCommand([4, 5]));

        self::assertSame([4, 5], $result->ids);
        self::assertEvent('delete.publicLink.selection', 'Links deleted');
    }

    #[Test]
    public function refreshEmitsTheEvent(): void
    {
        $this->service->method('refresh')->willReturn(true);

        $result = (new RefreshPublicLinkUseCase($this->service, $this->eventEmitter))
            ->execute(new RefreshPublicLinkCommand(4));

        self::assertSame(4, $result->id);
        self::assertSame('edit.publicLink.refresh', $this->eventEmitter->getLastEvent()['name']);
    }

    #[Test]
    public function getReturnsTheLink(): void
    {
        $link = PublicLinkDataGenerator::factory()->buildPublicLinkList();
        $this->service->method('getById')->willReturn($link);

        $result = (new GetPublicLinkUseCase($this->service))
            ->execute(new GetPublicLinkCommand(4));

        self::assertSame($link, $result->publicLink);
    }

    #[Test]
    public function searchReturnsTheLinks(): void
    {
        $this->service->method('search')->willReturn(QueryResult::withTotalNumRows([], 2));

        $result = (new SearchPublicLinksUseCase($this->service))
            ->execute(new SearchPublicLinksCommand(new ItemSearchDto()));

        self::assertSame([], $result->publicLinks);
        self::assertSame(2, $result->total);
    }

    private function assertEvent(string $name, string $text): void
    {
        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame($name, $event['name']);
        self::assertSame($text, (new EventMessageFormatter())->composeText($event['message']));
    }
}
