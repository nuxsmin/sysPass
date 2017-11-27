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

namespace SP\Modules\Web\Controllers\Traits;

use SP\Services\CustomField\CustomFieldService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;

/**
 * Trait ItemTrait
 *
 * @package SP\Modules\Web\Controllers\Traits
 */
trait ItemTrait
{
    /**
     * Obtener la lista de campos personalizados y sus valores
     *
     * @param $moduleId
     * @param $itemId
     * @return array
     * @throws \Defuse\Crypto\Exception\CryptoException
     */
    protected function getCustomFieldsForItem($moduleId, $itemId)
    {
        $customFieldService = new CustomFieldService();
        return $customFieldService->getForModuleById($moduleId, $itemId);
    }

    /**
     * Return user groups list
     *
     * @return array
     */
    protected function getUserGroups()
    {
        $userGroupService = new UserGroupService();
        return $userGroupService->getItemsForSelect();
    }

    /**
     * Return user profiles list
     *
     * @return array
     */
    protected function getUserProfiles()
    {
        $userProfile = new UserProfileService();
        return $userProfile->getItemsForSelect();
    }
}