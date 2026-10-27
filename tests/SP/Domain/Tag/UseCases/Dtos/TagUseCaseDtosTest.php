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

namespace SP\Tests\Domain\Tag\UseCases\Dtos;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionParameter;
use SP\Domain\Common\Dtos\Command;
use SP\Domain\Common\Dtos\Result;
use SP\Domain\Core\Dtos\ItemSearchDto;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\UseCases\Dtos\CreateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\CreateTagResult;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagResult;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagsCommand;
use SP\Domain\Tag\UseCases\Dtos\DeleteTagsResult;
use SP\Domain\Tag\UseCases\Dtos\GetTagCommand;
use SP\Domain\Tag\UseCases\Dtos\GetTagResult;
use SP\Domain\Tag\UseCases\Dtos\SearchTagsCommand;
use SP\Domain\Tag\UseCases\Dtos\SearchTagsResult;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagCommand;
use SP\Domain\Tag\UseCases\Dtos\UpdateTagResult;

/**
 * Guards the P5-1 Command and Result DTO convention on the Tag pilot inputs.
 */
#[Group('unitary')]
final class TagUseCaseDtosTest extends TestCase
{
    private const DTO_DIRECTORY = '/lib/SP/Domain/Tag/UseCases/Dtos';

    /**
     * @return array<string, array{string, class-string<Command|Result>}>
     */
    public static function dtoProvider(): array
    {
        $classes = glob(REAL_APP_ROOT . self::DTO_DIRECTORY . '/*.php') ?: [];

        $dtos = [];

        foreach ($classes as $file) {
            $shortName = basename($file, '.php');
            $dtos[$shortName] = [
                'SP\\Domain\\Tag\\UseCases\\Dtos\\' . $shortName,
                str_ends_with($shortName, 'Command') ? Command::class : Result::class
            ];
        }

        return $dtos;
    }

    /**
     * @param class-string<Command|Result> $contract
     */
    #[DataProvider('dtoProvider')]
    public function testDtoFollowsTheConvention(string $dto, string $contract): void
    {
        $reflection = new ReflectionClass($dto);

        self::assertTrue($reflection->isFinal(), "$dto must be final");
        self::assertTrue($reflection->isReadOnly(), "$dto must be readonly");
        self::assertTrue($reflection->implementsInterface($contract), "$dto must implement $contract");

        $constructor = $reflection->getConstructor();

        self::assertNotNull($constructor, "$dto must take its data through the constructor");

        foreach ($constructor->getParameters() as $parameter) {
            self::assertParameterIsPromotedPublic($dto, $parameter);
        }
    }

    public function testTagCommandsCarryTheInput(): void
    {
        $command = new CreateTagCommand('example');
        $update = new UpdateTagCommand(1, 'renamed');
        $delete = new DeleteTagCommand(2);
        $deleteBatch = new DeleteTagsCommand([3, 4]);
        $get = new GetTagCommand(5);
        $search = new SearchTagsCommand(new ItemSearchDto('term'));

        self::assertSame('example', $command->name);
        self::assertSame(1, $update->id);
        self::assertSame('renamed', $update->name);
        self::assertSame(2, $delete->id);
        self::assertSame([3, 4], $deleteBatch->ids);
        self::assertSame(5, $get->id);
        self::assertSame('term', $search->search->getSeachString());
    }

    public function testTagResultsCarryTheOutput(): void
    {
        $tag = new Tag();

        self::assertSame(1, (new CreateTagResult(1))->id);
        self::assertSame(2, (new UpdateTagResult(2))->id);
        self::assertSame(3, (new DeleteTagResult(3))->id);
        self::assertSame([4, 5], (new DeleteTagsResult([4, 5]))->ids);
        self::assertSame($tag, (new GetTagResult($tag))->tag);

        $search = new SearchTagsResult([$tag], 1);

        self::assertSame([$tag], $search->tags);
        self::assertSame(1, $search->total);
    }

    private static function assertParameterIsPromotedPublic(string $dto, ReflectionParameter $parameter): void
    {
        self::assertTrue($parameter->isPromoted(), "$dto::\${$parameter->getName()} must be a promoted property");
        self::assertTrue(
            (new ReflectionClass($dto))->getProperty($parameter->getName())->isPublic(),
            "$dto::\${$parameter->getName()} must be public"
        );
    }
}
