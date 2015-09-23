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

namespace SP;


class SessionUtil
{
    /**
     * Establece las variables de sesión del usuario.
     *
     * @param User $User
     * @throws SPException
     */
    public static function loadUserSession(User $User)
    {
        Session::setUserId($User->getUserId());
        Session::setUserName($User->getUserName());
        Session::setUserLogin($User->getUserLogin());
        Session::setUserProfileId($User->getUserProfileId());
        Session::setUserGroupId($User->getUserGroupId());
        Session::setUserGroupName($User->getUserGroupName());
        Session::setUserEMail($User->getUserEmail());
        Session::setUserIsAdminApp($User->isUserIsAdminApp());
        Session::setUserIsAdminAcc($User->isUserIsAdminAcc());
        Session::setUserIsLdap($User->isUserIsLdap());
        Session::setUserProfile(Profile::getProfile($User->getUserProfileId()));
    }

    /**
     * Establecer la clave pública RSA en la sessión
     */
    public static function loadPublicKey()
    {
        $CryptPKI = new CryptPKI();
        Session::setPublicKey($CryptPKI->getPublicKey());
    }
}