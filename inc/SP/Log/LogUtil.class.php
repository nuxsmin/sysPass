<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Log;

/**
 * Class LogUtil
 *
 * @package SP\Log
 */
class LogUtil
{
    /**
     * Registrar que una extensión no ha sido cargada
     *
     * @param string $extension La extensión no cargada
     * @param string $source    El origen del error
     * @return Log
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function extensionNotLoaded($extension, $source = __FUNCTION__)
    {
        $msg = sprintf(__('Extensión \'%s\' no cargada'), $extension);

        return Log::writeNewLog($source, $msg, Log::ERROR);
    }
}