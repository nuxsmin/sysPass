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
        CoreSession::setSessionKey('Authenticator.twofapass', $pass);
    }

    /**
     * Devolver el estado de 2FA del usuario
     *
     * @return bool
     */
    public static function getTwoFApass()
    {
        CoreSession::getSessionKey('Authenticator.twofapass');
    }
}