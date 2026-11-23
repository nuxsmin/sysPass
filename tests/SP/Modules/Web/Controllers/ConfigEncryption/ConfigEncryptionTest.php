<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\InjectVault;
use SP\Tests\IntegrationTestCase;

/**
 * Encryption settings boundary tests.
 */
#[Group('integration')]
#[InjectVault]
class ConfigEncryptionTest extends IntegrationTestCase
{
    #[Test]
    public function removedHashOnlySaveRouteReturnsNotFound(): void
    {
        $master = $this->createMock(MasterPassService::class);
        $master->expects(self::never())->method('updateConfig');
        $container = $this->buildContainer(
            self::buildRequest('post', 'index.php', ['r' => 'configEncryption/save'], [
                'no_account_change' => '1', 'confirm_masterpass_change' => '1',
                'current_masterpass' => 'synthetic-old', 'new_masterpass' => 'synthetic-new',
                'new_masterpass_repeat' => 'synthetic-new', 'isAjax' => '1',
            ]),
            [MasterPassService::class => $master]
        );
        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }
        self::assertSame(404, $container->get(\Klein\Response::class)->code());
    }

    #[Test]
    public function removedRefreshRouteReturnsNotFound(): void
    {
        $container = $this->buildContainer(self::buildRequest('get', 'index.php', ['r' => 'configEncryption/refresh']));
        ob_start();
        try {
            self::runApp($container);
        } finally {
            ob_end_clean();
        }
        self::assertSame(404, $container->get(\Klein\Response::class)->code());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function saveTemp()
    {
        $data = [
            'temporary_masterpass_maxtime' => self::$faker->randomNumber(4),
            'temporary_masterpass_group' => self::$faker->randomNumber(3),
            'temporary_masterpass_email' => self::$faker->email()
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configEncryption/saveTemp']),
            $data
        );

        $this->expectOutputString('{"status":"OK","description":"Temporary password generated","data":null}');

        IntegrationTestCase::runApp($container);
    }

    protected function getUserDataDto(): UserDto
    {
        return parent::getUserDataDto()->mutate(['id' => 100]);
    }
}
