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

namespace SP\Tests\Modules\Cli;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SP\Core\Definitions\CoreDefinitions;
use SP\Core\Definitions\DomainDefinitions;
use SP\Domain\Core\Context\Context;
use SP\Domain\Database\Dtos\DatabaseConnectionData;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\File\FileSystem;
use Symfony\Component\Console\Tester\CommandTester;

use function DI\factory;
use function SP\Tests\getDbHandler;

define('APP_MODULE', 'cli');

/**
 * Class CliTestCase
 */
abstract class CliTestCase extends TestCase
{
    protected static ContainerInterface $dic;
    private static ?array $moduleDefinitions = null;
    /**
     * @var string[]
     */
    protected static array $commandInputData = [];

    /**
     * This method is called before the first test of this test class is run.
     *
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::buildContainer();
    }

    /**
     * @throws Exception
     */
    protected static function buildContainer(): void
    {
        if (self::$moduleDefinitions === null) {
            self::$moduleDefinitions = FileSystem::require(
                FileSystem::buildPath(REAL_APP_ROOT, 'app', 'modules', 'cli', 'module.php')
            );
        }

        $builder = new ContainerBuilder();
        $builder->addDefinitions(array_merge(
            CoreDefinitions::getDefinitions(REAL_APP_ROOT, 'cli'),
            DomainDefinitions::getDefinitions(),
            self::$moduleDefinitions,
            [
                DatabaseConnectionData::class => factory(
                    static fn() => DatabaseConnectionData::getFromEnvironment()
                )
            ]
        ));

        self::$dic = $builder->build();

        $context = self::$dic->get(Context::class);
        $context->initialize();
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function executeCommandTest(
        string $commandClass,
        ?array $inputData = null,
        bool $useInputData = true
    ): CommandTester {
        $installCommand = self::$dic->get($commandClass);

        if (null === $inputData && $useInputData) {
            $inputData = static::$commandInputData;
        }

        $commandTester = new CommandTester($installCommand);
        $commandTester->execute(
            $inputData ?? [],
            ['interactive' => false]
        );

        return $commandTester;
    }

    protected function setupDatabase(): void
    {
        self::$dic->set(DbStorageHandler::class, getDbHandler());
    }
}
