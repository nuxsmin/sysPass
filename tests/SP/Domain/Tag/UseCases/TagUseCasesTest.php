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

namespace SP\Tests\Domain\Tag\UseCases;

use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Tag\Exceptions\TagException;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\CreateTagUseCase;
use SP\Domain\Tag\UseCases\DeleteTagUseCase;
use SP\Domain\Tag\UseCases\DeleteTagsUseCase;
use SP\Domain\Tag\UseCases\Dtos\CreateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagsCommand;
use SP\Domain\Tag\UseCases\Dtos\GetTagCommand;
use SP\Domain\Tag\UseCases\Dtos\SearchTagsCommand;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagCommand;
use SP\Domain\Tag\UseCases\GetTagUseCase;
use SP\Domain\Tag\UseCases\SearchTagsUseCase;
use SP\Domain\Tag\UseCases\UpdateTagUseCase;
use SP\Tests\Stubs\InMemoryEventEmitter;
use SP\Tests\Stubs\InMemoryTagRepository;

/**
 * Unit tests for the Tag use cases using in-memory fakes.
 */
#[Group('unitary')]
final class TagUseCasesTest extends TestCase
{
    private InMemoryTagRepository $tagRepository;
    private InMemoryEventEmitter $eventEmitter;
    private CustomFieldDataService&MockObject $customFieldDataService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tagRepository = new InMemoryTagRepository();
        $this->eventEmitter = new InMemoryEventEmitter();
        $this->customFieldDataService = $this->createMock(CustomFieldDataService::class);
    }

    #[Test]
    public function createTagStoresTheTagAndEmitsTheEvent(): void
    {
        $result = (new CreateTagUseCase($this->tagRepository, $this->eventEmitter))
            ->execute(new CreateTagCommand('example'));

        self::assertSame(1, $result->id);
        self::assertSame('example', $this->tagRepository->getById(1)->getData(Tag::class)?->getName());

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('create.tag', $event['name']);
        self::assertSame(
            "Tag added\nName: example\nID: 1",
            (new EventMessageFormatter())->composeText($event['message'])
        );
    }

    #[Test]
    public function createTagRejectsAnEmptyName(): void
    {
        $this->expectException(TagException::class);
        $this->expectExceptionCode(TagException::NAME_REQUIRED);
        $this->expectExceptionMessage('A tag name is needed');

        (new CreateTagUseCase($this->tagRepository, $this->eventEmitter))
            ->execute(new CreateTagCommand(''));
    }

    #[Test]
    public function updateTagChangesTheTagAndEmitsTheEvent(): void
    {
        $id = $this->seedTag('example');

        $result = (new UpdateTagUseCase($this->tagRepository, $this->eventEmitter))
            ->execute(new UpdateTagCommand($id, 'renamed'));

        self::assertSame($id, $result->id);
        self::assertSame('renamed', $this->tagRepository->getById($id)->getData(Tag::class)?->getName());

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('edit.tag', $event['name']);
        self::assertSame(
            "Tag updated\nName: renamed\nID: 1",
            (new EventMessageFormatter())->composeText($event['message'])
        );
    }

    #[Test]
    public function updateTagRejectsAnEmptyName(): void
    {
        $this->expectException(TagException::class);
        $this->expectExceptionCode(TagException::NAME_REQUIRED);

        (new UpdateTagUseCase($this->tagRepository, $this->eventEmitter))
            ->execute(new UpdateTagCommand(1, ''));
    }

    #[Test]
    public function deleteTagRemovesTheTagAndItsCustomFields(): void
    {
        $id = $this->seedTag('example');

        $this->customFieldDataService
            ->expects(self::once())
            ->method('delete')
            ->with([$id], AclActionsInterface::TAG);

        $result = (new DeleteTagUseCase(
            $this->tagRepository,
            $this->customFieldDataService,
            $this->eventEmitter
        ))->execute(new DeleteTagCommand($id));

        self::assertSame($id, $result->id);
        self::assertSame(0, $this->tagRepository->getById($id)->getNumRows());

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('delete.tag', $event['name']);
        self::assertSame(
            "Tag removed\nID: 1",
            (new EventMessageFormatter())->composeText($event['message'])
        );
    }

    #[Test]
    public function deleteTagThrowsWhenTheTagDoesNotExist(): void
    {
        $this->customFieldDataService->expects(self::never())->method('delete');

        $this->expectException(TagException::class);
        $this->expectExceptionCode(TagException::NOT_FOUND);
        $this->expectExceptionMessage('Tag not found');

        (new DeleteTagUseCase(
            $this->tagRepository,
            $this->customFieldDataService,
            $this->eventEmitter
        ))->execute(new DeleteTagCommand(100));
    }

    #[Test]
    public function deleteTagsRemovesTheTagsAndTheirCustomFields(): void
    {
        $ids = [$this->seedTag('one'), $this->seedTag('two')];

        $this->customFieldDataService
            ->expects(self::once())
            ->method('delete')
            ->with($ids, AclActionsInterface::TAG);

        $result = (new DeleteTagsUseCase(
            $this->tagRepository,
            $this->customFieldDataService,
            $this->eventEmitter
        ))->execute(new DeleteTagsCommand($ids));

        self::assertSame($ids, $result->ids);

        foreach ($ids as $id) {
            self::assertSame(0, $this->tagRepository->getById($id)->getNumRows());
        }

        $event = $this->eventEmitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('delete.tag.selection', $event['name']);
        self::assertSame('Tags deleted', (new EventMessageFormatter())->composeText($event['message']));
    }

    #[Test]
    public function deleteTagsThrowsWhenTheBatchDoesNotMatch(): void
    {
        $this->customFieldDataService->expects(self::never())->method('delete');

        $this->expectException(TagException::class);
        $this->expectExceptionCode(TagException::DELETE_FAILED);
        $this->expectExceptionMessage('Error while removing the tags');

        (new DeleteTagsUseCase(
            $this->tagRepository,
            $this->customFieldDataService,
            $this->eventEmitter
        ))->execute(new DeleteTagsCommand([100, 200]));
    }

    #[Test]
    public function getTagReturnsTheTag(): void
    {
        $id = $this->seedTag('example');

        $result = (new GetTagUseCase($this->tagRepository))->execute(new GetTagCommand($id));

        self::assertSame($id, $result->tag->getId());
        self::assertSame('example', $result->tag->getName());
    }

    #[Test]
    public function getTagThrowsWhenTheTagDoesNotExist(): void
    {
        $this->expectException(TagException::class);
        $this->expectExceptionCode(TagException::NOT_FOUND);

        (new GetTagUseCase($this->tagRepository))->execute(new GetTagCommand(100));
    }

    #[Test]
    public function searchTagsReturnsTheTagsAndTheTotal(): void
    {
        $this->seedTag('one');
        $this->seedTag('two');

        $result = (new SearchTagsUseCase($this->tagRepository))
            ->execute(new SearchTagsCommand(new ItemSearchDto('example')));

        self::assertCount(2, $result->tags);
        self::assertSame(2, $result->total);
    }

    #[Test]
    public function useCasesOnlyDependOnPorts(): void
    {
        $useCases = [
            CreateTagUseCase::class,
            UpdateTagUseCase::class,
            DeleteTagUseCase::class,
            DeleteTagsUseCase::class,
            GetTagUseCase::class,
            SearchTagsUseCase::class,
        ];

        foreach ($useCases as $useCase) {
            $constructor = (new ReflectionClass($useCase))->getConstructor();

            self::assertNotNull($constructor, "$useCase must take its dependencies through the constructor");

            foreach ($constructor->getParameters() as $parameter) {
                $type = $parameter->getType();

                self::assertInstanceOf(ReflectionNamedType::class, $type);
                self::assertTrue(
                    interface_exists($type->getName()),
                    sprintf('%s::$%s must be an interface (port), %s given', $useCase, $parameter->getName(), $type->getName())
                );
            }
        }
    }

    private function seedTag(string $name): int
    {
        return $this->tagRepository->create((new Tag())->mutate(['name' => $name]))->getLastId();
    }
}
