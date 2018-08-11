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

namespace SP\Tests\Services\Backup;

use PHPUnit\Framework\TestCase;
use SP\Services\Backup\FileBackupService;
use function SP\Tests\setupContext;

/**
 * Class FileBackupServiceTest
 *
 * @package SP\Tests\Services\Backup
 */
class FileBackupServiceTest extends TestCase
{
    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Services\ServiceException
     */
    public function testDoBackup()
    {
        $dic = setupContext();
        $service = $dic->get(FileBackupService::class);
        $service->doBackup(RESOURCE_DIR);

        $this->assertFileExists($service->getBackupFileApp() . '.gz');
        $this->assertFileExists($service->getBackupFileDb());
    }
}
