<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Mgmt\ApiTokens;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;

defined('APP_ROOT') || die();

/**
 * Class ApiTokensUtil con utilidades para la gestión de tokens API
 *
 * @package SP\Api
 */
class ApiTokensUtil
{
    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions()
    {
        $actions = [
            ActionsInterface::ACCOUNT_SEARCH => Acl::getActionInfo(ActionsInterface::ACCOUNT_SEARCH),
            ActionsInterface::ACCOUNT_VIEW => Acl::getActionInfo(ActionsInterface::ACCOUNT_VIEW),
            ActionsInterface::ACCOUNT_VIEW_PASS => Acl::getActionInfo(ActionsInterface::ACCOUNT_VIEW_PASS),
            ActionsInterface::ACCOUNT_DELETE => Acl::getActionInfo(ActionsInterface::ACCOUNT_DELETE),
            ActionsInterface::ACCOUNT_CREATE => Acl::getActionInfo(ActionsInterface::ACCOUNT_CREATE),
            ActionsInterface::BACKUP_CONFIG => Acl::getActionInfo(ActionsInterface::BACKUP_CONFIG),
            ActionsInterface::CATEGORY => Acl::getActionInfo(ActionsInterface::CATEGORY),
            ActionsInterface::CLIENT => Acl::getActionInfo(ActionsInterface::CLIENT)
        ];

        return $actions;
    }
}