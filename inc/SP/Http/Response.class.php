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

namespace SP\Http;

use SP\Util\Json;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es encargada de ejecutar acciones comunes para las funciones
 */
class Response
{
    /**
     * Devuelve una respuesta en formato XML con el estado y el mensaje.
     *
     * @param string $description mensaje a devolver
     * @param int    $status      devuelve el estado
     * @return bool
     */
    public static function printXML($description, $status = 1)
    {
        if (!is_string($description)) {
            return false;
        }

        $arrStrFrom = array("&", "<", ">", "\"", "\'");
        $arrStrTo = array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;");

        $cleanDescription = str_replace($arrStrFrom, $arrStrTo, $description);

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<root>\n<status>" . $status . "</status>\n <description>" . $cleanDescription . "</description>\n</root>";

        header("Content-Type: application/xml");
        exit($xml);
    }

    /**
     * Devuelve una respuesta en formato JSON con el estado y el mensaje.
     *
     * @param string|array $data   mensaje a devolver
     * @param int          $status devuelve el estado
     * @param string       $action con la accion a realizar
     * @return bool
     */
    public static function printJSON($data, $status = 1, $action = '')
    {
        if (!is_array($data)) {
            $json = array(
                'status' => $status,
                'description' => $data,
                'action' => $action
            );
        } else {
            $data['status'] = $status;
            $data['action'] = $action;
            $json = $data;
        }

        header('Content-type: application/json');
        exit(Json::getJson($json));
    }
}