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

namespace SP\Tests\Modules\Web\Controllers\ConfigMail;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\IntegrationTestCase;

/**
 * Class ConfigMailTest
 */
#[Group('integration')]
class ConfigMailTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function check()
    {
        $data = [
            'mail_enabled' => true,
            'mail_server' => self::$faker->domainName(),
            'mail_port' => self::$faker->randomNumber(3),
            'mail_user' => self::$faker->userName(),
            'mail_pass' => self::$faker->password(),
            'mail_security' => 'tls',
            'mail_from' => self::$faker->email(),
            'mail_auth_enabled' => self::$faker->boolean(),
            'mail_recipients' => self::$faker->email()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configMail/check'], $data),
        );

        $this->expectOutputString('{"status":"OK","description":"Email sent","data":["Please, check your inbox"]}');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function save()
    {
        $data = [
            'mail_enabled' => true,
            'mail_server' => self::$faker->domainName(),
            'mail_port' => self::$faker->randomNumber(3),
            'mail_user' => self::$faker->userName(),
            'mail_pass' => self::$faker->password(),
            'mail_security' => 'tls',
            'mail_from' => self::$faker->email(),
            'mail_auth_enabled' => self::$faker->boolean(),
            'mail_recipients' => self::$faker->email()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configMail/save'], $data)
        );

        $this->expectOutputString('{"status":"OK","description":"Configuration updated","data":null}');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function saveWithNoChanges()
    {
        $data = [
            'mail_enabled' => false
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configMail/save'], $data)
        );

        $this->expectOutputString('{"status":"OK","description":"Configuration updated","data":null}');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[TestWith(['', ''])]
    #[TestWith(['test_server', ''])]
    #[TestWith(['', 'me@email.com'])]
    public function saveWithMissingParameters(?string $mailServer, ?string $mailFrom)
    {
        $data = [
            'mail_enabled' => true,
            'mail_server' => $mailServer,
            'mail_from' => $mailFrom
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configMail/save'], $data)
        );

        $this->expectOutputString('{"status":"ERROR","description":"Missing Mail parameters","data":null}');

        IntegrationTestCase::runApp($container);
    }

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['isMailEnabled'] = true;

        return $configData;
    }
}
