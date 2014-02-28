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

define('APP_ROOT', '..');
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'init.php';

$offset = 3600 * 24;
$expire = "Expires: " . gmdate("D, d M Y H:i:s", time() + $offset) . " GMT";

header("content-type: application/x-javascript");
header($expire);
header('Cache-Control: max-age=3600, must-revalidate');

$arrJsLang = array(_('Error en la consulta'),
    _('Ha ocurrido un error'),
    _('Sesión finalizada'),
    _('Borrar la cuenta?'),
    _('Borrar el usuario?'),
    _('Guarde la configuración para que sea efectiva'),
    _('Clave Generada'),
    _('Nivel alto'),
    _('Nivel medio'),
    _('Nivel bajo'),
    _('Nivel muy alto'),
    _('Utilizar al menos 8 caracteres'),
    _('Borrar elemento?'),
    _('Página no encontrada'),
    _('Archivo no soportado para visualizar'),
    _('Eliminar archivo?'),
    _('Su navegador no soporta subir archivos con HTML5'),
    _('Demasiados archivos'),
    _('No es posible guardar el archivo.<br>Tamaño máximo:'),
    _('Extensión no permitida'),
    _('Vaciar el registro de eventos?'));

echo "// i18n language array from PHP. Detected language: ".SP_Init::$LANG."\n";
echo "var LANG = ['".implode("','",$arrJsLang)."']; \n";
echo "var APP_ROOT = '".SP_Init::$WEBROOT."';\n\n";
include_once 'functions.js';