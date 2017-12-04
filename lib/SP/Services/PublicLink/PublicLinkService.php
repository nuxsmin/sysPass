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

namespace SP\Services\PublicLink;

use SP\Account\AccountUtil;
use SP\DataModel\ItemSearchData;
use SP\DataModel\PublicLinkBaseData;
use SP\DataModel\PublicLinkListData;
use SP\Mgmt\Users\UserUtil;
use SP\Services\Service;
use SP\Services\ServiceItemInterface;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class PublicLinkService
 *
 * @package SP\Services\PublicLink
 */
class PublicLinkService extends Service implements ServiceItemInterface
{

    /**
     * Deletes an item
     *
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        // TODO: Implement delete() method.
    }

    /**
     * Returns the item for given id
     *
     * @param int $id
     * @return mixed
     */
    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    /**
     * Returns all the items
     *
     * @return mixed
     */
    public function getAll()
    {
        // TODO: Implement getAll() method.
    }

    /**
     * Returns all the items for given ids
     *
     * @param array $ids
     * @return array
     */
    public function getByIdBatch(array $ids)
    {
        // TODO: Implement getByIdBatch() method.
    }

    /**
     * Deletes all the items for given ids
     *
     * @param array $ids
     * @return $this
     */
    public function deleteByIdBatch(array $ids)
    {
        // TODO: Implement deleteByIdBatch() method.
    }

    /**
     * Checks whether the item is in use or not
     *
     * @param $id int
     * @return bool
     */
    public function checkInUse($id)
    {
        // TODO: Implement checkInUse() method.
    }

    /**
     * Searches for items by a given filter
     *
     * @param ItemSearchData $SearchData
     * @return mixed
     */
    public function search(ItemSearchData $SearchData)
    {
        $Data = new QueryData();
        $Data->setMapClassName(PublicLinkListData::class);
        $Data->setSelect('publicLink_id, publicLink_hash, publicLink_linkData');
        $Data->setFrom('publicLinks');
        $Data->setLimit('?,?');
        $Data->addParam($SearchData->getLimitStart());
        $Data->addParam($SearchData->getLimitCount());

        DbWrapper::setFullRowCount();

        /** @var PublicLinkListData[] $queryRes */
        $queryRes = DbWrapper::getResultsArray($Data);

        $publicLinks = [];
        $publicLinks['count'] = $Data->getQueryNumRows();

        foreach ($queryRes as $PublicLinkListData) {
            $PublicLinkData = Util::castToClass(PublicLinkBaseData::class, $PublicLinkListData->getPublicLinkLinkData());

            $PublicLinkListData->setAccountName(AccountUtil::getAccountNameById($PublicLinkData->getItemId()));
            $PublicLinkListData->setUserLogin(UserUtil::getUserLoginById($PublicLinkData->getUserId()));
            $PublicLinkListData->setNotify(__($PublicLinkData->isNotify() ? 'ON' : 'OFF'));
            $PublicLinkListData->setDateAdd(date('Y-m-d H:i', $PublicLinkData->getDateAdd()));
            $PublicLinkListData->setDateExpire(date('Y-m-d H:i', $PublicLinkData->getDateExpire()));
            $PublicLinkListData->setCountViews($PublicLinkData->getCountViews() . '/' . $PublicLinkData->getMaxCountViews());
            $PublicLinkListData->setUseInfo($PublicLinkData->getUseInfo());

            if ($SearchData->getSeachString() === ''
                || stripos($PublicLinkListData->getAccountName(), $SearchData->getSeachString()) !== false
                || stripos($PublicLinkListData->getUserLogin(), $SearchData->getSeachString()) !== false
            ) {
                $publicLinks[] = $PublicLinkListData;
            }
        }

        return $publicLinks;
    }

    /**
     * Creates an item
     *
     * @param mixed $itemData
     * @return mixed
     */
    public function create($itemData)
    {
        // TODO: Implement create() method.
    }

    /**
     * Updates an item
     *
     * @param mixed $itemData
     * @return mixed
     */
    public function update($itemData)
    {
        // TODO: Implement update() method.
    }

    /**
     * Checks whether the item is duplicated on updating
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnUpdate($itemData)
    {
        // TODO: Implement checkDuplicatedOnUpdate() method.
    }

    /**
     * Checks whether the item is duplicated on adding
     *
     * @param mixed $itemData
     * @return bool
     */
    public function checkDuplicatedOnAdd($itemData)
    {
        // TODO: Implement checkDuplicatedOnAdd() method.
    }
}