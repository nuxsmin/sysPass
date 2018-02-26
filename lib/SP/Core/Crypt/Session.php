<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

namespace SP\Core\Crypt;

use SP\Core\SessionFactory as CoreSession;

/**
 * Class Session
 *
 * @package SP\Core\Crypt
 */
class Session
{
    /**
     * Devolver la clave maestra de la sesión
     *
     * @return string
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @todo Use session from DI
     */
    public static function getSessionKey()
    {
        return CoreSession::getVault()->getData();
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @param $data
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @todo Use session from DI
     */
    public static function saveSessionKey($data)
    {
        CoreSession::setVault((new Vault())->saveData($data));
    }

    /**
     * Regenerar la clave de sesión
     *
     * @param \SP\Core\Session\Session $session
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    public static function reKey(\SP\Core\Session\Session $session)
    {
        debugLog(__METHOD__);

        $oldSeed = session_id() . $session->getSidStartTime();

        session_regenerate_id(true);

        $newSeed = session_id() . $session->setSidStartTime(time());

        CoreSession::setVault(CoreSession::getVault()->reKey($newSeed, $oldSeed));
    }
}