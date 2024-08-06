<?php
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\Database\QueryData;
use SP\Infrastructure\Database\QueryResult;
use SP\Infrastructure\File\FileException;
use SP\Tests\IntegrationTestCase;

/**
 * Class SaveDeleteControllerTest
 */
#[Group('integration')]
class SaveDeleteControllerTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws InvalidClassException
     * @throws FileException
     */
    public function testSaveDeleteAction()
    {
        $configService = self::createStub(ConfigService::class);
        $configService->method('getByParam')->willReturnArgument(0);

        $definitions = $this->getModuleDefinitions();
        $definitions[ConfigService::class] = $configService;

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'account/saveDelete/1'])
        );

        $this->runApp($container);

        $this->expectOutputString(
            '{"status":0,"description":"Account removed","data":[],"messages":[]}'
        );
    }

    protected function getDatabaseReturn(): callable
    {
        return function (QueryData $queryData): QueryResult {
            if (!empty($queryData->getMapClassName())) {
                $reflection = new ReflectionClass($queryData->getMapClassName());
                return new QueryResult([$reflection->newInstance()], 1, 100);
            }

            return new QueryResult([], 1, 100);
        };
    }
}
