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
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AuthTokenData;
use SP\DataModel\ItemSearchData;
use SP\Infrastructure\Auth\Repositories\AuthTokenRepository;
use SP\Infrastructure\Common\Repositories\DuplicatedItemException;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use SP\Util\Util;
use stdClass;
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
     * @var \SP\Infrastructure\Auth\Repositories\AuthTokenRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el repositorio
        self::$repository = $dic->get(AuthTokenRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(1);
        $this->assertEquals(1, $result->getNumRows());

        $data = $result->getData();

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(1, $data->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_SEARCH, $data->getActionId());
        $this->assertEquals('12b9027d24efff7bfbaca8bd774a4c34b45de35e033d2b192a88f4dfaee5c233', $data->getToken());
        $this->assertNull($data->getHash());

        $result = self::$repository->getById(2);
        $this->assertEquals(1, $result->getNumRows());

        $data = $result->getData();

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(ActionsInterface::ACCOUNT_VIEW_PASS, $data->getActionId());
        $this->assertEquals(self::AUTH_TOKEN, $data->getToken());
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetTokenByUserId()
    {
        $this->assertEquals(self::AUTH_TOKEN, self::$repository->getTokenByUserId(1));

        $this->assertNull(self::$repository->getTokenByUserId(3));
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetTokenByToken()
    {
        $result = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_VIEW_PASS, self::AUTH_TOKEN);
        /** @var AuthTokenData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
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
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRefreshVaultByUserId()
    {
        $vault = Vault::getInstance()
            ->saveData('prueba', self::AUTH_TOKEN_PASS)
            ->getSerialized();
        $hash = Hash::hashKey(self::AUTH_TOKEN_PASS);

        $this->assertEquals(1, self::$repository->refreshVaultByUserId(1, $vault, $hash));

        $result = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_VIEW_PASS, self::AUTH_TOKEN);
        /** @var AuthTokenData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());

        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertTrue(Hash::checkHashKey(self::AUTH_TOKEN_PASS, $data->getHash()));
        $this->assertEquals($vault, $data->getVault());

        /** @var Vault $vault */
        $vault = Util::unserialize(Vault::class, $data->getVault());
        $this->assertEquals('prueba', $vault->getData(self::AUTH_TOKEN_PASS));
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testRefreshTokenByUserId()
    {
        $token = PasswordUtil::generateRandomBytes();

        // Comprobar actualización con usuario que existe
        $this->assertEquals(4, self::$repository->refreshTokenByUserId(1, $token));
        $this->assertEquals($token, self::$repository->getTokenByUserId(1));

        // Comprobar actualización con usuario que NO existe
        $this->assertEquals(0, self::$repository->refreshTokenByUserId(10, $token));
        $this->assertNull(self::$repository->getTokenByUserId(10));
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws CryptoException
     */
    public function testUpdate()
    {
        $token = PasswordUtil::generateRandomBytes();
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
        /** @var AuthTokenData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $data->getActionId());
        $this->assertEquals($hash, $data->getHash());
        $this->assertEquals(2, $data->getUserId());
        $this->assertEquals($vault->getSerialized(), $data->getVault());

        $this->expectException(DuplicatedItemException::class);

        $authTokenData->setId(2);
        $authTokenData->setUserId(1);

        self::$repository->update($authTokenData);
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

        $result = self::$repository->search($itemSearchData);
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

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(5, self::getRowCount('AuthToken'));

        $this->assertEquals(3, self::$repository->deleteByIdBatch([1, 2, 3]));

        $this->assertEquals(2, self::getRowCount('AuthToken'));

        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetUserIdForToken()
    {
        $this->assertEquals(1, self::$repository->getUserIdForToken(self::AUTH_TOKEN));

        $this->assertFalse(self::$repository->getUserIdForToken('no_token'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));

        $this->assertEquals(0, self::$repository->delete(10));
    }

    /**
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        $token = PasswordUtil::generateRandomBytes();
        $hash = Hash::hashKey('prueba123');
        $vault = Vault::getInstance()->saveData('prueba', 'prueba123');

        $authTokenData = new AuthTokenData();
        $authTokenData->setActionId(ActionsInterface::ACCOUNT_CREATE);
        $authTokenData->setCreatedBy(1);
        $authTokenData->setHash($hash);
        $authTokenData->setToken($token);
        $authTokenData->setVault($vault);
        $authTokenData->setUserId(2);

        $this->assertEquals(6, self::$repository->create($authTokenData));
        $this->assertEquals(6, self::getRowCount('AuthToken'));

        $result = self::$repository->getTokenByToken(ActionsInterface::ACCOUNT_CREATE, $token);
        /** @var AuthTokenData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(AuthTokenData::class, $data);
        $this->assertEquals(ActionsInterface::ACCOUNT_CREATE, $data->getActionId());
        $this->assertEquals($hash, $data->getHash());
        $this->assertEquals(6, $data->getId());
        $this->assertEquals(2, $data->getUserId());
        $this->assertEquals($vault->getSerialized(), $data->getVault());

        $this->expectException(DuplicatedItemException::class);

        $authTokenData->setUserId(1);

        self::$repository->create($authTokenData);
    }
}
