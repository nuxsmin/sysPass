<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Exceptions\SPException;
use SP\Storage\DB;
use SP\Storage\QueryData;

/**
 * Class AccountFavorites para la gestión de las cuentas favoritas de los usuarios
 *
 * @package SP\Account
 */
class AccountFavorites
{
    /**
     * Obtener un array con los Ids de cuentas favoritas
     *
     * @param $userId int El Id de usuario
     * @return array
     */
    public static function getFavorites($userId)
    {
        $query = 'SELECT accfavorite_accountId FROM accFavorites WHERE accfavorite_userId = :userId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($userId, 'userId');

        DB::setReturnArray();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false){
            return array();
        }

        $favorites = array();

        foreach($queryRes as $favorite){
            $favorites[] = $favorite->accfavorite_accountId;
        }

        return $favorites;
    }

    /**
     * Añadir una cuenta a la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function addFavorite($accountId, $userId)
    {
        $query = 'INSERT INTO accFavorites SET accfavorite_accountId = :accountId, accfavorite_userId = :userId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'accountId');
        $Data->addParam($userId, 'userId');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al añadir favorito'));
        }
    }

    /**
     * Eliminar una cuenta de la lista de favoritos
     *
     * @param $accountId int El Id de la cuenta
     * @param $userId    int El Id del usuario
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function deleteFavorite($accountId, $userId)
    {
        $query = 'DELETE FROM accFavorites WHERE accfavorite_accountId = :accountId AND accfavorite_userId = :userId';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($accountId, 'accountId');
        $Data->addParam($userId, 'userId');

        if (DB::getQuery($Data) === false) {
            throw new SPException(SPException::SP_ERROR, _('Error al eliminar favorito'));
        }
    }
}