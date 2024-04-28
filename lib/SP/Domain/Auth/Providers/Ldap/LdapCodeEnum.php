<?php
/*
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

namespace SP\Domain\Auth\Providers\Ldap;

/**
 * Class LdapCodeEnum
 */
enum LdapCodeEnum: int
{
    case  SUCCESS                   = 0;
    case  OPERATIONS_ERROR          = 1;
    case  AUTH_METHOD_NOT_SUPPORTED = 7;
    case  STRONGER_AUTH_REQUIRED    = 8;
    case  CONFIDENTIALITY_REQUIRED  = 13;
    case  NO_SUCH_OBJECT            = 32;
    case  INVALID_CREDENTIALS       = 49;
    case  FILTER_ERROR              = 87;
}
