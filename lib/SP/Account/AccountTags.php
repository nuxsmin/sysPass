<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Account;

use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\Storage\DbWrapper;
use SP\Storage\QueryData;

defined('APP_ROOT') || die();

/**
 * Class AccountTags
 *
 * @package SP\Account
 */
class AccountTags
{
    /**
     * Devolver las etiquetas de una cuenta
     *
     * @param AccountData $accountData
     * @return array
     */
    public static function getTags(AccountData $accountData)
    {
        $query = /** @lang SQL */
            'SELECT T.id, T.name
                FROM AccountToTag AT
                INNER JOIN Tag T ON AT.tagId = T.id
                WHERE AT.accountId = ?
                ORDER BY T.name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setUseKeyPair(true);
        $Data->addParam($accountData->getId());

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Devolver las etiquetas de una cuenta por id
     *
     * @param int $id Id de la cuenta
     * @return array
     */
    public static function getTagsForId($id)
    {
        $query = /** @lang SQL */
            'SELECT T.id, T.name
                FROM AccountToTag AT
                INNER JOIN Tag T ON AccountToTag.tagId = T.id
                WHERE AT.accountId = ?
                ORDER BY T.name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setUseKeyPair(true);
        $Data->addParam($id);

        return DbWrapper::getResultsArray($Data);
    }

    /**
     * Actualizar las etiquetas de una cuenta
     *
     * @param AccountExtData $accountData
     * @param bool           $isUpdate
     * @return bool
     * @throws SPException
     */
    public function addTags(AccountExtData $accountData, $isUpdate = false)
    {
        if ($isUpdate === true) {
            $this->deleteTags($accountData);
        }

        $numTags = count($accountData->getTags());

        if ($numTags === 0) {
            return true;
        }

        $query = /** @lang SQL */
            'INSERT INTO AccountToTag (accountId, tagId) VALUES ' . implode(',', array_fill(0, $numTags, '(?,?)'));

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al añadir las etiquetas de la cuenta', false));

        foreach ($accountData->getTags() as $tag) {
            $Data->addParam($accountData->getId());
            $Data->addParam($tag);
        }

        return DbWrapper::getQuery($Data);
    }

    /**
     * Eliminar las etiquetas de una cuenta
     *
     * @param AccountExtData $accountData
     * @return bool
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function deleteTags(AccountExtData $accountData)
    {
        $numTags = count($accountData->getTags());

        $Data = new QueryData();

        if ($numTags > 0) {
            $params = implode(',', array_fill(0, $numTags, '?'));

            $query = /** @lang SQL */
                'DELETE FROM AccountToTag WHERE accountId = ? AND tagId NOT IN (' . $params . ')';

            $Data->setParams(array_merge((array)$accountData->getId(), $accountData->getTags()));
        } else {
            $query = /** @lang SQL */
                'DELETE FROM AccountToTag WHERE accountId = ?';

            $Data->addParam($accountData->getId());
        }

        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al eliminar las etiquetas de la cuenta', false));

        return DbWrapper::getQuery($Data);
    }
}