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

namespace SP\Mgmt\Users;

use SP\Storage\DB;
use SP\Storage\QueryData;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class UserPassRecover para la gestión de recuperaciones de claves de usuarios
 *
 * @package SP
 */
class UserPassRecover
{
    /**
     * Tiempo máximo para recuperar la clave
     */
    const MAX_PASS_RECOVER_TIME = 3600;
    /**
     * Número de intentos máximos para recuperar la clave
     */
    const MAX_PASS_RECOVER_LIMIT = 3;
    const USER_LOGIN_EXIST = 1;
    const USER_MAIL_EXIST = 2;

    /**
     * Comprobar el hash de recuperación de clave.
     *
     * @param string $hash con el hash de recuperación
     * @return int con el Id del usuario
     */
    public static function checkHashPassRecover($hash)
    {
        $query = 'SELECT userpassr_userId FROM usrPassRecover '
            . 'WHERE userpassr_hash = :hash '
            . 'AND userpassr_used = 0 '
            . 'AND userpassr_date >= :date '
            . 'ORDER BY userpassr_date DESC LIMIT 1';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($hash, 'hash');
        $Data->addParam(time() - self::MAX_PASS_RECOVER_TIME, 'date');

        $queryRes = DB::getResults($Data);

        if ($queryRes === false) {
            return false;
        }

        return $queryRes->userpassr_userId;
    }

    /**
     * Marcar como usado el hash de recuperación de clave.
     *
     * @param string $hash con el hash de recuperación
     * @return bool
     */
    public static function updateHashPassRecover($hash)
    {
        $query = 'UPDATE usrPassRecover SET userpassr_used = 1 WHERE userpassr_hash = :hash';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam($hash, 'hash');

        return DB::getQuery($Data);
    }

    /**
     * Comprobar el límite de recuperaciones de clave.
     *
     * @param string $login con el login del usuario
     * @return bool
     */
    public static function checkPassRecoverLimit($login)
    {
        $query = 'SELECT userpassr_userId ' .
            'FROM usrPassRecover ' .
            'WHERE userpassr_userId = :id ' .
            'AND userpassr_used = 0 ' .
            'AND userpassr_date >= :date';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(UserUtil::getUserIdByLogin($login), 'id');
        $Data->addParam(time() - self::MAX_PASS_RECOVER_TIME, 'date');

        return (DB::getQuery($Data) === false || DB::$lastNumRows >= self::MAX_PASS_RECOVER_LIMIT);
    }

    /**
     * Insertar un registro de recuperación de clave.
     *
     * @param string $login con el login del usuario
     * @param string $hash  con el hash para el cambio
     * @return bool
     */
    public static function addPassRecover($login, $hash)
    {
        $query = 'INSERT INTO usrPassRecover SET '
            . 'userpassr_userId = :id,'
            . 'userpassr_hash = :hash,'
            . 'userpassr_date = UNIX_TIMESTAMP(),'
            . 'userpassr_used = 0';

        $Data = new QueryData();
        $Data->setQuery($query);
        $Data->addParam(UserUtil::getUserIdByLogin($login), 'id');
        $Data->addParam($hash, 'hash');

        return DB::getQuery($Data);
    }

}