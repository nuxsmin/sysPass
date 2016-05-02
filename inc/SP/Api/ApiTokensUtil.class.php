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

namespace SP\Api;

use SP\Core\Acl;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ItemSearchData;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ApiTokensUtil con utilidades para la gestión de tokens API
 *
 * @package SP\Api
 */
class ApiTokensUtil
{
    /**
     * Obtener los tokens de la API
     *
     * @param int $tokenId opcional, con el Id del token a consultar
     * @param bool $returnRawData Devolver la consulta tal cual
     * @return array|object con la lista de tokens
     */
    public static function getTokens($tokenId = null, $returnRawData = false)
    {
        $query = 'SELECT authtoken_id,' .
            'authtoken_userId,' .
            'authtoken_actionId, ' .
            'authtoken_token, ' .
            'user_login ' .
            'FROM authTokens ' .
            'LEFT JOIN usrData ON user_id = authtoken_userId ';

        $Data = new QueryData();

        if (!is_null($tokenId)) {
            $query .= "WHERE authtoken_id = :id LIMIT 1";
            $Data->addParam($tokenId, 'id');
        } else {
            $query .= "ORDER BY user_login";
        }

        $Data->setQuery($query);

        if (!$returnRawData) {
            DB::setReturnArray();
        }

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        if (!$returnRawData) {
            foreach ($queryRes as &$token) {
                $token->authtoken_actionId = Acl::getActionName($token->authtoken_actionId);
            }
        }

        return $queryRes;
    }

    /**
     * Obtener los tokens de la API de una búsqueda
     * @param ItemSearchData $SearchData
     * @return array|object con la lista de tokens
     */
    public static function getTokensMgmtSearch(ItemSearchData $SearchData)
    {
        $query = 'SELECT authtoken_id,' .
            'authtoken_userId,' .
            'authtoken_actionId, ' .
            'authtoken_token, ' .
            'user_login ' .
            'FROM authTokens ' .
            'LEFT JOIN usrData ON user_id = authtoken_userId ';

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

        DB::setReturnArray();
        DB::setFullRowCount();

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return array();
        }

        foreach ($queryRes as &$token) {
            $token->authtoken_actionId = Acl::getActionName($token->authtoken_actionId);
        }

        $queryRes['count'] = DB::$lastNumRows;

        return $queryRes;
    }

    /**
     * Devuelver un array de acciones posibles para los tokens
     *
     * @return array
     */
    public static function getTokenActions()
    {
        $actions = array(
            ActionsInterface::ACTION_ACC_SEARCH => Acl::getActionName(ActionsInterface::ACTION_ACC_SEARCH),
            ActionsInterface::ACTION_ACC_VIEW => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW),
            ActionsInterface::ACTION_ACC_VIEW_PASS => Acl::getActionName(ActionsInterface::ACTION_ACC_VIEW_PASS),
            ActionsInterface::ACTION_ACC_DELETE => Acl::getActionName(ActionsInterface::ACTION_ACC_DELETE),
            ActionsInterface::ACTION_CFG_BACKUP => Acl::getActionName(ActionsInterface::ACTION_CFG_BACKUP),
            ActionsInterface::ACTION_CFG_EXPORT => Acl::getActionName(ActionsInterface::ACTION_CFG_EXPORT),
        );

        return $actions;
    }

    /**
     * Obtener el usuario a partir del token
     *
     * @param $token string El token de autorización
     * @return bool|mixed
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function getUserIdForToken($token)
    {
        $query = 'SELECT authtoken_userId FROM authTokens WHERE authtoken_token = :token LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($token, 'token');

        try {
            $queryRes = DB::getResults($Data);
        } catch (SPException $e) {
            throw new SPException(SPException::SP_CRITICAL, _('Error interno'));
        }

        if (DB::$lastNumRows === 0) {
            return false;
        }

        return $queryRes->authtoken_userId;
    }
}