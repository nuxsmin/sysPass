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
include_once (APP_ROOT . "/inc/init.php");

SP_Util::checkReferer('POST');

if ( ! SP_Init::isLoggedIn() ) {
    SP_Common::printXML(_('La sesión no se ha iniciado o ha caducado'),10);
}

SP_Users::checkUserAccess('config') || SP_Html::showCommonError('unavailable');

$intCategoryFunction = ( isset($_POST["categoryFunction"]) ) ? (int) $_POST["categoryFunction"] : 0;
$categoryName = ( isset($_POST["categoryName"]) ) ? SP_Html::sanitize($_POST["categoryName"]) : "";
$categoryNameNew = ( isset($_POST["categoryNameNew"]) ) ? SP_Html::sanitize($_POST["categoryNameNew"]) : "";
$categoryId = ( isset($_POST["categoryId"]) ) ? (int) $_POST["categoryId"] : 0;

switch ($intCategoryFunction) {
    case 1:
        if ($categoryName == "") {
            SP_Common::printXML(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría existe
            if (SP_Category::getCategoryIdByName($categoryName) === 0) {
                if (SP_Category::categoryAdd($categoryName)) {
                    $message['action'] = _('Nueva Categoría');
                    $message['text'][] = _('Nombre') . ': ' . $categoryName;

                    SP_Common::wrLogInfo($message);
                    SP_Common::sendEmail($message);

                    SP_Common::printXML(_('Categoría añadida'), 0);
                } else {
                    SP_Common::printXML(_('Error al añadir la categoría'));
                }
            } else {
                SP_Common::printXML(_('Ya existe una categoría con ese nombre'));
            }
        }
        break;
    case 2:
        if ($categoryNameNew == "" || !$categoryId) {
            SP_Common::printXML(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría existe
            if (SP_Category::getCategoryIdByName($categoryNameNew) !== 0) {
                SP_Common::printXML(_('Ya existe una categoría con ese nombre'));
            } else {
                if (SP_Category::editCategoryById($categoryId, $categoryNameNew)) {
                    $message['action'] = _('Modificar Categoría');
                    $message['text'][] = _('Nombre') . ': ' . $categoryNameNew;

                    SP_Common::wrLogInfo($message);
                    SP_Common::sendEmail($message);

                    SP_Common::printXML(_('Categoría modificada'), 0);
                } else {
                    SP_Common::printXML(_('Error al modificar la categoría'));
                }
            }
        }
        break;
    case 3:
        if (!$categoryId) {
            SP_Common::printXML(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría está en uso por una cuenta
            if (SP_Category::isCategoryInUse($categoryId)) {
                SP_Common::printXML(_('Categoría en uso, no es posible eliminar'));
            } else {
                if (SP_Category::categoryDel($categoryId)) {
                    $message['action'] = _('Eliminar Categoría');
                    $message['text'][] = _('ID') . ': ' . $categoryId;

                    SP_Common::wrLogInfo($message);
                    SP_Common::sendEmail($message);

                    SP_Common::printXML(_('Categoría eliminada'));
                } else {
                    SP_Common::printXML(_('Error al eliminar la categoría'));
                }
            }
        }
        break;
    default:
        SP_Common::printXML(_('No es una acción válida'));
}