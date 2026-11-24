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

namespace SP\Tests\Infrastructure\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Infrastructure\Crypt\MasterPasswordRequestLock;
use SP\Infrastructure\Database\PDOWrapper;

#[Group('unitary')]
final class MasterPasswordRequestLockTest extends TestCase
{
    #[Test]
    public function testConstructionDoesNotOpenADatabaseConnection(): void
    {
        $pdo = $this->createMock(PDOWrapper::class);
        $pdo->expects(self::never())->method('build');
        $lock = new MasterPasswordRequestLock($this->createStub(ConfigDataInterface::class), $pdo);
        $lock->release();
    }
}
