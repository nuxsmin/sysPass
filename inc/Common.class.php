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
     * Devuelve un icono de ayuda con el mensaje.
     *
     * @param int $type tipo de mensaje
     * @param int $id   id del mensaje
     * @return string Con la etiqueta html del icono de ayuda
     */
    public static function printHelpButton($type, $id)
    {
        $msgHelp[0] = _('Indicar el usuario de conexión a la base de datos de phpPMS');
        $msgHelp[1] = _('Indicar el nombre de la base de datos de phpPMS');
        $msgHelp[2] = _('Indicar el servidor de la base de datos de phpPMS');
        $msgHelp[3] = _('Habilita el nombre de la cuenta de la búsqueda, como enlace a los detalles de la cuenta');
        $msgHelp[4] = _('Número de resultados por página a mostrar, al realizar una búsqueda');
        $msgHelp[5] = _('Habilita la subida/descarga de archivos para las cuentas');
        $msgHelp[6] = _('Establece el tamaño máximo para subir archivos') . "<br><br>" . _('El máximo absuluto es de 16MB');
        $msgHelp[7] = _('Habilita la opción de añadir un enlace a Wiki externa para los resultados de la búsqueda');
        $msgHelp[8] = _('URL que utiliza la wiki para realizar una búsqueda de una página en esta') . "<br><br>" . _('Como parámetro se utiliza el nombre del cliente') . "<br><br>" . _('Ejemplo') . ":<br><br>https://wiki.cygnux.org/search.php?phrase=";
        $msgHelp[9] = _('URL que utiliza la wiki para acceder a los detalles de una página de ésta') . "<br><br>" . _('El nombre de la cuenta se utiliza como parámetro de la variable de búsqueda de la Wiki') . "<br><br>" . _('Ejemplo') . ":<br><br>https://wiki.cygnux.org/show.php?name=";
        $msgHelp[10] = _('Prefijo para determinar qué cuentas tienen un enlace a una página de la Wiki') . "<br><br>" . _('Ejemplos') . ": serv- | srv- | vm-";
        $msgHelp[11] = _('Habilita de autentificación mediante servidor LDAP') . "<br><br>" . _('Este método utilizará MySQL en caso de fallo');
        $msgHelp[12] = _('Usuario para conectar con el servicio de LDAP') . "<br><br>" . _('Ejemplo') . ":<br><br>cn=syspass,ou=Users,dc=cygnux,o=org";
        $msgHelp[13] = _('Base en la que realizar la búsqueda de usuarios de LDAP') . "<br><br>" . _('Ejemplo') . ":<br><br>dc=cygnux,o=org";
        $msgHelp[14] = _('Grupo de LDAP al que debe de pertenecer el usuario para permitir el acceso') . "<br><br>" . _('Ejemplo') . ":<br><br>cn=GRP_SPUSERS,ou=USERS | GRP_SPUSERS";
        $msgHelp[15] = _('Nombre o dirección IP del servidor de LDAP');
        $msgHelp[16] = _('Establece una nueva clave maestra sin re-encriptar las cuentas');
        $msgHelp[17] = _('Clave del usuario de conexión a LDAP');
        $msgHelp[18] = _('En este modo no se puede acceder a la aplicación. Para deshabilitarlo es necesario modificar el archivo de configuración');
        $msgHelp[19] = _('Muestra información relativa a la configuración de la aplicación y rendimiento');
        $msgHelp[20] = _('Guarda las acciones realizadas en la aplicación');
        $msgHelp[21] = _('Comprobar actualizaciones de la aplicación (sólo para los usuarios administradores)');
        $msgHelp[22] = _('Extensiones de máximo 4 caracteres.') . "<br><br>" . _('Escribir extensión y pulsar intro para añadir.');
        $msgHelp[23] = _('Importar desde KeePass o KeePassX. El nombre del cliente será igual a KeePass o KeePassX') . "<br><br>" . _('Importar desde un archivo CSV con el formato') . ":<br><br>" . _('nombre_de_cuenta;cliente;categoría;url;usuario;clave;notas') . "<br><br>" . _('Si el cliente o la categoría no están creados, se crean automáticamente.');
        $msgHelp[24] = _('Permite que las cuentas sin acceso sean visibles sólo para las búsquedas.');
        $msgHelp[25] = _('Muestra los resultados de búsqueda de cuentas en formato tarjeta.');
        $msgHelp[26] = _('Habilita el modo de conexión con LDAP de Active Directory.');
        $msgHelp[27] = _('Define el grupo de usuarios por defecto para los nuevos usuarios de LDAP.');
        $msgHelp[28] = _('Define el perfil de usuario por defecto para los nuevos usuarios de LDAP.');
        $msgHelp[29] = _('Define el usuario por defecto para las cuentas importadas.');
        $msgHelp[30] = _('Define el grupo por defecto para las cuentas importadas.');

        if (array_key_exists($id, $msgHelp)) {
            return '<img src="imgs/help.png" title="' . $msgHelp[$id] . '" class="inputImgMini" />';
        }
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