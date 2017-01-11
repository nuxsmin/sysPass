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

namespace SP\Mgmt\Users;

use Plugins\Authenticator\Authenticator;
use Plugins\Authenticator\AuthenticatorData;
use Plugins\Authenticator\AuthenticatorPlugin;
use SP\Core\Exceptions\SPException;
use SP\DataModel\PluginData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Log\Log;
use SP\Mgmt\Plugins\Plugin;
use SP\Storage\DB;
use SP\Storage\QueryData;
use SP\Util\Util;

/**
 * Class UserPreferencesUtil
 *
 * @package SP\Mgmt\Users
 */
class UserPreferencesUtil
{
    /**
     * @param UserData $UserData
     * @param UserPreferencesData $UserPreferences
     * @return bool
     */
    public static function migrateTwoFA(UserData $UserData, UserPreferencesData $UserPreferences)
    {
        $Log = new Log(__FUNCTION__);
        $Log->addDescription(_('Actualizando preferencias'));

        $Authenticator = new Authenticator($UserData->getUserId(), $UserData->getUserLogin());

        /** @var AuthenticatorData $AuthenticatorData */
        $AuthenticatorData = new AuthenticatorData();
        $AuthenticatorData->setUserId($UserData->getUserId());
        $AuthenticatorData->setIV($Authenticator->getInitializationKey());
        $AuthenticatorData->setTwofaEnabled(1);
        $AuthenticatorData->setDate(time());

        $data[$UserData->getUserId()] = $AuthenticatorData;

        $Log->addDetails(_('Usuario'), $UserData->getUserLogin());

        try {
            $PluginData = new PluginData();
            $PluginData->setPluginName(AuthenticatorPlugin::PLUGIN_NAME);
            $PluginData->setPluginEnabled(1);
            $PluginData->setPluginData(serialize($data));

            Plugin::getItem($PluginData)->update();

            $UserPreferences->setUse2Fa(0);
            $UserPreferences->setUserId($UserData->getUserId());
            UserPreferences::getItem($UserPreferences)->update();

            $Log->addDescription(_('Preferencias actualizadas'));
            $Log->writeLog();
        } catch (SPException $e) {
            $Log->addDescription($e->getMessage());
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            return false;
        }

        return true;
    }
}