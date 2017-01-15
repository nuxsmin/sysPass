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

namespace SP\Mgmt\PublicLinks;

use SP\Account\AccountUtil;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkListData;
use SP\Mgmt\ItemSearchInterface;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Class PublicLinkUtil con utilidades para la gestión de enlaces
 *
 * @package SP
 */
class PublicLinkSearch extends PublicLinkBase implements ItemSearchInterface
{
    /**
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function getMgmtSearch(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setSelect('publicLink_id, publicLink_hash, publicLink_linkData');
        $Data->setFrom('publicLinks');
        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DB::setFullRowCount();

        /** @var PublicLinkListData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $publicLinks = [];
        $publicLinks['count'] = $Data->getQueryNumRows();

        foreach ($queryRes as $PublicLinkListData) {
            $PublicLinkData = Util::castToClass($this->getDataModel(), $PublicLinkListData->getPublicLinkLinkData());

            $PublicLinkListData->setAccountName(AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
            $PublicLinkListData->setUserLogin(UserUtil::getUserLoginById($PublicLinkData->getUserId()));
            $PublicLinkListData->setNotify($PublicLinkData->isNotify() ? __('ON') : __('OFF'));
            $PublicLinkListData->setDateAdd(date('Y-m-d H:i', $PublicLinkData->getDateAdd()));
            $PublicLinkListData->setDateExpire(date('Y-m-d H:i', $PublicLinkData->getDateExpire()));
            $PublicLinkListData->setCountViews($PublicLinkData->getCountViews() . '/' . $PublicLinkData->getMaxCountViews());
            $PublicLinkListData->setUseInfo($PublicLinkData->getUseInfo());

            if ($SearchData->getSeachString() === ''
                || stripos($PublicLinkListData->getAccountName(), $SearchData->getSeachString()) !== false
                || stripos($PublicLinkListData->getUserLogin(), $SearchData->getSeachString()) !== false
            ){
                $publicLinks[] = $PublicLinkListData;
            }
        }

        return $publicLinks;
    }
}