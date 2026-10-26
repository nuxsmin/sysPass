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

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\BodyChecker;
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
     */
    #[Test]
    #[BodyChecker('getEnvironmentOutputChecker')]
    public function getEnvironment()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'bootstrap/getEnvironment'])
        );

        IntegrationTestCase::runApp($container);
    }

    public function getEnvironmentOutputChecker(string $output): void
    {
        $json = json_decode($output);

        $properties = [
            'lang',
            'locale',
            'app_root',
            'max_file_size',
            'check_updates',
            'check_notices',
            'check_notifications',
            'timezone',
            'debug',
            'cookies_enabled',
            'plugins',
            'loggedin',
            'authbasic_autologin',
            'pki_key',
            'pki_max_size',
            'import_allowed_mime',
            'files_allowed_mime',
            'session_timeout',
            'csrf',
        ];

        self::assertCount(count($properties), (array)$json->data);

        foreach ($properties as $property) {
            self::assertObjectHasProperty($property, $json->data);
        }
    }
}
