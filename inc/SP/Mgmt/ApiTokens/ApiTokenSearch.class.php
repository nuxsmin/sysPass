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
use SP\DataModel\ItemSearchData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class ApiTokenSearch
 *
 * @package SP\Mgmt\ApiTokens
 */
class ApiTokenSearch extends ApiTokenBase implements ItemSearchInterface
{
    /**
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $query = /** @lang SQL */
            'SELECT authtoken_id,
            authtoken_userId,
            authtoken_actionId, 
            authtoken_token,
            CONCAT(user_name, \' (\', user_login, \')\') AS user_login 
            FROM authTokens 
            LEFT JOIN usrData ON user_id = authtoken_userId ';

        $Data = new QueryData();

        if ($SearchData->getSeachString() !== '') {
            $search = '%' . $SearchData->getSeachString() . '%';
            $query .= ' WHERE user_login LIKE ?';

            $Data->addParam($search);
        }

        $query .= ' ORDER BY user_login';
        $query .= ' LIMIT ?, ?';

        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        $Data->setQuery($query);

        DB::setFullRowCount();

        $queryRes = DB::getResultsArray($Data);

        foreach ($queryRes as $token) {
            $token->authtoken_actionId = Acl::getActionName($token->authtoken_actionId);
        }

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}