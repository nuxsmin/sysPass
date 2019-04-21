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

namespace SP\Tests\Services\Account;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountCryptService;
use SP\Services\Account\AccountService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountCryptServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountCryptServiceTest extends DatabaseTestCase
{
    const CURRENT_MASTERPASS = '12345678900';
    const NEW_MASTERPASS = '00123456789';
    const CURRENT_HASH = '$2y$10$xtsuN2PUvgSH/0mrfBlsbOActVgCjYcqDqC6L3T9QraNxZC4RXGYa';
    /**
     * @var AccountService
     */
    private static $accountService;
    /**
     * @var AccountCryptService
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

        // Inicializar el servicio
        self::$service = $dic->get(AccountCryptService::class);
        self::$accountService = $dic->get(AccountService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws ServiceException
     * @throws CryptoException
     * @throws NoSuchItemException
     */
    public function testUpdateMasterPassword()
    {
        $request = new UpdateMasterPassRequest(self::CURRENT_MASTERPASS, self::NEW_MASTERPASS, self::CURRENT_HASH);

        self::$service->updateMasterPassword($request);

        $account = self::$accountService->getPasswordForId(1);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals('&¿\'f!i$XwSwc', $pass);

        $account = self::$accountService->getPasswordForId(2);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals('&¿\'f!i$XwSwc', $pass);

        $request = new UpdateMasterPassRequest('12345', self::NEW_MASTERPASS, self::CURRENT_HASH);

        self::$service->updateMasterPassword($request);

        $account = self::$accountService->getPasswordForId(1);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals('&¿\'f!i$XwSwc', $pass);
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testUpdateHistoryMasterPassword()
    {
        $request = new UpdateMasterPassRequest(self::CURRENT_MASTERPASS, self::NEW_MASTERPASS, self::CURRENT_HASH);

        self::$service->updateHistoryMasterPassword($request);

        $account = self::$accountService->getPasswordHistoryForId(3);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('_{/uHL\>\'Oj0', $pass);

        $account = self::$accountService->getPasswordHistoryForId(4);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(5);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(6);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);

        $account = self::$accountService->getPasswordHistoryForId(7);
        $pass = Crypt::decrypt($account->getPass(), $account->getKey(), self::NEW_MASTERPASS);

        $this->assertEquals($request->getHash(), $account->getMPassHash());
        $this->assertEquals('-{?^··\mjC<c', $pass);
    }
}
