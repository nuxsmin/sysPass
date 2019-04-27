<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use SP\Core\Context\SessionContext;

/**
 * Class Session
 *
 * @package SP\Core\Crypt
 */
final class Session
{
    /**
     * Devolver la clave maestra de la sesión
     *
     * @param SessionContext $sessionContext
     *
     * @return string
     * @throws CryptoException
     */
    public static function getSessionKey(SessionContext $sessionContext)
    {
        return $sessionContext->getVault()->getData(self::getKey($sessionContext));
    }

    /**
     * @param SessionContext $sessionContext
     *
     * @return string
     */
    private static function getKey(SessionContext $sessionContext)
    {
        return session_id() . $sessionContext->getSidStartTime();
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @param                $data
     * @param SessionContext $sessionContext
     *
     * @throws CryptoException
     */
    public static function saveSessionKey($data, SessionContext $sessionContext)
    {
        $sessionContext->setVault((new Vault())->saveData($data, self::getKey($sessionContext)));
    }

    /**
     * Regenerar la clave de sesión
     *
     * @param SessionContext $sessionContext
     *
     * @throws CryptoException
     */
    public static function reKey(SessionContext $sessionContext)
    {
        logger(__METHOD__);

        $oldSeed = session_id() . $sessionContext->getSidStartTime();

        session_regenerate_id(true);

        $newSeed = session_id() . $sessionContext->setSidStartTime(time());

        $sessionContext->setVault($sessionContext->getVault()->reKey($newSeed, $oldSeed));
    }
}