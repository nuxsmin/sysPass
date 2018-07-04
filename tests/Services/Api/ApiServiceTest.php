<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use SP\Core\Acl\ActionsInterface;
use SP\Services\Api\ApiService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class ApiServiceTest
 *
 * @package SP\Tests\Services
 */
class ApiServiceTest extends DatabaseTestCase
{
    /**
     * @var ApiService
     */
    private static $service;

    /**
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \DI\DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_account.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(ApiService::class);
    }

    /**
     * @throws \SP\Services\ServiceException
     */
    public function testSetup()
    {
        self::$service->setup(ActionsInterface::ACCOUNT_SEARCH);
    }

    public function testGetParam()
    {

    }

    public function testGetHelp()
    {

    }

    public function testGetParamInt()
    {

    }

    public function testGetParamEmail()
    {

    }

    public function testSetApiRequest()
    {

    }

    public function testGetActions()
    {

    }

    public function testGetParamString()
    {

    }

    public function testGetParamRaw()
    {

    }

    public function testGetRequestId()
    {

    }

    public function testGetMasterPass()
    {

    }
}
