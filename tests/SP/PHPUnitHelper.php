<?php

declare(strict_types=1);
/*
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

namespace SP\Tests;

use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\Constraint\Constraint;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Infrastructure\File\FileSystem;

/**
 * Trait PHPUnitHelper
 *
 * @method static assertSameSize(array $firstCallArguments, array $consecutiveCallArguments, string $string)
 * @method static assertThat(mixed $actualArgument, Constraint $expected)
 * @method static assertEquals(mixed $expected, mixed $actualArgument)
 * @property PathsContext $pathsContext
 */
trait PHPUnitHelper
{
    /**
     * @param array $firstCallArguments
     * @param array ...$consecutiveCallsArguments
     *
     * @return iterable
     */
    public static function withConsecutive(array $firstCallArguments, array ...$consecutiveCallsArguments): iterable
    {
        foreach ($consecutiveCallsArguments as $consecutiveCallArguments) {
            self::assertSameSize(
                $firstCallArguments,
                $consecutiveCallArguments,
                'Each expected arguments list need to have the same size.'
            );
        }

        $allConsecutiveCallsArguments = [$firstCallArguments, ...$consecutiveCallsArguments];

        $numberOfArguments = count($firstCallArguments);
        $argumentList = [];
        for ($argumentPosition = 0; $argumentPosition < $numberOfArguments; $argumentPosition++) {
            $argumentList[$argumentPosition] = array_column($allConsecutiveCallsArguments, $argumentPosition);
        }

        $mockedMethodCall = 0;
        $callbackCall = 0;
        foreach ($argumentList as $index => $argument) {
            yield new Callback(
                static function (mixed $actualArgument) use (
                    $argumentList,
                    &$mockedMethodCall,
                    &$callbackCall,
                    $index,
                    $numberOfArguments
                ): bool {
                    $expected = $argumentList[$index][$mockedMethodCall] ?? null;

                    $callbackCall++;
                    $mockedMethodCall = (int)($callbackCall / $numberOfArguments);

                    if ($expected instanceof Constraint) {
                        self::assertThat($actualArgument, $expected);
                    } else {
                        self::assertEquals($expected, $actualArgument);
                    }

                    return true;
                },
            );
        }
    }

    /**
     * Return a Callback that implements a generator function
     *
     * @param array $values
     * @return Callback
     */
    public static function withGenerator(array $values): Callback
    {
        return new Callback(function () use ($values) {
            foreach ($values as $value) {
                yield $value;
            }
        });
    }

    public static function withResolveCallableCallback(): Callback
    {
        return new Callback(function (callable $callable) {
            $callable();
            return true;
        });
    }

    public static function getRepositoryStubMethods(string $class): array
    {
        return array_filter(
            get_class_methods($class),
            static fn(string $method) => $method != 'transactionAware'
        );
    }

    /**
     * @return PathsContext
     */
    protected function getPathsContext(): PathsContext
    {
        $pathsContext = new PathsContext();
        $pathsContext->addPath(Path::TMP, TMP_PATH);
        $pathsContext->addPath(Path::APP, APP_PATH);
        $pathsContext->addPath(Path::RESOURCES, RESOURCE_PATH);
        $pathsContext->addPath(Path::CACHE, FileSystem::buildPath(RESOURCE_PATH, 'cache'));
        $pathsContext->addPath(Path::SQL, FileSystem::buildPath(TEST_ROOT, 'schemas'));
        $pathsContext->addPath(Path::CONFIG, FileSystem::buildPath(RESOURCE_PATH, 'config'));
        $pathsContext->addPath(Path::XML_SCHEMA, FileSystem::buildPath(TEST_ROOT, 'schemas', 'syspass.xsd'));

        $this->pathsContext = $pathsContext;

        return $pathsContext;
    }
}
