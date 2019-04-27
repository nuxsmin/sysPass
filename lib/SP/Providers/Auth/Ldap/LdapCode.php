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

namespace SP\Providers\Auth\Ldap;


/**
 * Interface LdapCode
 *
 * @package SP\Providers\Auth\Ldap
 */
interface LdapCode
{
    const SUCCESS = 0;
    const OPERATIONS_ERROR = 1;
    const AUTH_METHOD_NOT_SUPPORTED = 7;
    const STRONGER_AUTH_REQUIRED = 8;
    const CONFIDENTIALITY_REQUIRED = 13;
    const NO_SUCH_OBJECT = 32;
    const INVALID_CREDENTIALS = 49;
    const FILTER_ERROR = 87;
}