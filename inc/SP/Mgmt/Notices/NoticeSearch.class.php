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

namespace SP\Mgmt\Notices;

defined('APP_ROOT') || die();

use SP\Core\Session;
use SP\DataModel\ItemSearchData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class NoticeSearch
 *
 * @package SP\Mgmt\Categories
 */
class NoticeSearch extends NoticeBase implements ItemSearchInterface
{
    /**
     * Obtiene el listado de categorías mediante una búsqueda
     *
     * @param ItemSearchData $SearchData
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('notice_id, notice_type, notice_component, notice_description, FROM_UNIXTIME(notice_date) AS notice_date, notice_checked, notice_userId, notice_sticky, notice_onlyAdmin');
        $Data->setFrom('notices');
        $Data->setOrder('notice_date DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('(notice_type LIKE ? OR notice_component LIKE ? OR notice_description LIKE ?) AND notice_onlyAdmin = 0');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }

    /**
     * Obtiene el listado de categorías mediante una búsqueda
     *
     * @param ItemSearchData $SearchData
     * @return array con el id de categoria como clave y en nombre como valor
     */
    public function getMgmtSearchUser(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setSelect('notice_id, notice_type, notice_component, notice_description, FROM_UNIXTIME(notice_date) AS notice_date, notice_checked, notice_userId, notice_sticky, notice_onlyAdmin');
        $Data->setFrom('notices');
        $Data->setOrder('notice_date DESC');

        if ($SearchData->getSeachString() !== '') {
            $Data->setWhere('(notice_type LIKE ? OR notice_component LIKE ? OR notice_description LIKE ?) AND notice_userId = ? AND notice_onlyAdmin = 0');

            $search = '%' . $SearchData->getSeachString() . '%';
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam($search);
            $Data->addParam(Session::getUserData()->getUserId());
        }

        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        /** @var array $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $queryRes['count'] = $Data->getQueryNumRows();

        return $queryRes;
    }
}