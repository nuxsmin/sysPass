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

namespace SP\Account;

use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountData;
use SP\DataModel\AccountExtData;
use SP\Storage\DB;
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
            'SELECT tag_id, tag_name
                FROM accTags
                JOIN tags ON tag_id = acctag_tagId
                WHERE acctag_accountId = ?
                ORDER BY tag_name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setUseKeyPair(true);
        $Data->addParam($accountData->getAccountId());

        return DB::getResultsArray($Data);
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
            'INSERT INTO accTags (acctag_accountId, acctag_tagId) VALUES ' . implode(',', array_fill(0, $numTags, '(?,?)'));

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al añadir las etiquetas de la cuenta', false));

        foreach ($accountData->getTags() as $tag) {
            $Data->addParam($accountData->getAccountId());
            $Data->addParam($tag);
        }

        return DB::getQuery($Data);
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
                'DELETE FROM accTags WHERE acctag_accountId = ? AND acctag_tagId NOT IN (' . $params . ')';

            $Data->setParams(array_merge((array)$accountData->getAccountId(), $accountData->getTags()));
        } else {
            $query = /** @lang SQL */
                'DELETE FROM accTags WHERE acctag_accountId = ?';

            $Data->addParam($accountData->getAccountId());
        }

        $Data->setQuery($query);
        $Data->setOnErrorMessage(__('Error al eliminar las etiquetas de la cuenta', false));

        return DB::getQuery($Data);
    }
}