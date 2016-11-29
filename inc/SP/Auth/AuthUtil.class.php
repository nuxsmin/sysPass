<?php

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