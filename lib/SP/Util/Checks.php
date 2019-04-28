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

namespace SP\Util;

/**
 * Class Checks utilidades de comprobación
 *
 * @package SP\Util
 */
final class Checks
{
    /**
     * Comprobar si sysPass se ejecuta en W$indows.
     *
     * @return bool
     */
    public static function checkIsWindows()
    {
        return strpos(PHP_OS, 'WIN') === 0;
    }

    /**
     * Comprobar la versión de PHP.
     *
     * @return bool
     */
    public static function checkPhpVersion()
    {
        return version_compare(PHP_VERSION, '7.0', '>=')
            && version_compare(PHP_VERSION, '7.4', '<');
    }
}
