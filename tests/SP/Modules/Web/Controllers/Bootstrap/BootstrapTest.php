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

namespace SP\Tests\Modules\Web\Controllers\Bootstrap;

use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileException;
use SP\Tests\IntegrationTestCase;

/**
 * Class BootstrapTest
 */
#[Group('integration')]
class BootstrapTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     * @throws InvalidClassException
     * @throws FileException
     */
    #[Test]
    public function getEnvironment()
    {
        $definitions = $this->getModuleDefinitions();
        $response = $this->getMockBuilder(Response::class)
                         ->onlyMethods(['body'])
                         ->getMock();
        $response->method('body')
                 ->with(
                     self::callback(static function (string $content) {
                         self::assertNotEmpty($content);

                         $json = json_decode($content);

                         self::assertObjectHasProperty('lang', $json->data);
                         self::assertObjectHasProperty('locale', $json->data);
                         self::assertObjectHasProperty('app_root', $json->data);
                         self::assertObjectHasProperty('max_file_size', $json->data);
                         self::assertObjectHasProperty('check_updates', $json->data);
                         self::assertObjectHasProperty('check_notices', $json->data);
                         self::assertObjectHasProperty('check_notifications', $json->data);
                         self::assertObjectHasProperty('timezone', $json->data);
                         self::assertObjectHasProperty('debug', $json->data);
                         self::assertObjectHasProperty('cookies_enabled', $json->data);
                         self::assertObjectHasProperty('plugins', $json->data);
                         self::assertObjectHasProperty('loggedin', $json->data);
                         self::assertObjectHasProperty('authbasic_autologin', $json->data);
                         self::assertObjectHasProperty('pki_key', $json->data);
                         self::assertObjectHasProperty('pki_max_size', $json->data);
                         self::assertObjectHasProperty('import_allowed_mime', $json->data);
                         self::assertObjectHasProperty('files_allowed_mime', $json->data);
                         self::assertObjectHasProperty('session_timeout', $json->data);
                         self::assertObjectHasProperty('csrf', $json->data);

                         return true;
                     })
                 );

        $definitions[Response::class] = $response;

        $container = $this->buildContainer(
            $definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'bootstrap/getEnvironment'])
        );

        $this->runApp($container);
    }
}
