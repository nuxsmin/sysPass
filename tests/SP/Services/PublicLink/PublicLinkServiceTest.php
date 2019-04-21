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

namespace SP\Tests\Services\PublicLink;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Config\ConfigData;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Vault;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountExtData;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\NoSuchItemException;
use SP\Services\PublicLink\PublicLinkService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use SP\Util\Util;
use function SP\Tests\setupContext;

/**
 * Class PublicLinkServiceTest
 *
 * @package SP\Tests\Services\PublicLink
 */
class PublicLinkServiceTest extends DatabaseTestCase
{
    /**
     * @var string
     */
    private static $salt;
    /**
     * @var PublicLinkService
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

        self::$dataset = 'syspass_publicLink.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(PublicLinkService::class);

        self::$salt = $dic->get(ConfigData::class)->getPasswordSalt();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAllBasic()
    {
        $data = self::$service->getAllBasic();

        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());

        $this->assertInstanceOf(PublicLinkListData::class, $data[1]);
        $this->assertEquals(3, $data[1]->getId());
        $this->assertEquals(2, $data[1]->getItemId());
        $this->assertEquals('ac744b6948823cb0514546c567981ce4fe7240df396826d99f24fd9a1344', $data[1]->getHash());
        $this->assertNotEmpty($data[1]->getData());
        $this->assertEquals(1, $data[1]->getUserId());
        $this->assertEquals(1, $data[1]->getTypeId());
        $this->assertEquals(0, $data[1]->isNotify());
        $this->assertEquals(1529276100, $data[1]->getDateAdd());
        $this->assertEquals(1532280828, $data[1]->getDateExpire());
        $this->assertEquals(0, $data[1]->getDateUpdate());
        $this->assertEquals(0, $data[1]->getCountViews());
        $this->assertEquals(3, $data[1]->getMaxCountViews());
        $this->assertEquals(0, $data[1]->getTotalCountViews());
        $this->assertNull($data[1]->getUseInfo());
        $this->assertEquals('Apple', $data[1]->getAccountName());
        $this->assertEquals('admin', $data[1]->getUserLogin());
    }

    /**
     * @throws SPException
     */
    public function testGetByHash()
    {
        $hash = 'ced3400ea170619ad7d2589488b6b60747ea99f12e220f5a910ede6d834f';

        $data = self::$service->getByHash($hash);

        $this->assertInstanceOf(PublicLinkData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals($hash, $data->getHash());
        $this->assertNotEmpty($data->getData());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getTypeId());
        $this->assertEquals(0, $data->isNotify());
        $this->assertEquals(1529228863, $data->getDateAdd());
        $this->assertEquals(1532280825, $data->getDateExpire());
        $this->assertEquals(0, $data->getDateUpdate());
        $this->assertEquals(0, $data->getCountViews());
        $this->assertEquals(3, $data->getMaxCountViews());
        $this->assertEquals(0, $data->getTotalCountViews());
        $this->assertNull($data->getUseInfo());

        $this->expectException(NoSuchItemException::class);

        self::$service->getByHash('');
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSearch()
    {
        $itemSearchData = new ItemSearchData();
        $itemSearchData->setLimitCount(10);
        $itemSearchData->setSeachString('Google');

        $result = self::$service->search($itemSearchData);
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals('ced3400ea170619ad7d2589488b6b60747ea99f12e220f5a910ede6d834f', $data[0]->getHash());
        $this->assertNotEmpty($data[0]->getData());
        $this->assertEquals(1, $data[0]->getUserId());
        $this->assertEquals(1, $data[0]->getTypeId());
        $this->assertEquals(0, $data[0]->isNotify());
        $this->assertEquals(1529228863, $data[0]->getDateAdd());
        $this->assertEquals(1532280825, $data[0]->getDateExpire());
        $this->assertEquals(0, $data[0]->getDateUpdate());
        $this->assertEquals(0, $data[0]->getCountViews());
        $this->assertEquals(3, $data[0]->getMaxCountViews());
        $this->assertEquals(0, $data[0]->getTotalCountViews());
        $this->assertNull($data[0]->getUseInfo());
        $this->assertEquals('Google', $data[0]->getAccountName());
        $this->assertEquals('admin', $data[0]->getUserLogin());

        $itemSearchData->setSeachString('Apple');

        $result = self::$service->search($itemSearchData);
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(3, $data[0]->getId());
        $this->assertEquals(2, $data[0]->getItemId());

        $itemSearchData->setSeachString('');

        $result = self::$service->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());
    }

    /**
     * @throws NoSuchItemException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetHashForItem()
    {
        $data = self::$service->getHashForItem(2);

        $this->assertEquals(3, $data->getId());
        $this->assertEquals('ac744b6948823cb0514546c567981ce4fe7240df396826d99f24fd9a1344', $data->getHash());

        $this->expectException(NoSuchItemException::class);

        self::$service->getHashForItem(3);
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreate()
    {
        self::$service->delete(2);

        $data = new PublicLinkData();
        $data->setItemId(1);
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setUserId(1);
        $data->setTypeId(1);
        $data->setNotify(1);
        $data->setDateExpire(time() + 600);
        $data->setDateAdd(time());
        $data->setMaxCountViews(3);

        $this->assertEquals(4, self::$service->create($data));

        /** @var PublicLinkListData $resultData */
        $resultData = self::$service->getById(4);

        $this->assertEquals(4, $resultData->getId());
        $this->assertEquals($data->getItemId(), $resultData->getItemId());
        $this->assertEquals($data->getHash(), $resultData->getHash());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getTypeId(), $resultData->getTypeId());
        $this->assertEquals($data->isNotify(), $resultData->isNotify());
        $this->assertEquals($data->getDateExpire(), $resultData->getDateExpire());
        $this->assertEquals($data->getMaxCountViews(), $resultData->getMaxCountViews());

        $this->checkVaultData($resultData);

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @param PublicLinkListData $data
     *
     * @throws CryptoException
     */
    private function checkVaultData(PublicLinkListData $data)
    {
        $this->assertNotEmpty($data->getData());

        /** @var Vault $vault */
        $vault = Util::unserialize(Vault::class, $data->getData());

        $this->assertInstanceOf(Vault::class, $vault);

        /** @var AccountExtData $accountData */
        $accountData = Util::unserialize(AccountExtData::class, $vault->getData(self::$service->getPublicLinkKey($data->getHash())->getKey()));
        $this->assertInstanceOf(AccountExtData::class, $accountData);
        $this->assertEquals($data->getItemId(), $accountData->getId());
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testCreateSameItemId()
    {
        $data = new PublicLinkData();
        $data->setItemId(2);
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setData('data');
        $data->setUserId(1);
        $data->setTypeId(1);
        $data->setNotify(1);
        $data->setDateExpire(time() + 600);
        $data->setDateAdd(time());
        $data->setMaxCountViews(3);

        $this->expectException(DuplicatedItemException::class);

        self::$service->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testDelete()
    {
        self::$service->delete(2);
        self::$service->delete(3);

        $this->assertEquals(0, $this->conn->getRowCount('PublicLink'));

        $this->expectException(NoSuchItemException::class);

        $this->assertEquals(0, self::$service->delete(10));
    }

    /**
     * @throws ConstraintException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     */
    public function testRefresh()
    {
        $this->assertEquals(1, self::$service->refresh(2));

        /** @var PublicLinkListData $data */
        $data = self::$service->getById(2);

        $this->checkVaultData($data);

        $this->expectException(NoSuchItemException::class);

        self::$service->refresh(4);
    }

    /**
     * @throws ConstraintException
     * @throws ServiceException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$service->deleteByIdBatch([2, 3]));

        $this->assertEquals(0, $this->conn->getRowCount('PublicLink'));

        $this->expectException(ServiceException::class);

        self::$service->deleteByIdBatch([10]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testAddLinkView()
    {
        $hash = 'ac744b6948823cb0514546c567981ce4fe7240df396826d99f24fd9a1344';

        $useInfo[] = [
            'who' => SELF_IP_ADDRESS,
            'time' => time(),
            'hash' => $hash,
            'agent' => 'Mozilla/Firefox',
            'https' => true
        ];

        $data = new PublicLinkData();
        $data->setHash($hash);
        $data->setUseInfo($useInfo);

        self::$service->addLinkView($data);

        /** @var PublicLinkData $resultData */
        $resultData = self::$service->getByHash($hash);

        $this->assertEquals(1, $resultData->getCountViews());
        $this->assertEquals(1, $resultData->getTotalCountViews());
        $this->assertCount(2, unserialize($resultData->getUseInfo()));

        $this->expectException(NoSuchItemException::class);

        $data->setHash('123');

        self::$service->addLinkView($data);
    }

    /**
     * @throws ConstraintException
     * @throws EnvironmentIsBrokenException
     * @throws QueryException
     * @throws SPException
     */
    public function testUpdate()
    {
        $data = new PublicLinkData();
        $data->setId(3);
        $data->setItemId(2);
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setData('data');
        $data->setUserId(2);
        $data->setTypeId(1);
        $data->setNotify(0);
        $data->setDateExpire(time() + 3600);
        $data->setDateAdd(time());
        $data->setMaxCountViews(6);

        $this->assertEquals(1, self::$service->update($data));

        /** @var PublicLinkListData $resultData */
        $resultData = self::$service->getById(3);

        $this->assertEquals(3, $resultData->getId());
        $this->assertEquals($data->getItemId(), $resultData->getItemId());
        $this->assertEquals($data->getHash(), $resultData->getHash());
        $this->assertEquals($data->getData(), $resultData->getData());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getTypeId(), $resultData->getTypeId());
        $this->assertEquals($data->isNotify(), $resultData->isNotify());
        $this->assertEquals($data->getDateExpire(), $resultData->getDateExpire());
        $this->assertEquals($data->getDateAdd(), $resultData->getDateAdd());
        $this->assertEquals($data->getMaxCountViews(), $resultData->getMaxCountViews());

        $this->expectException(ConstraintException::class);

        $data->setItemId(1);

        self::$service->update($data);
    }

    /**
     * @throws SPException
     * @throws CryptoException
     */
    public function testGetById()
    {
        $data = self::$service->getById(2);

        $this->assertInstanceOf(PublicLinkListData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals('ced3400ea170619ad7d2589488b6b60747ea99f12e220f5a910ede6d834f', $data->getHash());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getTypeId());
        $this->assertEquals(0, $data->isNotify());
        $this->assertEquals(1529228863, $data->getDateAdd());
        $this->assertEquals(1532280825, $data->getDateExpire());
        $this->assertEquals(0, $data->getDateUpdate());
        $this->assertEquals(0, $data->getCountViews());
        $this->assertEquals(3, $data->getMaxCountViews());
        $this->assertEquals(0, $data->getTotalCountViews());
        $this->assertNull($data->getUseInfo());
        $this->assertEquals('Google', $data->getAccountName());
        $this->assertEquals('admin', $data->getUserLogin());

        $this->checkVaultData($data);

        $this->expectException(NoSuchItemException::class);

        self::$service->getById(10);
    }
}
