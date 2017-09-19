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

use SP\Core\Acl;
use SP\Core\ActionsInterface;

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
            ActionsInterface::ACTION_ACC_SEARCH => Acl::getActionName(ActionsInterface::ACTION_ACC_SEARCH),
            ActionsInterface::ACTION_ACC_VIEW => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW),
            ActionsInterface::ACTION_ACC_VIEW_PASS => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW_PASS),
            ActionsInterface::ACTION_ACC_DELETE => Acl::getActionName(ActionsInterface::ACTION_ACC_DELETE),
            ActionsInterface::ACTION_ACC_NEW => Acl::getActionName(ActionsInterface::ACTION_ACC_NEW),
            ActionsInterface::ACTION_CFG_BACKUP => Acl::getActionName(ActionsInterface::ACTION_CFG_BACKUP),
            ActionsInterface::ACTION_MGM_CATEGORIES => Acl::getActionName(ActionsInterface::ACTION_MGM_CATEGORIES),
            ActionsInterface::ACTION_MGM_CUSTOMERS => Acl::getActionName(ActionsInterface::ACTION_MGM_CUSTOMERS)
        ];

        return $actions;
    }
}