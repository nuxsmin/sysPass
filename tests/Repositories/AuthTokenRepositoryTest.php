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

namespace SP\Tests\Repositories;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Repositories\AuthToken\AuthTokenRepository;
use SP\Repositories\DuplicatedItemException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\Util;
use function SP\Tests\setupContext;

/**
 * Class AuthTokenRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class AuthTokenRepositoryTest extends DatabaseTestCase
{
    const AUTH_TOKEN = '2cee8b224f48e01ef48ac172e879cc7825800a9d7ce3b23783212f4758f1c146';
    const AUTH_TOKEN_PASS = 123456;

    /**
     * @var AuthTokenRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(AuthTokenRepository::class);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetById()
    {
        $authToken = self::$repository->getById(1);

        $this->assertInstanceOf(AuthTokenData::class, $authToken);
        $this->assertEquals(1, $authToken->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_SEARCH, $authToken->getActionId());
        $this->assertEquals(self::AUTH_TOKEN, $authToken->getToken());
        $this->assertNull($authToken->getHash());

        $authToken = self::$repository->getById(2);

        $this->assertInstanceOf(AuthTokenData::class, $authToken);
        $this->assertEquals(2, $authToken->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $authToken->getActionId());
        $this->assertEquals(self::AUTH_TOKEN, $authToken->getToken());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $authToken->getHash()));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetTokenByUserId()
    {
        $this->assertEquals(self::AUTH_TOKEN, self::$repository->getTokenByUserId(1));

        $this->assertNull(self::$repository->getTokenByUserId(2));
    }

    /**
     * @throws CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetTokenByToken()
    {
        $authToken = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_VIEW_PASS, self::AUTH_TOKEN);

        $this->assertEquals(2, $authToken->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $authToken->getActionId());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $authToken->getHash()));
        $this->assertNotEmpty($authToken->getVault());

        /** @var Vault $vault */
        $vault = Util::unserialize(Vault::class, $authToken->getVault());
        $this->assertEquals('12345678900', $vault->getData(self::AUTH_TOKEN_PASS . self::AUTH_TOKEN));

        $this->expectException(CryptoException::class);

        $vault->getData(1234);
    }

    /**
     * @depends testGetTokenByToken
     * @throws CryptoException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testRefreshVaultByUserId()
    {
        $vault = Vault::getInstance()
            ->saveData('prueba', self::AUTH_TOKEN_PASS)
            ->getSerialized();
        $hash = Hash::hashKey(self::AUTH_TOKEN_PASS);

        $this->assertEquals(1, self::$repository->refreshVaultByUserId(1, $vault, $hash));

        $authToken = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_VIEW_PASS, self::AUTH_TOKEN);

        $this->assertInstanceOf(AuthTokenData::class, $authToken);
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $authToken->getHash()));
        $this->assertEquals($vault, $authToken->getVault());

        /** @var Vault $vault */
        $vault = Util::unserialize(Vault::class, $authToken->getVault());
        $this->assertEquals('prueba', $vault->getData(self::AUTH_TOKEN_PASS));
    }

    /**
     * @depends testGetTokenByUserId
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testRefreshTokenByUserId()
    {
        $token = Util::generateRandomBytes();

        // Comprobar actualización con usuario que existe
        $this->assertEquals(2, self::$repository->refreshTokenByUserId(1, $token));
        $this->assertEquals($token, self::$repository->getTokenByUserId(1));

        // Comprobar actualización con usuario que NO existe
        $this->assertEquals(0, self::$repository->refreshTokenByUserId(2, $token));
        $this->assertNull(self::$repository->getTokenByUserId(2));
    }

    /**
     * @covers  \SP\Repositories\AuthToken\AuthTokenRepository::checkDuplicatedOnUpdate()
     * @depends testGetTokenByToken
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Repositories\DuplicatedItemException
     * @throws CryptoException
     */
    public function testUpdate()
    {
        $token = Util::generateRandomBytes();
        $hash = Hash::hashKey('prueba123');
        $vault = Vault::getInstance()->saveData('prueba', 'prueba123');

        $authTokenData = new AuthTokenData();
        $authTokenData->setId(1);
        $authTokenData->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $authTokenData->setCreatedBy(1);
        $authTokenData->setHash($hash);
        $authTokenData->setToken($token);
        $authTokenData->setVault($vault);
        $authTokenData->setUserId(2);

        $this->assertEquals(1, self::$repository->update($authTokenData));

        $result = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_CREATE, $token);

        $this->assertInstanceOf(AuthTokenData::class, $result);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $result->getActionId());
        $this->assertEquals($hash, $result->getHash());
        $this->assertEquals(2, $result->getUserId());
        $this->assertEquals($vault->getSerialized(), $result->getVault());

        $this->expectException(DuplicatedItemException::class);

        $authTokenData->setId(2);
        $authTokenData->setUserId(1);

        self::$repository->update($authTokenData);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('admin');

        $result = self::$repository->search($itemSearchData);
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());
        $this->assertCount(2, $data);

        $this->assertInstanceOf(\stdClass::class, $data[0]);
        $this->assertEquals(ActionsInterface::ACCOUNT_SEARCH, $data[0]->actionId);
        $this->assertEquals(self::AUTH_TOKEN, $data[0]->token);

        $this->assertInstanceOf(\stdClass::class, $data[1]);
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $data[1]->actionId);
        $this->assertEquals(self::AUTH_TOKEN, $data[1]->token);

        $itemSearchData = new ItemSearchData();
        $itemSearchData->setSeachString('test');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, $this->conn->getRowCount('AuthToken'));

        $this->assertEquals(2, self::$repository->deleteByIdBatch([1, 2, 3]));
        $this->assertEquals(0, $this->conn->getRowCount('AuthToken'));

        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetUserIdForToken()
    {
        $this->assertEquals(1, self::$repository->getUserIdForToken(self::AUTH_TOKEN));

        $this->assertFalse(self::$repository->getUserIdForToken('no_token'));
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));

        $this->assertEquals(0, self::$repository->delete(10));
    }

    /**
     * @covers \SP\Repositories\AuthToken\AuthTokenRepository::checkDuplicatedOnAdd()
     *
     * @throws CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function testCreate()
    {
        $token = Util::generateRandomBytes();
        $hash = Hash::hashKey('prueba123');
        $vault = Vault::getInstance()->saveData('prueba', 'prueba123');

        $authTokenData = new AuthTokenData();
        $authTokenData->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $authTokenData->setCreatedBy(1);
        $authTokenData->setHash($hash);
        $authTokenData->setToken($token);
        $authTokenData->setVault($vault);
        $authTokenData->setUserId(2);

        $this->assertEquals(3, self::$repository->create($authTokenData));
        $this->assertEquals(3, $this->conn->getRowCount('AuthToken'));

        $result = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_CREATE, $token);

        $this->assertInstanceOf(AuthTokenData::class, $result);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $result->getActionId());
        $this->assertEquals($hash, $result->getHash());
        $this->assertEquals(3, $result->getId());
        $this->assertEquals(2, $result->getUserId());
        $this->assertEquals($vault->getSerialized(), $result->getVault());

        $this->expectException(DuplicatedItemException::class);

        $authTokenData->setUserId(1);

        self::$repository->create($authTokenData);
    }
}
