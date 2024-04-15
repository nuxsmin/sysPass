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

namespace SP\Domain\Auth\Services;

/**
 * Class LoginStatus
 */
enum LoginStatus: int
{
    case OK                    = 0;
    case INVALID_LOGIN         = 1;
    case INVALID_MASTER_PASS   = 2;
    case USER_DISABLED         = 3;
    case OLD_PASS_REQUIRED     = 5;
    case MAX_ATTEMPTS_EXCEEDED = 6;
    case PASS_RESET_REQUIRED   = 7;
    case PASS                  = 100;
}
