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
 * Class AccountHelp
 *
 * @package SP\Modules\Api\Controllers\Help
 */
final class AccountHelp implements HelpInterface
{
    use HelpTrait;

    /**
     * @return array
     */
    public static function view()
    {
        return
            [
                self::getItem('id', __('Account Id'), true)
            ];
    }

    /**
     * @return array
     */
    public static function viewPass()
    {
        return
            [
                self::getItem('id', __('Account Id'), true),
                self::getItem('tokenPass', __('Token\'s password'), true),
                self::getItem('details', __('Send details in the response'))
            ];
    }

    /**
     * @return array
     */
    public static function editPass()
    {
        return
            [
                self::getItem('id', __('Account Id'), true),
                self::getItem('tokenPass', __('Token\'s password'), true),
                self::getItem('pass', __('Password'), true),
                self::getItem('expireDate', __('Password Expiry Date'))
            ];
    }

    /**
     * @return array
     */
    public static function create()
    {
        return
            [
                self::getItem('tokenPass', __('Token\'s password'), true),
                self::getItem('name', __('Account name'), true),
                self::getItem('categoryId', __('Category Id'), true),
                self::getItem('clientId', __('Client Id'), true),
                self::getItem('pass', __('Password'), true),
                self::getItem('login', __('Access user')),
                self::getItem('url', __('Access URL or IP')),
                self::getItem('notes', __('Notes about the account')),
                self::getItem('private', __('Private Account')),
                self::getItem('privateGroup', __('Private Account for Group')),
                self::getItem('expireDate', __('Password Expiry Date')),
                self::getItem('parentId', __('Linked Account')),
                self::getItem('tagsId', __('Array with tags id')),
                self::getItem('userGroupId', __('Group Id'))
            ];
    }

    /**
     * @return array
     */
    public static function edit()
    {
        return
            [
                self::getItem('id', __('Account Id'), true),
                self::getItem('name', __('Account name')),
                self::getItem('categoryId', __('Category Id')),
                self::getItem('clientId', __('Client Id')),
                self::getItem('login', __('Access user')),
                self::getItem('url', __('Access URL or IP')),
                self::getItem('notes', __('Notes about the account')),
                self::getItem('private', __('Private Account')),
                self::getItem('privateGroup', __('Private Account for Group')),
                self::getItem('expireDate', __('Password Expiry Date')),
                self::getItem('parentId', __('Linked Account')),
                self::getItem('tagsId', __('Array with tags id')),
                self::getItem('userGroupId', __('Group Id'))
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
                self::getItem('count', __('Number of results to display')),
                self::getItem('categoryId', __('Category Id to filter on')),
                self::getItem('clientId', __('Client Id to filter on')),
                self::getItem('tagsId', __('Array with tags id for filtering')),
                self::getItem('op', __('Filtering operator'))
            ];
    }

    /**
     * @return array
     */
    public static function delete()
    {
        return
            [
                self::getItem('id', __('Account Id'), true)
            ];
    }
}