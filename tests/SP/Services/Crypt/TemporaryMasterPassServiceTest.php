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

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Repositories\NoSuchItemException;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\ServiceException;
use function SP\Tests\setupContext;

/**
 * Class TemporaryMasterPassServiceTest
 *
 * @package SP\Tests\Services\Crypt
 */
class TemporaryMasterPassServiceTest extends TestCase
{
    /**
     * @var ContextInterface
     */
    private $context;
    /**
     * @var TemporaryMasterPassService
     */
    private $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public function setUp()
    {
        $dic = setupContext();

        // Inicializar el repositorio
        $this->service = $dic->get(TemporaryMasterPassService::class);

        $this->context = $dic->get(ContextInterface::class);
    }

    /**
     * @throws ServiceException
     */
    public function testCreate()
    {
        $key = $this->service->create();

        $this->assertNotEmpty($key);
        $this->assertEquals($this->context->getTrasientKey('_tempmasterpass'), $key);

        return $key;
    }

    /**
     * @depends testCreate
     *
     * @param $key
     *
     * @throws CryptoException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testGetUsingKey($key)
    {
        $this->assertEquals('12345678900', $this->service->getUsingKey($key));

        $this->expectException(CryptoException::class);

        $this->service->getUsingKey('test123');
    }

    /**
     * @depends testCreate
     *
     * @param $key
     *
     * @throws ServiceException
     */
    public function testCheckTempMasterPass($key)
    {
        $this->assertTrue($this->service->checkTempMasterPass($key));

        for ($i = 1; $i <= 50; $i++) {
            $this->assertFalse($this->service->checkTempMasterPass('test123'));
        }

        // The 50's attempt should fails
        $this->assertFalse($this->service->checkTempMasterPass($key));
    }

    /**
     * @throws ServiceException
     */
    public function testExpiredKey()
    {
        $key = $this->service->create(10);

        print 'Sleeping for 12 seconds';

        sleep(12);

        $this->assertFalse($this->service->checkTempMasterPass($key));
    }
}
