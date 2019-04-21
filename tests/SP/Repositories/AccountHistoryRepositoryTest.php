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
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountHistoryCreateDto;
use SP\DataModel\ItemSearchData;
use SP\Repositories\Account\AccountHistoryRepository;
use SP\Services\Account\AccountPasswordRequest;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Util\PasswordUtil;
use function SP\Tests\setupContext;

/**
 * Class AccountHistoryRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class AccountHistoryRepositoryTest extends DatabaseTestCase
{
    /**
     * @var AccountHistoryRepository
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

        self::$dataset = 'syspass_accountHistory.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(AccountHistoryRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDelete()
    {
        $this->assertEquals(0, self::$repository->delete(1));
        $this->assertEquals(1, self::$repository->delete(3));
        $this->assertEquals(1, self::$repository->delete(4));

        $this->assertEquals(3, $this->conn->getRowCount('AccountHistory'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();
        $data = $result->getDataAsArray();

        $this->assertEquals(5, $result->getNumRows());
        $this->assertCount(5, $data);
        $this->assertEquals(7, $data[0]->id);
        $this->assertEquals('2018-06-13 20:14:23', $data[0]->dateEdit);
        $this->assertEquals('2018-06-05 22:11:40', $data[0]->dateAdd);
        $this->assertEquals('admin', $data[0]->userAdd);
        $this->assertEquals('admin', $data[0]->userEdit);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAccountsPassData()
    {
        $result = self::$repository->getAccountsPassData();
        $data = $result->getDataAsArray();

        $this->assertEquals(5, $result->getNumRows());
        $this->assertCount(5, $data);
        $this->assertEquals(3, $data[0]->id);
        $this->assertEquals('Google', $data[0]->name);
        $this->assertEquals(pack('H*', '646566353032303064396362643366376662646536326637663732663861383732623430613839386131643134333933663662623033316664343362366461643762626564643634386437363964346634616234386638336636653236396166623734636261383134313363626162326461393733343934613231653934666331616664633637313732316562356666396562646132613665313937626233333563613632383830393934333863643731333230383132316430366433303838'), $data[0]->pass);
        $this->assertEquals(pack('H*', '6465663130303030646566353032303032636635623034396437656539356531653838663166613438643061616132663133613163663766346238316165663837326134373665316461653661353865316666626438346130383166303062633138646136373265653935643234626564336565303063333262646262303433336633356534323263616337613238363532336233313666316137333462616337343839346631333632643863376430373861373862396135633064396239653061353537626562666336636566623766363166376330393734356461623536373762303436313865343936383434663932666364303634316330303935636239363938336361336631363161623134663339643536636233653938333833613062396464356365383736333334376364363933313563306436343362623937366139383831376632346431303364316533353133306262393862353034353262346334663934663162323531383632356530653331346438343430323362666334306264616265376437386238663632326535353338636537663431626261616461613138646333333662623762636565333030656565333734616537356365303131363731323239383132383964346634383661376635303136303835336138663335653366393230383632386162373332343335633037656432616234'), $data[0]->key);
        $this->assertEquals(pack('H*', '24327924313024787473754E325055766753482F306D7266426C73624F4163745667436A596371447143364C3354395172614E785A43345258475961'), $data[0]->mPassHash);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetHistoryForAccount()
    {
        $result = self::$repository->getHistoryForAccount(2);
        $data = $result->getDataAsArray();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertCount(1, $data);
        $this->assertEquals(3, $data[0]->id);
        $this->assertEquals('2018-06-06 22:20:29', $data[0]->dateEdit);
        $this->assertEquals('2018-06-05 22:49:34', $data[0]->dateAdd);
        $this->assertEquals('admin', $data[0]->userAdd);
        $this->assertEquals('admin', $data[0]->userEdit);

        $result = self::$repository->getHistoryForAccount(1);
        $this->assertEquals(4, $result->getNumRows());

        $result = self::$repository->getHistoryForAccount(10);
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws EnvironmentIsBrokenException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $result = self::$repository->create(new AccountHistoryCreateDto(2, true, false, PasswordUtil::generateRandomBytes()));
        $this->assertEquals(8, $result);

        $result = self::$repository->create(new AccountHistoryCreateDto(2, true, true, PasswordUtil::generateRandomBytes()));
        $this->assertEquals(9, $result);

        $result = self::$repository->create(new AccountHistoryCreateDto(10, true, false, PasswordUtil::generateRandomBytes()));
        $this->assertEquals(0, $result);

        $this->assertEquals(7, $this->conn->getRowCount('AccountHistory'));
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
        $data = $result->getDataAsArray();

        $this->assertEquals(5, $result->getNumRows());
        $this->assertCount(5, $data);
        $this->assertEquals(7, $data[0]->id);

        $itemSearchData->setSeachString('test');
        $result = self::$repository->search($itemSearchData);

        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByIdBatch()
    {
        $this->assertEquals(3, self::$repository->deleteByIdBatch([1, 3, 4, 5]));
        $this->assertEquals(0, self::$repository->deleteByIdBatch([]));

        $this->assertEquals(2, $this->conn->getRowCount('AccountHistory'));
    }

    /**
     * @throws SPException
     */
    public function testGetById()
    {
        $result = self::$repository->getById(3);
        /** @var AccountHistoryData $data */
        $data = $result->getData();

        $this->assertEquals(1, $result->getNumRows());
        $this->assertInstanceOf(AccountHistoryData::class, $data);
        $this->assertEquals(3, $data->getId());
        $this->assertEquals('2018-06-06 22:20:29', $data->getDateEdit());
        $this->assertEquals('2018-06-05 22:49:34', $data->getDateAdd());
        $this->assertEquals(1, $data->getUserId());
        $this->assertEquals(1, $data->getUserEditId());

        $result = self::$repository->getById(1);
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws EnvironmentIsBrokenException
     * @throws SPException
     */
    public function testUpdatePassword()
    {
        $request = new AccountPasswordRequest();
        $request->id = 3;
        $request->pass = PasswordUtil::generateRandomBytes();
        $request->key = PasswordUtil::generateRandomBytes();
        $request->hash = PasswordUtil::generateRandomBytes();

        $this->assertEquals(1, self::$repository->updatePassword($request));

        $result = self::$repository->getById(3);
        /** @var AccountHistoryData $data */
        $data = $result->getData();

        $this->assertEquals($request->pass, $data->getPass());
        $this->assertEquals($request->key, $data->getKey());

        $request->id = 10;
        $this->assertEquals(0, self::$repository->updatePassword($request));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByAccountIdBatch()
    {
        $this->assertEquals(4, self::$repository->deleteByAccountIdBatch([1, 3]));

        $this->assertEquals(0, self::$repository->deleteByAccountIdBatch([10]));

        $this->assertEquals(0, self::$repository->deleteByAccountIdBatch([]));

        $this->assertEquals(1, $this->conn->getRowCount('AccountHistory'));
    }
}
