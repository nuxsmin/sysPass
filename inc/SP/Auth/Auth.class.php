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

namespace SP\Auth;

use SP\Auth\Database\Database;
use SP\Auth\Ldap\LdapMsAds;
use SP\Auth\Ldap\LdapStd;
use SP\Config\Config;
use SP\DataModel\UserData;
use SP\DataModel\UserPassRecoverData;
use SP\Storage\DB;
use SP\Log\Email;
use SP\Html\Html;
use SP\Core\Init;
use SP\Log\Log;
use SP\Mgmt\Users\UserPassRecover;
use SP\Storage\QueryData;
use SP\Util\Checks;
use SP\Util\Util;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de realizar la autentificación de usuarios de sysPass.
 */
class Auth
{
    public static $status = 0;
    public static $description;

    /**
     * Autentificación de usuarios con LDAP.
     *
     * @param UserData $UserData Datos del usuario
     * @return bool|int|Ldap\LdapAuthData
     */
    public static function authUserLDAP(UserData $UserData)
    {
        if (!Checks::ldapIsAvailable()
            || !Checks::ldapIsEnabled()
        ) {
            return false;
        }

        $Ldap = (Config::getConfig()->isLdapAds()) ? new LdapMsAds() : new LdapStd();

        if (!$Ldap->authenticate($UserData)) {
            return false;
        }

        $LdapAuthData = $Ldap->getLdapAuthData();

        // Comprobamos si la cuenta está bloqueada o expirada
        if ($LdapAuthData->getExpire() > 0) {
            self::$status = 701;

            $LdapAuthData->setStatus(701);

            return false;
        } elseif (!$LdapAuthData->isInGroup()) {
            self::$status = 702;

            $LdapAuthData->setStatus(702);

            return false;
        }

        return $LdapAuthData;
    }

    /**
     * Autentificación de usuarios con MySQL.
     *
     * Esta función comprueba la clave del usuario. Si el usuario necesita ser migrado desde phpPMS,
     * se ejecuta el proceso para actualizar la clave.
     *
     * @param UserData $UserData
     * @return bool
     */
    public static function authUserMySQL(UserData $UserData)
    {
        $AuthDatabase = new Database();

        return $AuthDatabase->authenticate($UserData);
    }

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
     * Comprobar si el usuario es autentificado por el servidor web
     *
     * @param $login string El login del usuario a comprobar
     * @return bool
     */
    public static function checkServerAuthUser($login)
    {
        $authUser = self::getServerAuthUser();

        return $authUser === null ?: $authUser === $login;
    }

    /**
     * Devolver el nombre del usuario autentificado por el servidor web
     *
     * @return string
     */
    public static function getServerAuthUser()
    {
        if (isset($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_USER'])) {
            return $_SERVER['PHP_AUTH_USER'];
        } elseif (isset($_SERVER['REMOTE_USER']) && !empty($_SERVER['REMOTE_USER'])) {
            return $_SERVER['REMOTE_USER'];
        }

        return null;
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
