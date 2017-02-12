<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Mgmt\CustomFields;

defined('APP_ROOT') || die();

use SP\Core\ActionsInterface;

/**
 * Class CustomFieldTypes
 *
 * @package SP\Mgmt\CustomFields
 */
class CustomFieldTypes
{
    /**
     * Tipos de Campos
     */
    const TYPE_TEXT = 1;
    const TYPE_PASSWORD = 2;
    const TYPE_DATE = 3;
    const TYPE_NUMBER = 4;
    const TYPE_EMAIL = 5;
    const TYPE_TELEPHONE = 6;
    const TYPE_URL = 7;
    const TYPE_COLOR = 8;
    const TYPE_WIKI = 9;
    const TYPE_TEXTAREA = 10;

    /**
     * Devolver los tipos de campos soportados
     *
     * @param int  $typeId El tipo de campo
     * @param bool $nice   Devolver en formato "bonito"
     * @return array
     */
    public static function getFieldsTypes($typeId = null, $nice = false)
    {
        $types = [
            self::TYPE_TEXT => ['text', __('Texto')],
            self::TYPE_WIKI => ['text', __('Wiki')],
            self::TYPE_PASSWORD => ['password', __('Clave')],
            self::TYPE_DATE => ['date', __('Fecha')],
            self::TYPE_NUMBER => ['number', __('Número')],
            self::TYPE_EMAIL => ['email', __('Email')],
            self::TYPE_TELEPHONE => ['tel', __('Teléfono')],
            self::TYPE_URL => ['url', __('Link')],
            self::TYPE_COLOR => ['color', __('Color')],
            self::TYPE_TEXTAREA => ['textarea', __('Área de Texto')]
        ];

        if (null !== $typeId) {
            return ($nice === false) ? $types[$typeId][0] : $types[$typeId][1];
        }

        return $types;
    }

    /**
     * Devuelve los módulos disponibles para los campos personalizados
     *
     * @param null $moduleId
     * @return array|string
     */
    public static function getFieldsModules($moduleId = null)
    {
        $modules = [
            ActionsInterface::ACTION_ACC => __('Cuentas'),
            ActionsInterface::ACTION_MGM_CATEGORIES => __('Categorías'),
            ActionsInterface::ACTION_MGM_CUSTOMERS => __('Clientes'),
            ActionsInterface::ACTION_USR_USERS => __('Usuarios'),
            ActionsInterface::ACTION_USR_GROUPS => __('Grupos')

        ];

        if (null !== $moduleId && !isset($modules[$moduleId])) {
            return '';
        }

        return (null !== $moduleId) ? $modules[$moduleId] : $modules;
    }
}