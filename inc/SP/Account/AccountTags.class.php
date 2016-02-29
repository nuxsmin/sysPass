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

namespace SP\Account;

use SP\Core\SPException;
use SP\DataModel\AccountData;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

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
        $query = 'SELECT tag_id, tag_name
                FROM accTags
                JOIN tags ON tag_id = acctag_tagId
                WHERE acctag_accountId = :id
                ORDER BY tag_name';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountData->getAccountId(), 'id');

        DB::setReturnArray();

        $tags = [];

        foreach (DB::getResults($Data) as $tag) {
            $tags[$tag->tag_id] = $tag->tag_name;
        }

        return $tags;
    }

    /**
     * Actualizar las etiquetas de una cuenta
     *
     * @param AccountData $accountData
     * @return bool
     * @throws SPException
     */
    public function addTags(AccountData $accountData)
    {
        if (!$this->deleteTags($accountData)) {
            throw new SPException(SPException::SP_WARNING, _('Error al eliminar las etiquetas de la cuenta'));
        }

        if (count($accountData->getTags()) === 0){
            return true;
        }

        $values = [];

        $Data = new QueryData();

        foreach ($accountData->getTags() as $tag) {
            $Data->addParam($accountData->getAccountId());
            $Data->addParam($tag);

            $values[] = '(?, ?)';
        }

        $query = 'INSERT INTO accTags (acctag_accountId, acctag_tagId) VALUES ' . implode(',', $values);

        $Data->setQuery($query);

        return DB::getQuery($Data);
    }

    /**
     * Eliminar las etiquetas de una cuenta
     *
     * @param AccountData $accountData
     * @return bool
     */
    public function deleteTags(AccountData $accountData)
    {
        $query = 'DELETE FROM accTags WHERE acctag_accountId = :id';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountData->getAccountId(), 'id');

        return DB::getQuery($Data);
    }
}