<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Modules\Cli;

use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use SP\Domain\Core\Context\ContextInterface;
use SP\Infrastructure\Database\DbStorageInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function SPT\getDbHandler;

use const SPT\APP_DEFINITIONS_FILE;

define('APP_MODULE', 'cli');

/**
 * Class CliTestCase
 */
abstract class CliTestCase extends TestCase
{
    protected static ContainerInterface $dic;
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

        $builder = new ContainerBuilder();
        $builder->addDefinitions(
            APP_DEFINITIONS_FILE,
            MODULES_PATH.DIRECTORY_SEPARATOR.'cli'.DIRECTORY_SEPARATOR.'module.php'
        );

        self::$dic = $builder->build();

        $context = self::$dic->get(ContextInterface::class);
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
        self::$dic->set(DbStorageInterface::class, getDbHandler());
    }
}
