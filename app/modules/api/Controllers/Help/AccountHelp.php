<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Api\Controllers\Help;

/**
 * Class AccountHelp
 *
 * @package SP\Modules\Api\Controllers\Help
 */
class AccountHelp implements HelpInterface
{
    use HelpTrait;

    /**
     * @return array
     */
    public static function view()
    {
        return
            [
                self::getItem('id', __('Id de la cuenta'), true)
            ];
    }

    /**
     * @return array
     */
    public static function viewPass()
    {
        return
            [
                self::getItem('id', __('Id de la cuenta'), true),
                self::getItem('tokenPass', __('Clave del token'), true),
                self::getItem('details', __('Devolver detalles en la respuesta'))
            ];
    }

    /**
     * @return array
     */
    public static function editPass()
    {
        return
            [
                self::getItem('id', __('Id de la cuenta'), true),
                self::getItem('tokenPass', __('Clave del token'), true),
                self::getItem('pass', __('Clave'), true),
                self::getItem('expireDate', __('Fecha Caducidad Clave'))
            ];
    }

    /**
     * @return array
     */
    public static function create()
    {
        return
            [
                self::getItem('tokenPass', __('Clave del token'), true),
                self::getItem('name', __('Nombre de cuenta'), true),
                self::getItem('categoryId', __('Id de categoría'), true),
                self::getItem('clientId', __('Id de cliente'), true),
                self::getItem('pass', __('Clave'), true),
                self::getItem('login', __('Usuario de acceso')),
                self::getItem('url', __('URL o IP de acceso')),
                self::getItem('notes', __('Notas sobre la cuenta')),
                self::getItem('private', __('Cuenta Privada')),
                self::getItem('privateGroup', __('Cuenta Privada Grupo')),
                self::getItem('expireDate', __('Fecha Caducidad Clave')),
                self::getItem('parentId', __('Cuenta Vinculada')),
                self::getItem('tagsId', __('Array con Ids de etiquetas'))
            ];
    }

    /**
     * @return array
     */
    public static function edit()
    {
        return
            [
                self::getItem('id', __('Id de la cuenta'), true),
                self::getItem('name', __('Nombre de cuenta')),
                self::getItem('categoryId', __('Id de categoría')),
                self::getItem('clientId', __('Id de cliente')),
                self::getItem('login', __('Usuario de acceso')),
                self::getItem('url', __('URL o IP de acceso')),
                self::getItem('notes', __('Notas sobre la cuenta')),
                self::getItem('private', __('Cuenta Privada')),
                self::getItem('privateGroup', __('Cuenta Privada Grupo')),
                self::getItem('expireDate', __('Fecha Caducidad Clave')),
                self::getItem('parentId', __('Cuenta Vinculada')),
                self::getItem('tagsId', __('Array con Ids de etiquetas'))
            ];
    }

    /**
     * @return array
     */
    public static function search()
    {
        return
            [
                self::getItem('text', __('Texto a buscar')),
                self::getItem('count', __('Número de resultados a mostrar')),
                self::getItem('categoryId', __('Id de categoría a filtrar')),
                self::getItem('clientId', __('Id de cliente a filtrar')),
                self::getItem('tagsId', __('Array con Ids de etiquetas a filtrar')),
                self::getItem('op', __('Operador de filtrado'))
            ];
    }

    /**
     * @return array
     */
    public static function delete()
    {
        return
            [
                self::getItem('id', __('Id de la cuenta'), true)
            ];
    }
}