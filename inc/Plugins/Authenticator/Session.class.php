<?php
/**
 * Created by PhpStorm.
 * User: rdb
 * Date: 4/01/17
 * Time: 8:32
 */

namespace Plugins\Authenticator;

use SP\Core\Session as CoreSession;

/**
 * Class Session
 *
 * @package Plugins\Authenticator
 */
class Session
{
    /**
     * Establecer el estado de 2FA del usuario
     *
     * @param bool $pass
     */
    public static function setTwoFApass($pass)
    {
        CoreSession::setPluginKey(AuthenticatorPlugin::PLUGIN_NAME, 'twofapass', $pass);
    }

    /**
     * Devolver el estado de 2FA del usuario
     *
     * @return bool
     */
    public static function getTwoFApass()
    {
        return CoreSession::getPluginKey(AuthenticatorPlugin::PLUGIN_NAME, 'twofapass');
    }

    /**
     * Establecer los datos del usuario
     *
     * @param AuthenticatorData $data
     */
    public static function setUserData(AuthenticatorData $data)
    {
        CoreSession::setPluginKey(AuthenticatorPlugin::PLUGIN_NAME, 'userdata', $data);
    }

    /**
     * Devolver los datos del usuario
     *
     * @return AuthenticatorData
     */
    public static function getUserData()
    {
        return CoreSession::getPluginKey(AuthenticatorPlugin::PLUGIN_NAME, 'userdata');
    }
}