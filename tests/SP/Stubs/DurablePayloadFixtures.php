<?php

declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
 */

namespace SP\Tests\Stubs;

use SP\Domain\Common\Adapters\SerializedPayloadRewriter;

/**
 * Class DurablePayloadFixtures
 */
final class DurablePayloadFixtures
{
    public static function vaultWithCrypt(): string
    {
        return 'O:19:"SP\Core\Crypt\Vault":5:{s:25:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'data";s:7:"payload";s:24:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'key";s:3:"key";s:28:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'timeSet";i:1;s:32:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'timeUpdated";i:0;s:26:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'crypt";O:19:"SP\Core\Crypt\Crypt":0:{}}';
    }

    public static function vaultWithoutCrypt(): string
    {
        return 'O:19:"SP\Core\Crypt\Vault":4:{s:25:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'data";s:7:"payload";s:24:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'key";s:3:"key";s:28:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0"
               . 'timeSet";i:1;s:32:"' . "\0" . 'SP\Core\Crypt\Vault' . "\0" . 'timeUpdated";i:0;}';
    }

    public static function currentVault(): string
    {
        return SerializedPayloadRewriter::rewrite(self::vaultWithCrypt());
    }

    public static function profile(): string
    {
        return 'O:24:"SP\DataModel\ProfileData":2:{s:10:"' . "\0*\0" . 'accView";b:1;s:13:"'
               . "\0*\0" . 'accPrivate";b:0;}';
    }

    public static function preferences(): string
    {
        return 'O:32:"SP\DataModel\UserPreferencesData":5:{s:7:"user_id";i:1;s:4:"lang";s:5:"es_ES";'
               . 's:5:"theme";s:13:"material-blue";s:14:"resultsPerPage";i:6;s:18:"checkNotifications";b:1;}';
    }

    public static function jsonPreferences(): string
    {
        return '{"lang":"en_US","theme":"material-blue","resultsPerPage":12}';
    }

    public static function password(): string
    {
        return 'O:32:"SP\DataModel\ItemPreset\Password":10:{s:40:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'length";i:12;s:44:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useNumbers";b:1;s:44:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useLetters";b:1;s:44:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useSymbols";b:0;s:42:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useUpper";b:1;s:42:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useLower";b:1;s:42:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'useImage";b:0;s:44:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'expireTime";i:86400;s:39:"' . "\0" . 'SP\DataModel\ItemPreset\Password'
               . "\0" . 'score";i:0;s:39:"' . "\0" . 'SP\DataModel\ItemPreset\Password' . "\0" . 'regex";N;}';
    }

    public static function accountPrivate(): string
    {
        return 'O:38:"SP\DataModel\ItemPreset\AccountPrivate":2:{s:51:"' . "\0"
               . 'SP\DataModel\ItemPreset\AccountPrivate' . "\0" . 'privateUser";b:1;s:52:"' . "\0"
               . 'SP\DataModel\ItemPreset\AccountPrivate' . "\0" . 'privateGroup";b:0;}';
    }

    public static function accountPermission(): string
    {
        return 'O:41:"SP\DataModel\ItemPreset\AccountPermission":4:{s:52:"' . "\0"
               . 'SP\DataModel\ItemPreset\AccountPermission' . "\0" . 'usersView";a:1:{i:0;i:1;}s:52:"'
               . "\0" . 'SP\DataModel\ItemPreset\AccountPermission' . "\0" . 'usersEdit";a:1:{i:0;i:2;}s:57:"'
               . "\0" . 'SP\DataModel\ItemPreset\AccountPermission' . "\0"
               . 'userGroupsView";a:1:{i:0;i:3;}s:57:"' . "\0" . 'SP\DataModel\ItemPreset\AccountPermission'
               . "\0" . 'userGroupsEdit";a:1:{i:0;i:4;}}';
    }

    public static function sessionTimeout(): string
    {
        return 'O:38:"SP\DataModel\ItemPreset\SessionTimeout":3:{s:47:"' . "\0"
               . 'SP\DataModel\ItemPreset\SessionTimeout' . "\0" . 'address";s:8:"10.0.0.1";s:44:"' . "\0"
               . 'SP\DataModel\ItemPreset\SessionTimeout' . "\0" . 'mask";s:13:"255.255.255.0";s:47:"'
               . "\0" . 'SP\DataModel\ItemPreset\SessionTimeout' . "\0" . 'timeout";i:3600;}';
    }

    public static function malformed(): string
    {
        return 'not-a-serialized-payload';
    }
}
