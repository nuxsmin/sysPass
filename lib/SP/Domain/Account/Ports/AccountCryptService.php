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

namespace SP\Domain\Account\Ports;

use SP\Domain\Account\Dtos\EncryptedPassword;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;

/**
 * Class AccountCryptService
 *
 * @package SP\Domain\Account\Services
 */
interface AccountCryptService
{
    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void;

    /**
     * Actualiza las claves de todas las cuentas con la nueva clave maestra.
     *
     * @throws ServiceException
     */
    public function updateHistoryMasterPassword(UpdateMasterPassRequest $updateMasterPassRequest): void;

    /**
     * Devolver los datos de la clave encriptados
     *
     * @throws ServiceException
     */
    public function getPasswordEncrypted(string $pass, ?string $masterPass = null): EncryptedPassword;
}
