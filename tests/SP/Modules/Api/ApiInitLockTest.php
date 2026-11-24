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

namespace SP\Tests\Modules\Api;

use Klein\Klein;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\ModuleDependencies;
use SP\Core\ProvidersHelper;
use SP\Domain\Log\Providers\LogHandler;
use SP\Domain\Core\Exceptions\InitializationException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Core\Ports\AppLockHandler;
use SP\Domain\Crypt\Ports\MasterPasswordRequestLock;
use SP\Domain\Database\Ports\DatabaseUtilInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Modules\Api\Init;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
final class ApiInitLockTest extends UnitaryTestCase
{
    #[Test]
    public function maintenanceRequestsDoNotAcquireTheActivityLock(): void
    {
        $this->config->getConfigData()->setInstalled(true)->setMaintenance(true);
        $request = $this->createStub(RequestService::class);
        $request->method('isAjax')->willReturn(false);
        $lock = $this->createMock(MasterPasswordRequestLock::class);
        $lock->expects(self::never())->method('acquire');
        $init = new Init(
            new ModuleDependencies($this->config, $this->context, $this->eventDispatcherStub),
            new ProvidersHelper((new \ReflectionClass(LogHandler::class))->newInstanceWithoutConstructor()),
            $request,
            new Klein(),
            $this->createStub(LanguageInterface::class),
            $this->createStub(DatabaseUtilInterface::class),
            $this->createStub(AppLockHandler::class),
            $lock
        );
        $this->expectException(InitializationException::class);
        $this->expectExceptionMessage('Maintenance mode');
        $init->initialize('account');
    }
}
