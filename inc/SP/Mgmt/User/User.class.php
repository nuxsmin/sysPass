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

namespace SP\Mgmt\User;

use SP\Config\ConfigDB;
use SP\Core\Crypt;
use SP\Core\SessionUtil;
use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class User
 *
 * @package SP
 */
class User extends UserBase
{
    /**
     * Actualizar la clave maestra del usuario en la BBDD.
     *
     * @param string $masterPwd con la clave maestra
     * @return bool
     */
    public function updateUserMPass($masterPwd)
    {
        $configHashMPass = ConfigDB::getValue('masterPwd');

        if ($configHashMPass === false) {
            return false;
        }

        if (is_null($configHashMPass)) {
            $configHashMPass = Crypt::mkHashPassword($masterPwd);
            ConfigDB::setValue('masterPwd', $configHashMPass);
        }

        if (Crypt::checkHashPass($masterPwd, $configHashMPass, true)) {
            $cryptMPass = Crypt::mkCustomMPassEncrypt(self::getCypherPass(), $masterPwd);

            if (!$cryptMPass) {
                return false;
            }
        } else {
            return false;
        }

        $query = 'UPDATE usrData SET '
            . 'user_mPass = :mPass,'
            . 'user_mIV = :mIV,'
            . 'user_lastUpdateMPass = UNIX_TIMESTAMP() '
            . 'WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($cryptMPass[0], 'mPass');
        $Data->addParam($cryptMPass[1], 'mIV');
        $Data->addParam($this->_userId, 'id');

        return DB::getQuery($Data);
    }

    /**
     * Obtener una clave de cifrado basada en la clave del usuario y un salt.
     *
     * @return string con la clave de cifrado
     */
    private function getCypherPass()
    {
        return Crypt::generateAesKey($this->_userPass . $this->_userLogin);
    }

    /**
     * Desencriptar la clave maestra del usuario para la sesión.
     *
     * @param bool $showPass opcional, para devolver la clave desencriptada
     * @return false|string Devuelve bool se hay error o string si se devuelve la clave
     */
    public function getUserMPass($showPass = false)
    {
        $query = 'SELECT user_mPass, user_mIV FROM usrData WHERE user_id = :id LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($this->_userId, 'id');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        if ($queryRes->user_mPass && $queryRes->user_mIV) {
            $clearMasterPass = Crypt::getDecrypt($queryRes->user_mPass, $queryRes->user_mIV, $this->getCypherPass());

            if (!$clearMasterPass) {
                return false;
            }

            return ($showPass === true) ? $clearMasterPass : SessionUtil::saveSessionMPass($clearMasterPass);
        }

        return false;
    }
}