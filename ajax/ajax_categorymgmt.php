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
    SP_Common::printJSON(_('La sesión no se ha iniciado o ha caducado'),10);
}

$sk = SP_Common::parseParams('p', 'sk', FALSE);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printJSON(_('CONSULTA INVÁLIDA'));
}

$intCategoryFunction = SP_Common::parseParams('p', 'categoryFunction', 0);
$categoryName = SP_Common::parseParams('p', 'categoryName');
$categoryNameNew = SP_Common::parseParams('p', 'categoryNameNew');
$categoryId = SP_Common::parseParams('p', 'categoryId', 0);

switch ($intCategoryFunction) {
    case 1:
        if ($categoryName == "") {
            SP_Common::printJSON(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría existe
            if (SP_Category::getCategoryIdByName($categoryName) === 0) {
                if (SP_Category::categoryAdd($categoryName)) {
                    SP_Common::printJSON(_('Categoría añadida'), 0);
                }
                SP_Common::printJSON(_('Error al añadir la categoría'));
            }
            SP_Common::printJSON(_('Ya existe una categoría con ese nombre'));
        }
        break;
    case 2:
        if ($categoryNameNew == "" || !$categoryId) {
            SP_Common::printJSON(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría existe
            if (SP_Category::getCategoryIdByName($categoryNameNew) !== 0) {
                SP_Common::printJSON(_('Ya existe una categoría con ese nombre'));
            } else {
                // Obtenemos el nombre de la categoría por el Id
                $oldCategoryName = SP_Category::getCategoryNameById($categoryId);
                
                if (SP_Category::editCategoryById($categoryId, $categoryNameNew)) {
                    $message['action'] = _('Modificar Categoría');
                    $message['text'][] = _('Nombre') . ': ' . $oldCategoryName.' > '.$categoryNameNew;

                    SP_Common::wrLogInfo($message);
                    SP_Common::sendEmail($message);

                    SP_Common::printJSON(_('Categoría modificada'), 0);
                }
                SP_Common::printJSON(_('Error al modificar la categoría'));
            }
        }
        break;
    case 3:
        if (!$categoryId) {
            SP_Common::printJSON(_('Nombre de categoría necesario'));
        } else {
            // Comprobamos si la categoría está en uso por una cuenta
            if (SP_Category::isCategoryInUse($categoryId)) {
                SP_Common::printJSON(_('Categoría en uso, no es posible eliminar'));
            } else {
                // Obtenemos el nombre de la categoría por el Id
                $oldCategoryName = SP_Category::getCategoryNameById($categoryId);
                
                if (SP_Category::categoryDel($categoryId)) {
                    $message['action'] = _('Eliminar Categoría');
                    $message['text'][] = _('Nombre') . ': ' .$oldCategoryName.' ('. $categoryId.')';

                    SP_Common::wrLogInfo($message);
                    SP_Common::sendEmail($message);

                    SP_Common::printJSON(_('Categoría eliminada'));
                }
                SP_Common::printJSON(_('Error al eliminar la categoría'));
            }
        }
        break;
    default:
        SP_Common::printJSON(_('Acción Inválida'));
}