<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Stubs;

/**
 * A PDO stub that overrides some unimplementd methods from \Pseudo\Pdo
 */
class Pdo extends \Pseudo\Pdo
{
    /**
     * (PHP 5 &gt;= 5.1.0, PHP 7, PECL pdo &gt;= 0.2.1)<br/>
     * Quotes a string for use in a query.
     *
     * @link https://php.net/manual/en/pdo.quote.php
     *
     * @param  string  $string  <p>
     * The string to be quoted.
     * </p>
     * @param  int  $type  [optional] <p>
     * Provides a data type hint for drivers that have alternate quoting styles.
     * </p>
     *
     * @return string|false a quoted string that is theoretically safe to pass into an
     * SQL statement. Returns <b>FALSE</b> if the driver does not support quoting in
     * this way.
     */
    public function quote($string, $parameter_type = self::PARAM_STR)
    {
        return $string;
    }
}