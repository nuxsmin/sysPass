<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Crypt;

use SP\Core\Context\SessionLifecycleHandler;
use SP\Domain\Core\Context\SessionContext;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\SPException;

use function SP\logger;

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
     * @throws CryptException
     */
    public static function getSessionKey(SessionContext $sessionContext): string
    {
        return $sessionContext->getVault()->getData(self::getKey($sessionContext));
    }

    private static function getKey(SessionContext $sessionContext): string
    {
        return self::buildSeed(session_id(), (string)$sessionContext->getSidStartTime());
    }

    private static function buildSeed(string ...$parts): string
    {
        return sha1(implode('', $parts));
    }

    /**
     * Guardar la clave maestra en la sesión
     *
     * @throws CryptException
     */
    public static function saveSessionKey(string $data, SessionContext $sessionContext): void
    {
        $sessionContext->setVault(Vault::factory(new Crypt())->saveData($data, self::getKey($sessionContext)));
    }

    /**
     * Regenerar la clave de sesión
     *
     * @throws CryptException
     * @throws SPException
     */
    public static function reKey(SessionContext $sessionContext): void
    {
        logger(__METHOD__);

        $oldSeed = self::getKey($sessionContext);

        SessionLifecycleHandler::regenerate();

        $newSeed = self::buildSeed(session_id(), (string)$sessionContext->setSidStartTime(time()));

        $sessionContext->setVault($sessionContext->getVault()->reKey($newSeed, $oldSeed));
    }
}
