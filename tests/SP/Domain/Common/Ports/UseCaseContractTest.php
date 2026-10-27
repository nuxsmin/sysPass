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
use ReflectionMethod;
use ReflectionNamedType;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Dtos\Result;
use SP\Domain\Common\Ports\UseCase;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagRepository;
use SP\Domain\Tag\UseCases\Dtos\CreateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\CreateTagResult;
use SP\Tests\Stubs\InMemoryTagRepository;

/**
 * Guards the P5-2 use case base contract.
 */
#[Group('unitary')]
final class UseCaseContractTest extends TestCase
{
    public function testContractHasASingleEntryPoint(): void
    {
        $reflection = new ReflectionClass(UseCase::class);

        self::assertTrue($reflection->isInterface());

        $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        self::assertCount(1, $publicMethods, 'A use case exposes exactly one public method');

        $method = $publicMethods[0];

        self::assertSame('execute', $method->getName());
        self::assertSame(1, $method->getNumberOfParameters());
        self::assertSame(1, $method->getNumberOfRequiredParameters());

        $command = $method->getParameters()[0];
        $commandType = $command->getType();

        self::assertInstanceOf(ReflectionNamedType::class, $commandType);
        self::assertSame(Command::class, $commandType->getName());

        $resultType = $method->getReturnType();

        self::assertInstanceOf(ReflectionNamedType::class, $resultType);
        self::assertSame(Result::class, $resultType->getName());
    }

    public function testContractKeepsTheDomainBoundary(): void
    {
        $contents = file_get_contents(REAL_APP_ROOT . '/lib/SP/Domain/Common/Ports/UseCase.php');

        self::assertIsString($contents);

        foreach (['SP\\Core\\', 'SP\\Infrastructure\\', 'SP\\Modules\\', 'SP\\Util\\'] as $outerLayer) {
            self::assertStringNotContainsString($outerLayer, $contents);
        }

        foreach (['__u(', 'processException(', '$_SERVER', '$_SESSION', '$_GET', '$_POST'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $contents);
        }
    }

    public function testUseCaseRunsWithInMemoryFakes(): void
    {
        $repository = new InMemoryTagRepository();
        $useCase = new InMemoryCreateTagUseCase($repository);

        $result = $useCase->execute(new CreateTagCommand('example'));

        self::assertInstanceOf(CreateTagResult::class, $result);
        self::assertSame(1, $result->id);

        $stored = $repository->getById($result->id)->getData(Tag::class);

        self::assertInstanceOf(Tag::class, $stored);
        self::assertSame('example', $stored->getName());
    }
}

/**
 * Minimal use case using the contract with an in-memory Tag repository.
 *
 * @implements UseCase<CreateTagCommand, CreateTagResult>
 */
final readonly class InMemoryCreateTagUseCase implements UseCase
{
    public function __construct(private TagRepository $tagRepository)
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

        return new CreateTagResult($id);
    }
}
