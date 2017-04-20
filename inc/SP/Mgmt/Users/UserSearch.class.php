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

namespace SP\Mgmt\Users;

use SP\Core\Session;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class UserSearch
 *
 * @package SP\Mgmt\Users
 */
class UserSearch extends UserBase implements ItemSearchInterface
{
    /**
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('user_id,
            user_name, 
            user_login,
            userprofile_name,
            usergroup_name,
            BIN(user_isAdminApp) AS user_isAdminApp,
            BIN(user_isAdminAcc) AS user_isAdminAcc,
            BIN(user_isLdap) AS user_isLdap,
            BIN(user_isDisabled) AS user_isDisabled,
            BIN(user_isChangePass) AS user_isChangePass');
        $Data->setFrom('usrData LEFT JOIN usrProfiles ON user_profileId = userprofile_id LEFT JOIN usrGroups ON usrData.user_groupId = usergroup_id');
        $Data->setOrder('user_name');

        if ($SearchData->getSeachString() !== '') {
            if (Session::getUserData()->isUserIsAdminApp()) {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ?');
            } else {
                $Data->setWhere('user_name LIKE ? OR user_login LIKE ? AND user_isAdminApp = 0');
            }

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
        } elseif (!Session::getUserData()->isUserIsAdminApp()) {
            $Data->setWhere('user_isAdminApp = 0');
        }

        $Data->setLimit('?, ?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}