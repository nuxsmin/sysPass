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

namespace SP\Tests\Core\Crypt;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Core\Crypt\ValidatedMasterKeyStore;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;

#[Group('unitary')]
final class ValidatedMasterKeyStoreTest extends TestCase
{
    #[Test]
    public function testStaleKeyIsRejectedBeforeItCanBeUsed(): void
    {
        $data = $this->createStub(ConfigDataInterface::class);
        $data->method('isInstalled')->willReturn(true);
        $store = $this->createStub(MasterKeyStore::class);
        $store->method('get')->willReturn('stale synthetic');
        $lock = $this->createMock(MasterPasswordRequestLock::class);
        $lock->expects(self::once())->method('assertCurrentMasterPassword')->with('stale synthetic')
            ->willThrowException(ServiceException::error('Master password has changed'));
        $this->expectException(ServiceException::class);
        (new ValidatedMasterKeyStore($store, $lock, $data))->get();
    }
}
