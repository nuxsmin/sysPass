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

namespace SP\Tests\Services\CustomField;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Crypt\Crypt;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\CustomField\CustomFieldCryptServiceInterface;
use SP\Domain\CustomField\Services\CustomFieldCryptService;
use SP\Domain\CustomField\Services\CustomFieldService;
use SP\Tests\DatabaseTestCase;
use SP\Tests\Services\Account\AccountCryptServiceTest;
use function SP\Tests\setupContext;

/**
 * Class CustomFieldCryptServiceTest
 *
 * @package SP\Tests\Services\CustomField
 */
class CustomFieldCryptServiceTest extends DatabaseTestCase
{
    /**
     * @var \SP\Domain\CustomField\CustomFieldServiceInterface
     */
    private static $customFieldService;
    /**
     * @var CustomFieldCryptServiceInterface
     */
    private static $service;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass(): void
    {
        $dic = setupContext();

        self::$loadFixtures = true;

        // Inicializar el repositorio
        self::$service = $dic->get(CustomFieldCryptService::class);
        self::$customFieldService = $dic->get(CustomFieldService::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws CryptoException
     */
    public function testUpdateMasterPassword()
    {
        $request = new UpdateMasterPassRequest(AccountCryptServiceTest::CURRENT_MASTERPASS, AccountCryptServiceTest::NEW_MASTERPASS, AccountCryptServiceTest::CURRENT_HASH);

        self::$service->updateMasterPassword($request);

        $result = self::$customFieldService->getAllEncrypted();

        $data = Crypt::decrypt($result[0]->getData(), $result[0]->getKey(), AccountCryptServiceTest::NEW_MASTERPASS);

        $this->assertEquals('1234', $data);
    }
}
