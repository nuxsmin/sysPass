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
use PHPUnit\Framework\MockObject\Stub;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Infrastructure\File\FileException;
use SP\Tests\IntegrationTestCase;

/**
 * Class ConfigBackupControllerTest
 */
#[Group('integration')]
class ConfigBackupControllerTest extends IntegrationTestCase
{
    private array $definitions;

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

        file_put_contents($filename, 'test_data');

        $container = $this->buildContainer(
            $this->definitions,
            $this->buildRequest('get', 'index.php', ['r' => 'configBackup/downloadBackupApp'])
        );

        $this->runApp($container);

        $this->expectOutputString('test_data');
    }

    /**
     * @throws InvalidClassException
     * @throws FileException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->definitions = $this->getModuleDefinitions();
    }

    protected function getConfigData(): ConfigDataInterface|Stub
    {
        $configData = parent::getConfigData();
        $configData->method('getBackupHash')->willReturn($this->passwordSalt);

        return $configData;
    }

}
