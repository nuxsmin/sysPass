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

namespace SP\Tests\Modules\Web\Controllers\AccountFavorite;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileException;
use SP\Tests\IntegrationTestCase;

/**
 * Class AccountFavoriteTest
 */
#[Group('integration')]
class AccountFavoriteTest extends IntegrationTestCase
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws FileException
     * @throws InvalidClassException
     * @throws ContainerExceptionInterface
     */
    public function testMarkAction()
    {
        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountFavorite/mark/100'])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Favorite added","data":[],"messages":[]}');
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Exception
     * @throws FileException
     * @throws InvalidClassException
     * @throws ContainerExceptionInterface
     */
    public function testUnmarkAction()
    {
        $definitions = $this->getModuleDefinitions();

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('post', 'index.php', ['r' => 'accountFavorite/unmark/100'])
        );

        $this->runApp($container);

        $this->expectOutputString('{"status":0,"description":"Favorite deleted","data":[],"messages":[]}');
    }
}
