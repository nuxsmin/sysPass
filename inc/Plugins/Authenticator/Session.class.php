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