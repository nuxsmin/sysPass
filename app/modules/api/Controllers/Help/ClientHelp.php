<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 * Class ClientHelp
 *
 * @package SP\Modules\Api\Controllers\Help
 */
final class ClientHelp implements HelpInterface
{
    use HelpTrait;

    /**
     * @return array
     */
    public static function view()
    {
        return
            [
                self::getItem('id', __('Client Id'), true)
            ];
    }

    /**
     * @return array
     */
    public static function create()
    {
        return
            [
                self::getItem('name', __('Client name'), true),
                self::getItem('description', __('Client description')),
                self::getItem('global', __('Global'))
            ];
    }

    /**
     * @return array
     */
    public static function edit()
    {
        return
            [
                self::getItem('id', __('Client Id'), true),
                self::getItem('name', __('Client name'), true),
                self::getItem('description', __('Client description')),
                self::getItem('global', __('Global'))
            ];
    }

    /**
     * @return array
     */
    public static function search()
    {
        return
            [
                self::getItem('text', __('Text to search for')),
                self::getItem('count', __('Number of results to display'))
            ];
    }

    /**
     * @return array
     */
    public static function delete()
    {
        return
            [
                self::getItem('id', __('Client Id'), true)
            ];
    }
}