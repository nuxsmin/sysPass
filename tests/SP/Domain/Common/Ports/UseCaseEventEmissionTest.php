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

namespace SP\Tests\Domain\Common\Ports;

use LogicException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionNamedType;
use SP\Core\Messages\EventMessageFormatter;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Dtos\Result;
use SP\Domain\Common\Ports\EventEmitter;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Core\Events\EventMessage;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\Dtos\CreateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\CreateTagResult;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagResult;
use SP\Tests\Stubs\InMemoryEventEmitter;
use SP\Tests\Stubs\InMemoryTagRepository;

/**
 * Guards the P5-3 event emission convention for use cases.
 */
#[Group('unitary')]
final class UseCaseEventEmissionTest extends TestCase
{
    public function testUseCaseEmitsAnUntranslatedAuditEvent(): void
    {
        $repository = new InMemoryTagRepository();
        $emitter = new InMemoryEventEmitter();
        $useCase = new CreateTagWithEventUseCase($repository, $emitter);

        $result = $useCase->execute(new CreateTagCommand('example'));

        self::assertInstanceOf(CreateTagResult::class, $result);
        self::assertSame(1, $result->id);

        $event = $emitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('create.tag', $event['name']);
        self::assertSame($useCase, $event['source']);

        $message = $event['message'];

        self::assertInstanceOf(EventMessage::class, $message);
        self::assertSame(1, $message->getDescriptionCounter());
        self::assertSame(2, $message->getDetailsCounter());

        $formatter = new EventMessageFormatter();

        self::assertSame(
            "Tag added\nName: example\nID: 1",
            $formatter->composeText($message)
        );
    }

    public function testUseCaseKeepsReceiverExtras(): void
    {
        $emitter = new InMemoryEventEmitter();
        $useCase = new CreateTagWithEventUseCase(new InMemoryTagRepository(), $emitter);

        $useCase->execute(new CreateTagCommand('example'));

        $message = $emitter->getLastEvent()['message'] ?? null;

        self::assertInstanceOf(EventMessage::class, $message);
        self::assertSame([7], $message->getExtra('userId'));
    }

    public function testUseCaseCanEmitAMessageLessEvent(): void
    {
        $repository = new InMemoryTagRepository();
        $emitter = new InMemoryEventEmitter();
        $useCase = new DeleteTagWithEventUseCase($repository, $emitter);

        $id = $repository->create((new Tag())->mutate(['name' => 'example']))->getLastId();

        $useCase->execute(new DeleteTagCommand($id));

        self::assertSame(1, $emitter->getEventCount());

        $event = $emitter->getLastEvent();

        self::assertNotNull($event);
        self::assertSame('delete.tag', $event['name']);
        self::assertSame($useCase, $event['source']);
        self::assertNull($event['message']);
    }

    public function testUseCaseOnlyDependsOnPorts(): void
    {
        $constructor = (new ReflectionClass(CreateTagWithEventUseCase::class))->getConstructor();

        self::assertNotNull($constructor);

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            self::assertInstanceOf(ReflectionNamedType::class, $type);
            self::assertTrue(
                interface_exists($type->getName()),
                sprintf('%s must be an interface (port), %s given', $parameter->getName(), $type->getName())
            );
        }
    }

    public function testEventEmitterPortKeepsTheDomainBoundary(): void
    {
        $contents = file_get_contents(REAL_APP_ROOT . '/lib/SP/Domain/Common/Ports/EventEmitter.php');

        self::assertIsString($contents);

        foreach (['SP\\Core\\', 'SP\\Infrastructure\\', 'SP\\Modules\\', 'SP\\Util\\'] as $outerLayer) {
            self::assertStringNotContainsString($outerLayer, $contents);
        }

        foreach (['__u(', '__(\'', 'processException(', '$_SERVER', '$_SESSION', '$_GET', '$_POST'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $contents);
        }
    }
}

/**
 * Minimal use case using the event emitter port and an in-memory Tag repository.
 *
 * @implements UseCase<CreateTagCommand, CreateTagResult>
 */
final readonly class CreateTagWithEventUseCase implements UseCase
{
    public function __construct(private TagRepository $tagRepository, private EventEmitter $eventEmitter)
    {
    }

    public function execute(Command $command): CreateTagResult
    {
        if (!$command instanceof CreateTagCommand) {
            throw new LogicException(sprintf('Expected command %s', CreateTagCommand::class));
        }

        $id = $this->tagRepository
            ->create((new Tag())->mutate(['name' => $command->name]))
            ->getLastId();

        $message = EventMessage::build('Tag added')
            ->addDetail('Name', $command->name)
            ->addDetail('ID', $id)
            ->addExtra('userId', 7);

        $this->eventEmitter->emit('create.tag', $this, $message);

        return new CreateTagResult($id);
    }
}

/**
 * Minimal use case that only publishes an event name, like legacy controllers
 * that emitted `new Event($this)`.
 *
 * @implements UseCase<DeleteTagCommand, DeleteTagResult>
 */
final readonly class DeleteTagWithEventUseCase implements UseCase
{
    public function __construct(private TagRepository $tagRepository, private EventEmitter $eventEmitter)
    {
    }

    public function execute(Command $command): DeleteTagResult
    {
        if (!$command instanceof DeleteTagCommand) {
            throw new LogicException(sprintf('Expected command %s', DeleteTagCommand::class));
        }

        $this->tagRepository->delete($command->id);

        $this->eventEmitter->emit('delete.tag', $this);

        return new DeleteTagResult($command->id);
    }
}
