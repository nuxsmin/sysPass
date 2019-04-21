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

namespace SP\Tests\Services\Api;

use Closure;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\SPException;
use SP\Services\Api\ApiRequest;
use SP\Services\Api\ApiService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\getResource;
use function SP\Tests\setupContext;

/**
 * Class ApiServiceTest
 *
 * @package SP\Tests\Services
 */
class ApiServiceTest extends DatabaseTestCase
{
    const ADMIN_TOKEN = '2cee8b224f48e01ef48ac172e879cc7825800a9d7ce3b23783212f4758f1c146';
    const ADMIN_PASS = '123456';
    const DEMO_TOKEN = '12b9027d24efff7bfbaca8bd774a4c34b45de35e033d2b192a88f4dfaee5c233';

    /**
     * @var ApiService
     */
    private static $service;
    /**
     * @var Closure
     */
    private static $changeRequest;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_authToken.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(ApiService::class);

        self::$changeRequest = function (string $request) use ($dic) {
            $dic->set(ApiRequest::class, new ApiRequest($request));
        };
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testSetup()
    {
        self::$changeRequest->call($this, getResource('json', 'account_search.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertTrue(self::$service->isInitialized());

        self::$service->setup(ActionsInterface::ACCOUNT_VIEW);

        $this->assertTrue(self::$service->isInitialized());

        self::$service->setup(ActionsInterface::ACCOUNT_DELETE);

        $this->assertTrue(self::$service->isInitialized());

        self::$changeRequest->call($this, getResource('json', 'account_viewPass.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_VIEW_PASS);

        $this->assertTrue(self::$service->isInitialized());

        $this->expectException(ServiceException::class);

        self::$service->setup(ActionsInterface::ACCOUNT_CREATE);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetParam()
    {
        self::$changeRequest->call($this, getResource('json', 'account_search.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertEquals('2cee8b224f48e01ef48ac172e879cc7825800a9d7ce3b23783212f4758f1c146', self::$service->getParam('authToken'));
        $this->assertEquals('API', self::$service->getParam('text'));
        $this->assertEquals('5', self::$service->getParam('count'));
        $this->assertEquals('1', self::$service->getParam('categoryId'));
        $this->assertEquals('1', self::$service->getParam('clientId'));

        $this->assertNull(self::$service->getParam('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionCode(-32602);

        self::$service->getParam('test', true);
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetParamInt()
    {
        self::$changeRequest->call($this, getResource('json', 'account_search.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertEquals(1, self::$service->getParamInt('categoryId'));
        $this->assertEquals(0, self::$service->getParamInt('text'));
    }

    /**
     */
    public function testGetParamEmail()
    {
        $this->markTestIncomplete();
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetParamString()
    {
        self::$changeRequest->call($this, getResource('json', 'account_add.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertEquals("bla bla bla\nbla bla~!?|.$%&/()=¿ªº€\"'", self::$service->getParamString('notes'));

        $this->assertEmpty(self::$service->getParamString('test'));
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetParamRaw()
    {
        self::$changeRequest->call($this, getResource('json', 'account_add.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertEquals("bla bla bla\nbla bla~!?|.$%&/()=¿ªº€\"'", self::$service->getParamRaw('notes'));
    }

    /**
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetRequestId()
    {
        self::$changeRequest->call($this, getResource('json', 'account_search.json'));

        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);

        $this->assertEquals(10, self::$service->getRequestId());
    }

    /**
     * @throws ServiceException
     */
    public function testGetMasterPass()
    {
        $this->assertEquals('12345678900', self::$service->getMasterPass());
    }
}
