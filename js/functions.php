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

$offset = 3600 * 24;
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";

header("content-type: application/x-javascript");
header($expire);
header('Cache-Control: max-age=3600, must-revalidate');

define('APP_ROOT', '..');

if ( isset($_GET["l"]) && isset($_GET["r"]) ){
    $appLang = strtolower($_GET["l"]);
    $appRoot = base64_decode(urldecode($_GET["r"]));
} else{
    return;
}

$locale= array(
    "es_es.utf8" => array('Error en la consulta',
                    'Ha ocurrido un error',
                    'Sesión finalizada',
                    'Borrar la cuenta?',
                    'Borrar el usuario?',
                    'Guarde la configuración para que sea efectiva',
                    'Clave Generada',
                    'Nivel alto',
                    'Nivel medio',
                    'Nivel bajo',
                    'Nivel muy alto',
                    'Utilizar al menos 8 caracteres',
                    'Borrar elemento?',
                    'Página no encontrada',
                    'Archivo no soportado para visualizar',
                    'Eliminar archivo?',
                    'Su navegador no soporta subir archivos con HTML5',
                    'Demasiados archivos',
                    'No es posible guardar el archivo.<br>Tamaño máximo:',
                    'Extensión no permitida',
                    'Vaciar el registro de eventos?'),
    "en_us.utf8" => array('Query error',
                    'There was an error',
                    'Session ended',
                    'Delete account?',
                    'Delete user?',
                    'You should save configuration in order to take effect',
                    'Generated Password',
                    'High level',
                    'Average level',
                    'Low level',
                    'Very high level',
                    'You should use at least 8 characters',
                    'Delete item?',
                    'Page not found',
                    'File not supported for preview',
                    'Delete file?',
                    'Your browser does not support HTML5 file uploads',
                    'Too many files',
                    'Unable to save file.<br>Max file size:',
                    'Extension not allowed',
                    'Clear event log?'));

$arrJsLang = array();

foreach ( $locale[$appLang] as $langIndex => $langDesc ){
    $arrJsLang[] = "'".$langDesc."'";
}

echo "// i18n language array from PHP. Detected language: $appLang\n";
echo "var LANG = [".implode(",",$arrJsLang)."]; \n\n";
echo "var APP_ROOT = '$appRoot';\n";
include_once 'functions.js';