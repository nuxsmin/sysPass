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

namespace SP\Tests\Services\AuthToken;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\Util;
use stdClass;
use function SP\Tests\setupContext;

/**
 * Class AuthTokenServiceTest
 *
 * @package SP\Tests\Services\AuthToken
 */
class AuthTokenServiceTest extends DatabaseTestCase
{
    const AUTH_TOKEN = '2cee8b224f48e01ef48ac172e879cc7825800a9d7ce3b23783212f4758f1c146';
    const AUTH_TOKEN_PASS = 123456;

    /**
     * @var AuthTokenService
     */
    private static $service;

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
        self::$service = $dic->get(AuthTokenService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDelete()
    {
        self::$service->delete(1);

        $this->expectException(NoSuchItemException::class);

        self::$service->delete(10);

        $this->assertEquals(4, $this->conn->getRowCount('AuthToken'));
    }

    /**
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$service->deleteByIdBatch([1, 2]));

        $this->assertEquals(0, self::$service->deleteByIdBatch([]));

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([3, 10]);

        $this->assertEquals(2, $this->conn->getRowCount('AuthToken'));

    }

    /**
     * @throws Exception
     */
    public function testRefreshAndUpdate()
    {
        $data = new AuthTokenData();
        $data->setId(1);
        $data->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $data->setCreatedBy(1);
        $data->setHash(self::AUTH_TOKEN_PASS);
        $data->setUserId(2);

        self::$service->refreshAndUpdate($data);

        $resultData = self::$service->getById(1);

        $vault = Util::unserialize(Vault::class, $resultData->getVault());

        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . $resultData->getToken()));

        $this->expectException(NoSuchItemException::class);

        $data->setId(10);
        $data->setActionId(ActionsInterface::ACCOUNT_DELETE);

        $this->assertEquals(0, self::$service->refreshAndUpdate($data));
    }

    /**
     * @throws ServiceException
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetTokenByToken()
    {
        $data = self::$service->getTokenByToken(ActionsInterface::ACCOUNT_VIEW_PASS, self::AUTH_TOKEN);

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $data->getActionId());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));
        $this->assertNotEmpty($data->getVault());

        /** @var Vault $vault */
        $vault = Util::unserialize(Vault::class, $data->getVault());
        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . self::AUTH_TOKEN));

        $this->expectException(CryptoException::class);

        $vault->getData(1234);
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new AuthTokenData();
        $data->setId(1);
        $data->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $data->setCreatedBy(1);
        $data->setHash(self::AUTH_TOKEN_PASS);
        $data->setUserId(2);

        self::$service->update($data);

        $data = self::$service->getTokenByToken(ActionsInterface::ACCOUNT_CREATE, $data->getToken());

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $data->getActionId());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));
        $this->assertEquals(2, $data->getUserId());

        $vault = Util::unserialize(Vault::class, $data->getVault());

        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . $data->getToken()));

        $this->expectException(NoSuchItemException::class);

        $data->setId(10);
        $data->setUserId(1);

        self::$service->update($data);
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $data = self::$service->getById(1);

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_SEARCH, $data->getActionId());
        $this->assertEquals(pack('H*', '31326239303237643234656666663762666261636138626437373461346333346234356465333565303333643262313932613838663464666165653563323333'), $data->getToken());
        $this->assertNull($data->getHash());

        $data = self::$service->getById(2);

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $data->getActionId());
        $this->assertEquals(self::AUTH_TOKEN, $data->getToken());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));

        $vault = Util::unserialize(Vault::class, $data->getVault());

        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . $data->getToken()));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('admin');

        $result = self::$service->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(4, $result->getNumRows());
        $this->assertCount(4, $data);

        $this->assertInstanceOf(stdClass::class, $data[0]);
        $this->assertEquals(ActionsInterface::ACCOUNT_SEARCH, $data[0]->actionId);
        $this->assertEquals(self::AUTH_TOKEN, $data[0]->token);

        $this->assertInstanceOf(stdClass::class, $data[1]);
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW, $data[1]->actionId);
        $this->assertEquals(self::AUTH_TOKEN, $data[1]->token);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('test');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $authTokenData = new AuthTokenData();
        $authTokenData->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $authTokenData->setCreatedBy(1);
        $authTokenData->setHash(self::AUTH_TOKEN_PASS);
        $authTokenData->setUserId(2);

        $this->assertEquals(6, self::$service->create($authTokenData));
        $this->assertEquals(6, $this->conn->getRowCount('AuthToken'));

        $data = self::$service->getTokenByToken(ActionsInterface::ACCOUNT_CREATE, $authTokenData->getToken());

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $data->getActionId());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));
        $this->assertEquals(6, $data->getId());
        $this->assertEquals(2, $data->getUserId());

        $vault = Util::unserialize(Vault::class, $data->getVault());

        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . $data->getToken()));

        $this->expectException(DuplicatedItemException::class);

        $authTokenData->setUserId(2);

        self::$service->create($authTokenData);
    }
}
