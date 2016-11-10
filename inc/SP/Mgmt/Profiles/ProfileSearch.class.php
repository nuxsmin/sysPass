<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Mgmt\Profiles;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\DataModel\ItemSearchData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Checks;

/**
 * Class ProfileSearch
 *
 * @package SP\Mgmt\Profiles
 */
class ProfileSearch extends ProfileBase implements ItemSearchInterface
{
    /**
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $query = /** @lang SQL */
            'SELECT userprofile_id, userprofile_name FROM usrProfiles';

        $Data = new QueryData();

        if ($SearchData->getSeachString() !== '') {
            $query .= ' WHERE userprofile_name LIKE ?';

            if (Checks::demoIsEnabled()) {
                $query .= ' userprofile_name <> "Admin" AND userprofile_name <> "Demo"';
            }

            $search = '%' . $SearchData->getSeachString() . '%';

            $Data->addParam($search);
        } elseif (Checks::demoIsEnabled()) {
            $query .= ' WHERE userprofile_name <> "Admin" AND userprofile_name <> "Demo"';
        }

        $query .= /** @lang SQL */
            ' ORDER BY userprofile_name LIMIT ?, ?';

        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        $Data->setQuery($query);

        DB::setFullRowCount();

        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}