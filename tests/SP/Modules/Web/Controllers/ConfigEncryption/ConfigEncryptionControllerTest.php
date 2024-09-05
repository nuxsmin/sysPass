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

namespace SP\Tests\Modules\Web\Controllers\ConfigEncryption;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\InjectConfigParam;
use SP\Tests\InjectVault;
use SP\Tests\IntegrationTestCase;

/**
 * Class RefreshControllerTest
 */
#[Group('integration')]
#[InjectVault]
class ConfigEncryptionControllerTest extends IntegrationTestCase
{

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function refresh()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configEncryption/refresh'])
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString('{"status":"OK","description":"Master password hash updated","data":null}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    #[InjectConfigParam(['masterPwd' => '$2y$10$6imglyA01feP1AnEmlAgUeQPa7vysHeKQLz0MDA1Zf7iG6ep.7PaC'])]
    #[TestWith([false], 'Change acoounts encryption')]
    #[TestWith([true], 'Change only hash')]
    public function save(bool $accountChange)
    {
        $data = [
            'current_masterpass' => 'a_password',
            'new_masterpass' => 'a_new_password',
            'new_masterpass_repeat' => 'a_new_password',
            'confirm_masterpass_change' => 'true',
            'no_account_change' => $accountChange,
            'isAjax' => 1
        ];

        $configData = $this->getConfigData();
        $configData['isMaintenance'] = true;

        $appLockHandler = self::createStub(AppLockHandler::class);
        $appLockHandler
            ->method('getLock')
            ->willReturn(100);

        $configData = self::createConfiguredStub(ConfigDataInterface::class, $configData);
        $configFileService = self::createStub(ConfigFileService::class);
        $configFileService
            ->method('getConfigData')
            ->willReturn($configData);

        $definitions = [
            ConfigFileService::class => $configFileService,
            AppLockHandler::class => $appLockHandler,
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest(
                'post',
                'index.php',
                ['r' => 'configEncryption/save'],
                $data
            ),
            $definitions
        );

        IntegrationTestCase::runApp($container);

        $this->expectOutputString(
            '{"status":"OK","description":"Master password updated","data":"Please, restart the session to update it"}'
        );
    }

    protected function getUserDataDto(): UserDto
    {
        return parent::getUserDataDto()->mutate(['id' => 100]);
    }
}
