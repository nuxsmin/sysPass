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

namespace SP\Core\Acl;

use Exception;
use SP\Core\Exceptions\SPException;

/**
 * Class UnauthorizedPageException
 *
 * @package SP\Core\Acl
 */
final class UnauthorizedPageException extends SPException
{
    /**
     * SPException constructor.
     *
     * @param string         $type
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($type, $code = 0, Exception $previous = null)
    {
        parent::__construct(__u('You don\'t have permission to access this page'), $type, __u('Please contact to the administrator'), $code, $previous);
    }

}