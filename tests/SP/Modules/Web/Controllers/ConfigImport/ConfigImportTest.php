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

namespace SP\Tests\Modules\Web\Controllers\ConfigImport;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Tests\InjectVault;
use SP\Tests\IntegrationTestCase;

use function SP\Tests\getResource;

/**
 * Class ConfigImportTest
 */
#[Group('integration')]
#[InjectVault]
class ConfigImportTest extends IntegrationTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    #[Test]
    public function import()
    {
        $file = sprintf('%s.csv', self::$faker->filePath());

        file_put_contents($file, getResource('import', 'data.csv'));

        $files = [
            'inFile' => [
                'name' => self::$faker->name(),
                'tmp_name' => $file,
                'size' => filesize($file),
                'type' => 'text/plain'
            ]
        ];

        $data = [
            'import_defaultuser' => self::$faker->randomNumber(3),
            'import_defaultgroup' => self::$faker->randomNumber(3),
            'importPwd' => self::$faker->password(),
            'importMasterPwd' => self::$faker->password(),
            'csvDelimiter' => ';',
        ];

        $container = $this->buildContainer(
            IntegrationTestCase::buildRequest('post', 'index.php', ['r' => 'configImport/import'], $data, $files)
        );

        $this->expectOutputString(
            '{"status":"OK","description":"Import finished","data":"Please check out the event log for more details"}'
        );

        IntegrationTestCase::runApp($container);
    }
}
