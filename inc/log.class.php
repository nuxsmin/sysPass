<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de manejar el registro de eventos
 */
class SP_Log
{
    static $numRows;

    /**
     * @brief Obtener los eventos guardados
     * @param int $start con el número de registro desde el que empezar
     * @return array con el resultado de la consulta
     */
    public static function getEvents($start)
    {
        $query = 'SELECT SQL_CALC_FOUND_ROWS ' .
            'log_id,' .
            'FROM_UNIXTIME(log_date) as date,' .
            'log_action,' .
            'log_login,' .
            'log_ipAddress,' .
            'log_description ' .
            'FROM log ' .
            'ORDER BY log_id DESC ' .
            'LIMIT ' . $start . ', 50';

        $queryRes = DB::getResults($query, __FUNCTION__, true);

        if ($queryRes === false) {
            return false;
        }

        $numRows = DB::getResults("SELECT FOUND_ROWS() as numRows", __FUNCTION__);

        self::$numRows = $numRows->numRows;

        return $queryRes;
    }

    /**
     * @brief Limpiar el registro de eventos
     * @return bool con el resultado
     */
    public static function clearEvents()
    {
        $query = 'TRUNCATE TABLE log';

        $queryRes = DB::doQuery($query, __FUNCTION__);

        if ($queryRes === false) {
            return false;
        }

        $message['action'] = _('Vaciar Eventos');
        $message['text'][] = _('Vaciar registro de eventos');
        self::wrLogInfo($message);
        SP_Common::sendEmail($message);

        return true;
    }

    /**
     * @brief Crear un nuevo registro en el registro de eventos
     * @param array $message con el nombre de la accióm y el texto del mensaje
     * @return bool
     */
    public static function wrLogInfo($message)
    {
        if (SP_Config::getValue('logenabled', 0) === 0 || !is_array($message)) {
            return false;
        }

        $login = (isset($_SESSION["ulogin"])) ? $_SESSION["ulogin"] : "-";
        $userId = (isset($_SESSION['uid'])) ? $_SESSION['uid'] : 0;
        $action = strip_tags(utf8_encode($message['action']));
        $description = strip_tags(utf8_encode(implode(';;', $message['text'])));

        $query = "INSERT INTO log SET " .
            "log_date = UNIX_TIMESTAMP()," .
            "log_login = '" . DB::escape($login) . "'," .
            "log_userId = " . $userId . "," .
            "log_ipAddress = '" . DB::escape($_SERVER['REMOTE_ADDR']) . "'," .
            "log_action = '" . DB::escape($action) . "'," .
            "log_description = '" . DB::escape($description) . "'";

        if (DB::doQuery($query, __FUNCTION__) === false) {
            return false;
        }
    }
}
