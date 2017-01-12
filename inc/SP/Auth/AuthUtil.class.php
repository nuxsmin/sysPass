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

namespace SP\Auth;

use SP\Core\Init;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Html\Html;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Users\UserPassRecover;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class AuthUtil
 *
 * @package SP\Auth
 */
class AuthUtil
{
    /**
     * Proceso para la recuperación de clave.
     *
     * @param UserData $UserData
     * @return bool
     */
    public static function mailPassRecover(UserData $UserData)
    {
        if (!$UserData->isUserIsDisabled()
            && !$UserData->isUserIsLdap()
            && !UserPassRecover::checkPassRecoverLimit($UserData)
        ) {
            $hash = Util::generateRandomBytes();

            $Log = new Log(_('Cambio de Clave'));

            $Log->addDescriptionHtml(_('Se ha solicitado el cambio de su clave de usuario.'));
            $Log->addDescriptionLine();
            $Log->addDescription(_('Para completar el proceso es necesario que acceda a la siguiente URL:'));
            $Log->addDescriptionLine();
            $Log->addDescription(Html::anchorText(Init::$WEBURI . '/index.php?a=passreset&h=' . $hash . '&t=' . time()));
            $Log->addDescriptionLine();
            $Log->addDescription(_('Si no ha solicitado esta acción, ignore este mensaje.'));

            $UserPassRecoverData = new UserPassRecoverData();
            $UserPassRecoverData->setUserpassrUserId($UserData->getUserId());
            $UserPassRecoverData->setUserpassrHash($hash);

            return (Email::sendEmail($Log, $UserData->getUserEmail(), false) && UserPassRecover::getItem($UserPassRecoverData)->add());
        }

        return false;
    }

    /**
     * Comprobar el token de seguridad
     *
     * @param $actionId int El id de la accion
     * @param $token    string El token de seguridad
     * @return bool
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function checkAuthToken($actionId, $token)
    {
        $query = /** @lang SQL */
            'SELECT authtoken_id
            FROM authTokens
            WHERE authtoken_actionId = ?
            AND authtoken_token = ?
            LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($actionId);
        $Data->addParam($token);

        DB::getQuery($Data);

        return ($Data->getQueryNumRows() === 1);
    }

    /**
     * Devuelve el typo de autentificación del servidor web
     *
     * @return string
     */
    public static function getServerAuthType()
    {
        return strtoupper($_SERVER['AUTH_TYPE']);
    }
}