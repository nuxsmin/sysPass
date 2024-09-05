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

namespace SP\Tests\Modules\Web\Controllers\ConfigBackup;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\IntegrationTestCase;

/**
 * Class ConfigBackupControllerTest
 */
#[Group('integration')]
class ConfigBackupControllerTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function downloadBackupApp()
    {
        $filename = REAL_APP_ROOT .
                    DIRECTORY_SEPARATOR .
                    'app' .
                    DIRECTORY_SEPARATOR .
                    'backup' .
                    DIRECTORY_SEPARATOR .
                    'sysPass_app-' .
                    $this->passwordSalt .
                    '.gz';

        file_put_contents($filename, 'test_data_app');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configBackup/downloadBackupApp'])
        );

        $this->expectOutputString('test_data_app');

        IntegrationTestCase::runApp($container);

        unlink($filename);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function downloadBackupDb()
    {
        $filename = REAL_APP_ROOT .
                    DIRECTORY_SEPARATOR .
                    'app' .
                    DIRECTORY_SEPARATOR .
                    'backup' .
                    DIRECTORY_SEPARATOR .
                    'sysPass_db-' .
                    $this->passwordSalt .
                    '.gz';

        file_put_contents($filename, 'test_data_db');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configBackup/downloadBackupDb'])
        );

        $this->expectOutputString('test_data_db');

        IntegrationTestCase::runApp($container);

        unlink($filename);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function downloadBackupExport()
    {
        $filename = REAL_APP_ROOT .
                    DIRECTORY_SEPARATOR .
                    'app' .
                    DIRECTORY_SEPARATOR .
                    'backup' .
                    DIRECTORY_SEPARATOR .
                    'sysPass_export-' .
                    $this->passwordSalt .
                    '.gz';

        file_put_contents($filename, 'test_data_export');

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configBackup/downloadExport'])
        );

        $this->expectOutputString('test_data_export');

        IntegrationTestCase::runApp($container);

        unlink($filename);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function fileBackup()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configBackup/fileBackup'])
        );

        $this->expectOutputString('{"status":"OK","description":"Backup process finished","data":null}');

        IntegrationTestCase::runApp($container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws NotFoundExceptionInterface
     */
    #[Test]
    public function xmlExport()
    {
        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('get', 'index.php', ['r' => 'configBackup/xmlExport'])
        );

        $this->expectOutputString('{"status":"OK","description":"Export process finished","data":null}');

        IntegrationTestCase::runApp($container);
    }

    protected function getConfigData(): array
    {
        $configData = parent::getConfigData();
        $configData['getBackupHash'] = $this->passwordSalt;
        $configData['getExportHash'] = $this->passwordSalt;

        return $configData;
    }
}
