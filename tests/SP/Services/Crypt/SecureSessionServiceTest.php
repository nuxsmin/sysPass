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

namespace SP\Tests\Services\Crypt;

use Defuse\Crypto\Key;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\UUIDCookie;
use SP\Services\Crypt\SecureSessionService;
use function SP\Tests\setupContext;

/**
 * Class SecureSessionServiceTest
 *
 * @package SP\Tests\Services\Crypt
 */
class SecureSessionServiceTest extends TestCase
{
    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public function testGetKey()
    {
        $dic = setupContext();
        $service = $dic->get(SecureSessionService::class);

        $stub = $this->createMock(UUIDCookie::class);
        $stub->method('loadCookie')
            ->willReturn(uniqid('', true));
        $stub->method('createCookie')
            ->willReturn(uniqid('', true));

        $this->assertInstanceOf(Key::class, $service->getKey($stub));
        $this->assertFileExists($service->getFilename());
    }
}
