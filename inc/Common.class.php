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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es encargada de ejecutar acciones comunes para las funciones
 */
class Common
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
        if (!is_string($data) && !is_array($data)) {
            return false;
        }

        $arrStrFrom = array("\\", '"', "'");
        $arrStrTo = array("\\", '\"', "\'");

        if (!is_array($data)) {
            $json = array(
                'status' => $status,
                'description' => str_replace($arrStrFrom, $arrStrTo, $data),
                'action' => $action
            );
        } else {
            array_walk($data,
                function (&$value, &$key) use ($arrStrFrom, $arrStrTo) {
                    return str_replace($arrStrFrom, $arrStrTo, $value);
                }
            );

            $data['status'] = $status;
            $data['action'] = $action;
            $json = $data;
        }

        header('Content-type: application/json');
        exit(json_encode($json));
    }

    /**
     * Devuelve un hash para verificación de formularios.
     * Esta función genera un hash que permite verificar la autenticidad de un formulario
     *
     * @param bool $new si es necesrio regenerar el hash
     * @return string con el hash de verificación
     */
    public static function getSessionKey($new = false)
    {
        $hash = sha1(time());

        // Generamos un nuevo hash si es necesario y lo guardamos en la sesión
        if (is_null(Session::getSecurityKey()) || $new === true) {
            Session::setSecurityKey($hash);
            return $hash;
        }

        return Session::getSecurityKey();
    }

    /**
     * Comprobar el hash de verificación de formularios.
     *
     * @param string $key con el hash a comprobar
     * @return bool|string si no es correcto el hash devuelve bool. Si lo es, devuelve el hash actual.
     */
    public static function checkSessionKey($key)
    {
        return (!is_null(Session::getSecurityKey()) && Session::getSecurityKey() == $key);
    }
}