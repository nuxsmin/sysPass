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
use Exception;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\CustomField\CustomFieldService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use SP\Tests\Services\Account\AccountCryptServiceTest;
use function SP\Tests\setupContext;

/**
 * Class MasterPassServiceTest
 *
 * @package SP\Tests\Services
 */
class MasterPassServiceTest extends DatabaseTestCase
{
    /**
     * @var CustomFieldService
     */
    private static $customFieldService;
    /**
     * @var AccountService
     */
    private static $accountService;
    /**
     * @var MasterPassService
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

        self::$dataset = 'syspass_accountCrypt.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$service = $dic->get(MasterPassService::class);
        self::$accountService = $dic->get(AccountService::class);
        self::$customFieldService = $dic->get(CustomFieldService::class);
    }

    /**
     * @throws CryptoException
     * @throws Exception
     */
    public function testChangeMasterPassword()
    {
        $request = new UpdateMasterPassRequest(AccountCryptServiceTest::CURRENT_MASTERPASS, AccountCryptServiceTest::NEW_MASTERPASS, AccountCryptServiceTest::CURRENT_HASH);

        self::$service->changeMasterPassword($request);

        $this->checckAccounts($request);
        $this->checkAccountsHistory($request);
        $this->checkCustomFields();

        $this->assertTrue(self::$service->checkMasterPassword(AccountCryptServiceTest::NEW_MASTERPASS));

        $this->assertTrue(self::$service->checkUserUpdateMPass(time()));
        $this->assertFalse(self::$service->checkUserUpdateMPass(time() - 10));
    }

    /**
     * @param UpdateMasterPassRequest $request
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function checckAccounts(UpdateMasterPassRequest $request)
    {
        $account = self::$accountService->getPasswordForId(1);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals('&¿\'f!i$XwSwc', $pass);

        $account = self::$accountService->getPasswordForId(2);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals('&¿\'f!i$XwSwc', $pass);
    }

    /**
     * @param UpdateMasterPassRequest $request
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function checkAccountsHistory(UpdateMasterPassRequest $request)
    {
        //  Verify accounts' password history data
        $account = self::$accountService->getPasswordHistoryForId(3);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('_{/uHL\>\'Oj0', $pass);

        $account = self::$accountService->getPasswordHistoryForId(4);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(5);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(6);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(7);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), $request->getNewMasterPass());

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);
    }

    /**
     * @throws CryptoException
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkCustomFields()
    {
        $result = self::$customFieldService->getAllEncrypted();

        $data = Crypt::decrypt($result[0]->getData(), $result[0]->getKey(), AccountCryptServiceTest::NEW_MASTERPASS);

        $this->assertEquals('1234', $data);
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testCheckUserUpdateMPass()
    {
        $this->assertTrue(self::$service->checkUserUpdateMPass(time()));
        $this->assertFalse(self::$service->checkUserUpdateMPass(1528236611 - 10));
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function testCheckMasterPassword()
    {
        $this->assertTrue(self::$service->checkMasterPassword(AccountCryptServiceTest::CURRENT_MASTERPASS));
        $this->assertFalse(self::$service->checkMasterPassword(AccountCryptServiceTest::NEW_MASTERPASS));
    }
}
