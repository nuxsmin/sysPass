<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Test\Services\Config;

use PHPUnit\Framework\TestCase;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Services\Config\ConfigBackupService;
use function SP\Test\setupContext;

/**
 * Class ConfigBackupServiceTest
 *
 * @package SP\Tests\Services\Config
 */
class ConfigBackupServiceTest extends TestCase
{

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public function testBackup()
    {
        $dic = setupContext();

        $configData = new ConfigData();
        $configData->setConfigVersion(uniqid());

        $service = $dic->get(ConfigBackupService::class);
        $service->backup($configData);

        $this->assertTrue(true);

        return $configData;
    }

    /**
     * @depends testBackup
     *
     * @param ConfigData $configData
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Core\Exceptions\ConfigException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\FileException
     */
    public function testRestore(ConfigData $configData)
    {
        $dic = setupContext();

        $service = $dic->get(ConfigBackupService::class);
        $data = $service->restore();

        $this->assertEquals($configData->getConfigVersion(), $data->getConfigVersion());

        $config = $dic->get(Config::class);
        $this->assertEquals($data->getConfigHash(), $config->loadConfigFromFile()->getConfigHash());

    }
}
