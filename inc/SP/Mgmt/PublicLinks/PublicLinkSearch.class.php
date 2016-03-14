<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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

namespace SP\Mgmt\PublicLinks;

use SP\Account\AccountUtil;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Mgmt\ItemSearchInterface;
use SP\Storage\DB;
use SP\Mgmt\Users\UserUtil;
use SP\Storage\QueryData;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class PublicLinkUtil con utilidades para la gestión de enlaces
 *
 * @package SP
 */
class PublicLinkSearch extends PublicLinkBase implements ItemSearchInterface
{
    /**
     * @param        $limitCount
     * @param int    $limitStart
     * @param string $search
     * @return mixed
     */
    public function getMgmtSearch($limitCount, $limitStart = 0, $search = '')
    {
        $query = /** @lang SQL */
            'SELECT publicLink_id, publicLink_hash, publicLink_linkData FROM publicLinks LIMIT ?, ?';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setMapClassName('SP\DataModel\PublicLinkListData');
        $Data->addParam($limitStart);
        $Data->addParam($limitCount);

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        $publicLinks = [];
        $publicLinks['count'] = DB::$lastNumRows;

        foreach ($queryRes as $PublicLinkListData) {
            /**
             * @var PublicLinkListData $PublicLinkListData
             * @var PublicLinkData $PublicLinkData
             */
            $PublicLinkData = unserialize($PublicLinkListData->getPublicLinkLinkData());

            if (get_class($PublicLinkData) === '__PHP_Incomplete_Class') {
                $PublicLinkData = Util::castToClass('SP\Mgmt\PublicLinks\PublicLink', $PublicLinkData);
            }

            $PublicLinkListData->setAccountName(AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
            $PublicLinkListData->setUserLogin(UserUtil::getUserLoginById($PublicLinkData->getUserId()));
            $PublicLinkListData->setNotify(($PublicLinkData->isNotify()) ? _('ON') : _('OFF'));
            $PublicLinkListData->setDateAdd(date("Y-m-d H:i", $PublicLinkData->getDateAdd()));
            $PublicLinkListData->setDateExpire(date("Y-m-d H:i", $PublicLinkData->getDateExpire()));
            $PublicLinkListData->setCountViews($PublicLinkData->getCountViews() . '/' . $PublicLinkData->getMaxCountViews());
            $PublicLinkListData->setUseInfo($PublicLinkData->getUseInfo());

            if (empty($search)
                || stripos($PublicLinkListData->getAccountName(), $search) !== false
                || stripos($PublicLinkListData->getUserLogin(), $search) !== false
            ){
                $publicLinks[] = $PublicLinkListData;
            }
        }

        return $publicLinks;
    }
}