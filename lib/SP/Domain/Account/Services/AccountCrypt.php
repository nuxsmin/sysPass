<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Domain\Account\Services;

use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Account\Ports\AccountCryptService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Core\Exceptions\CryptException;

use function SP\__u;

/**
 * Class AccountCrypt
 */
final class AccountCrypt extends Service implements AccountCryptService
{
    public function __construct(
        ConfigFileService $config,
        EventDispatcherInterface $eventDispatcher,
        Context $context,
        private readonly CryptInterface $crypt,
        private readonly MasterKeyStore $masterKeyStore
    ) {
        parent::__construct($config, $eventDispatcher, $context);
    }

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws ServiceException
     */
    public function getPasswordEncrypted(string $pass, ?string $masterPass = null): EncryptedPassword
    {
        try {
            if ($masterPass === null) {
                $masterPass = $this->masterKeyStore->get();
            }

            if (empty($masterPass)) {
                throw new ServiceException(__u('Master password not set'));
            }

            $key = $this->crypt->makeSecuredKey($masterPass);

            $encryptedPassword = new EncryptedPassword(
                $this->crypt->encrypt($pass, $key, $masterPass),
                $key
            );

            if (strlen($encryptedPassword->getPass()) > 1000 || strlen($encryptedPassword->getKey()) > 1000) {
                throw new ServiceException(__u('Internal error'));
            }

            return $encryptedPassword;
        } catch (CryptException $e) {
            throw ServiceException::error(__u('Internal error'), null, $e->getCode(), $e);
        }
    }
}
