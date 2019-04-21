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

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Repositories\DuplicatedItemException;
use SP\Repositories\PublicLink\PublicLinkRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use function SP\Tests\setupContext;

/**
 * Class PublicLinkRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class PublicLinkRepositoryTest extends DatabaseTestCase
{
    /**
     * @var PublicLinkRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(PublicLinkRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetHashForItem()
    {
        $result = self::$repository->getHashForItem(2);
        /** @var PublicLinkData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertEquals(1, $data->getId());
        $this->assertEquals(pack('H*', '646134633934396166303637386334353130313363626137633133626463396137636135383731383034663137343134306636626161653236346464'), $data->getHash());

        $result = self::$repository->getHashForItem(3);
        $this->assertEquals(0, $result->getNumRows());
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

        $result = self::$repository->search($itemSearchData);
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(2, $data[0]->getId());
        $this->assertEquals(1, $data[0]->getItemId());
        $this->assertEquals(pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635'), $data[0]->getHash());
        $this->assertNotEmpty($data[0]->getData());
        $this->assertEquals(1, $data[0]->getUserId());
        $this->assertEquals(1, $data[0]->getTypeId());
        $this->assertEquals(0, $data[0]->isNotify());
        $this->assertEquals(1529228863, $data[0]->getDateAdd());
        $this->assertEquals(1529229463, $data[0]->getDateExpire());
        $this->assertEquals(0, $data[0]->getDateUpdate());
        $this->assertEquals(0, $data[0]->getCountViews());
        $this->assertEquals(3, $data[0]->getMaxCountViews());
        $this->assertEquals(0, $data[0]->getTotalCountViews());
        $this->assertNull($data[0]->getUseInfo());
        $this->assertEquals('Google', $data[0]->getAccountName());
        $this->assertEquals('admin', $data[0]->getUserLogin());

        $itemSearchData->setSeachString('Apple');

        $result = self::$repository->search($itemSearchData);
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());
        $this->assertEquals(2, $data[0]->getItemId());

        $itemSearchData->setSeachString('');

        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(2, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(2, self::$repository->deleteByIdBatch([1, 2, 3]));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));

        $this->assertEquals(0, $this->conn->getRowCount('PublicLink'));
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     * @throws DuplicatedItemException
     * @throws SPException
     */
    public function testCreate()
    {
        self::$repository->delete(2);

        $data = new PublicLinkData();
        $data->setItemId(1);
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setData('data');
        $data->setUserId(1);
        $data->setTypeId(1);
        $data->setNotify(1);
        $data->setDateExpire(time() + 600);
        $data->setDateAdd(time());
        $data->setMaxCountViews(3);

        $this->assertEquals(3, self::$repository->create($data)->getLastId());

        /** @var PublicLinkListData $resultData */
        $resultData = self::$repository->getById(3)->getData();

        $this->assertEquals(3, $resultData->getId());
        $this->assertEquals($data->getItemId(), $resultData->getItemId());
        $this->assertEquals($data->getHash(), $resultData->getHash());
        $this->assertEquals($data->getData(), $resultData->getData());
        $this->assertEquals($data->getUserId(), $resultData->getUserId());
        $this->assertEquals($data->getTypeId(), $resultData->getTypeId());
        $this->assertEquals($data->isNotify(), $resultData->isNotify());
        $this->assertEquals($data->getDateExpire(), $resultData->getDateExpire());
        $this->assertTrue($data->getDateAdd() <= $resultData->getDateAdd());
        $this->assertEquals($data->getMaxCountViews(), $resultData->getMaxCountViews());

        $this->expectException(DuplicatedItemException::class);

        self::$repository->create($data);

        $this->expectException(ConstraintException::class);

        $data->setItemId(3);

        self::$repository->create($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(2);
        /** @var PublicLinkListData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(PublicLinkListData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals(pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635'), $data->getHash());
        $this->assertNotEmpty($data->getData());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getTypeId());
        $this->assertEquals(0, $data->isNotify());
        $this->assertEquals(1529228863, $data->getDateAdd());
        $this->assertEquals(1529229463, $data->getDateExpire());
        $this->assertEquals(0, $data->getDateUpdate());
        $this->assertEquals(0, $data->getCountViews());
        $this->assertEquals(3, $data->getMaxCountViews());
        $this->assertEquals(0, $data->getTotalCountViews());
        $this->assertNull($data->getUseInfo());
        $this->assertEquals('Google', $data->getAccountName());
        $this->assertEquals('admin', $data->getUserLogin());

        $this->assertEquals(0, self::$repository->getById(3)->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(1, self::$repository->delete(1));
        $this->assertEquals(1, self::$repository->delete(2));
        $this->assertEquals(0, self::$repository->delete(3));

        $this->assertEquals(0, $this->conn->getRowCount('PublicLink'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testAddLinkView()
    {
        $hash = pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635');

        $useInfo = [
            'who' => SELF_IP_ADDRESS,
            'time' => time(),
            'hash' => $hash,
            'agent' => 'Mozilla/Firefox',
            'https' => true
        ];

        $data = new PublicLinkData();
        $data->setHash($hash);
        $data->setUseInfo($useInfo);

        $this->assertEquals(1, self::$repository->addLinkView($data));

        /** @var PublicLinkData $resultData */
        $resultData = self::$repository->getByHash($hash)->getData();

        $this->assertEquals(1, $resultData->getCountViews());
        $this->assertEquals(1, $resultData->getTotalCountViews());
        $this->assertEquals($data->getUseInfo(), $resultData->getUseInfo());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByHash()
    {
        $hash = pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635');

        $result = self::$repository->getByHash($hash);
        /** @var PublicLinkData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(PublicLinkData::class, $data);
        $this->assertEquals(2, $data->getId());
        $this->assertEquals(1, $data->getItemId());
        $this->assertEquals($hash, $data->getHash());
        $this->assertNotEmpty($data->getData());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getTypeId());
        $this->assertEquals(0, $data->isNotify());
        $this->assertEquals(1529228863, $data->getDateAdd());
        $this->assertEquals(1529229463, $data->getDateExpire());
        $this->assertEquals(0, $data->getDateUpdate());
        $this->assertEquals(0, $data->getCountViews());
        $this->assertEquals(3, $data->getMaxCountViews());
        $this->assertEquals(0, $data->getTotalCountViews());
        $this->assertNull($data->getUseInfo());

        $this->assertEquals(0, self::$repository->getByHash('')->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws EnvironmentIsBrokenException
     */
    public function testRefresh()
    {
        $data = new PublicLinkData();
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setDateExpire(time() + 3600);
        $data->setMaxCountViews(6);
        $data->setData('data_new');
        $data->setId(1);

        $this->assertEquals(1, self::$repository->refresh($data));

        /** @var PublicLinkListData $resultData */
        $resultData = self::$repository->getById(1)->getData();

        $this->assertEquals($data->getHash(), $resultData->getHash());
        $this->assertEquals($data->getDateExpire(), $resultData->getDateExpire());
        $this->assertEquals($data->getMaxCountViews(), $resultData->getMaxCountViews());
        $this->assertEquals($data->getData(), $resultData->getData());

        $this->expectException(ConstraintException::class);

        $data->setId(2);

        self::$repository->refresh($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testRefreshNullHash()
    {
        $this->markTestIncomplete('Not working on Travis CI');

        $data = new PublicLinkData();
        $data->setHash(null);
        $data->setDateExpire(time() + 3600);
        $data->setMaxCountViews(6);
        $data->setData('data_new');
        $data->setId(1);

        $this->expectException(ConstraintException::class);

        self::$repository->refresh($data);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByIdBatch()
    {
        $result = self::$repository->getByIdBatch([1, 2, 3]);
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());

        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());

        $this->assertInstanceOf(PublicLinkListData::class, $data[1]);
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(1, $data[1]->getItemId());
        $this->assertEquals(pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635'), $data[1]->getHash());
        $this->assertNotEmpty($data[1]->getData());
        $this->assertEquals(1, $data[1]->getUserId());
        $this->assertEquals(1, $data[1]->getTypeId());
        $this->assertEquals(0, $data[1]->isNotify());
        $this->assertEquals(1529228863, $data[1]->getDateAdd());
        $this->assertEquals(1529229463, $data[1]->getDateExpire());
        $this->assertEquals(0, $data[1]->getDateUpdate());
        $this->assertEquals(0, $data[1]->getCountViews());
        $this->assertEquals(3, $data[1]->getMaxCountViews());
        $this->assertEquals(0, $data[1]->getTotalCountViews());
        $this->assertNull($data[1]->getUseInfo());
        $this->assertEquals('Google', $data[1]->getAccountName());
        $this->assertEquals('admin', $data[1]->getUserLogin());

        $this->assertEquals(0, self::$repository->getByIdBatch([])->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        /** @var PublicLinkListData[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals(2, $result->getNumRows());

        $this->assertInstanceOf(PublicLinkListData::class, $data[0]);
        $this->assertEquals(1, $data[0]->getId());

        $this->assertInstanceOf(PublicLinkListData::class, $data[1]);
        $this->assertEquals(2, $data[1]->getId());
        $this->assertEquals(1, $data[1]->getItemId());
        $this->assertEquals(pack('H*', '313065363937306666653833623531393234356635333433333732626366663433376461623565356134386238326131653238636131356235346635'), $data[1]->getHash());
        $this->assertNotEmpty($data[1]->getData());
        $this->assertEquals(1, $data[1]->getUserId());
        $this->assertEquals(1, $data[1]->getTypeId());
        $this->assertEquals(0, $data[1]->isNotify());
        $this->assertEquals(1529228863, $data[1]->getDateAdd());
        $this->assertEquals(1529229463, $data[1]->getDateExpire());
        $this->assertEquals(0, $data[1]->getDateUpdate());
        $this->assertEquals(0, $data[1]->getCountViews());
        $this->assertEquals(3, $data[1]->getMaxCountViews());
        $this->assertEquals(0, $data[1]->getTotalCountViews());
        $this->assertNull($data[1]->getUseInfo());
        $this->assertEquals('Google', $data[1]->getAccountName());
        $this->assertEquals('admin', $data[1]->getUserLogin());
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
        $data->setId(1);
        $data->setItemId(2);
        $data->setHash(PasswordUtil::generateRandomBytes());
        $data->setData('data');
        $data->setUserId(2);
        $data->setTypeId(1);
        $data->setNotify(0);
        $data->setDateExpire(time() + 3600);
        $data->setDateAdd(time());
        $data->setMaxCountViews(6);

        $this->assertEquals(1, self::$repository->update($data));

        /** @var PublicLinkListData $resultData */
        $resultData = self::$repository->getById(1)->getData();

        $this->assertEquals(1, $resultData->getId());
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

        self::$repository->update($data);
    }
}
