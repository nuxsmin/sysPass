<?php
/** 
* sysPass
* 
* @author nuxsmin
* @link http://syspass.org
* @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
class SP_Log {
    static $numRows;

    /**
     * @brief Obtener los eventos guardados
     * @param int $start con el número de registro desde el que empezar
     * @return array con el resultado de la consulta
     */ 
    public static function getEvents($start){
        $query = 'SELECT SQL_CALC_FOUND_ROWS log_id,'
                . 'FROM_UNIXTIME(log_date) as date,'
                . 'log_action,log_login,'
                . 'log_description '
                . 'FROM log '
                . 'ORDER BY log_id DESC '
                . 'LIMIT '.$start.', 50';

        $queryRes = DB::getResults($query, __FUNCTION__, TRUE);
        
        if ( $queryRes === FALSE ){
            return FALSE;
        }
        
        $numRows = DB::getResults("SELECT FOUND_ROWS() as numRows", __FUNCTION__);
        
        self::$numRows = $numRows->numRows;
        
        return $queryRes;
    }
    
    /**
     * @brief Limpiar el registro de eventos
     * @return bool con el resultado
     */ 
    public static function clearEvents(){
        $query = 'TRUNCATE TABLE log';

        $queryRes = DB::doQuery($query, __FUNCTION__);
        
        if ( $queryRes === FALSE ){
            return FALSE;
        }
        
        $message['action'] = _('Vaciar Eventos');
        $message['text'][] = _('Vaciar registro de eventos.');
        SP_Common::wrLogInfo($message);
        
        return TRUE;
    }
}
