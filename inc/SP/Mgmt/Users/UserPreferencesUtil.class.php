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
     * @return bool
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public static function migrateTwoFA()
    {
        $Log = new Log(__FUNCTION__);
        $Log->addDescription(_('Actualizando preferencias'));

        $query = /** @lang SQL */
            'SELECT user_id, user_login, user_mIV, user_preferences FROM usrData';

        $Data = new QueryData();
        $Data->setMapClassName(UserData::class);
        $Data->setQuery($query);

        /** @var UserData[] $queryRes */
        $queryRes = DB::getResultsArray($Data);

        $data = [];

        foreach ($queryRes as $user) {
            /** @var UserPreferencesData $UserPreferencesData */
            $UserPreferencesData = unserialize($user->getUserPreferences());

            if ($UserPreferencesData !== false) {
                if (get_class($UserPreferencesData) === '__PHP_Incomplete_Class') {
                    $UserPreferencesData = Util::castToClass(UserPreferencesData::class, $UserPreferencesData);
                }

                if ($UserPreferencesData->isUse2Fa()) {
                    $Authenticator = new Authenticator($user->user_id, $user->user_login, $user->user_mIV);

                    /** @var AuthenticatorData $AuthenticatorData */
                    $AuthenticatorData = new AuthenticatorData();
                    $AuthenticatorData->setUserId($user->user_id);
                    $AuthenticatorData->setIV($Authenticator->getInitializationKey());
                    $AuthenticatorData->setTwofaEnabled(1);
                    $AuthenticatorData->setDate(time());

                    $data[$user->user_id] = $AuthenticatorData;

                    $Log->addDetails(_('Usuario'), $user->user_login);
                }
            }
        }

        if (count($data) > 0) {
            try {
                $PluginData = new PluginData();
                $PluginData->setPluginName(AuthenticatorPlugin::PLUGIN_NAME);
                $PluginData->setPluginEnabled(1);
                $PluginData->setPluginData(serialize($data));

                Plugin::getItem($PluginData)->update();

                $Log->addDescription(_('Preferencias actualizadas'));
                $Log->writeLog();
            } catch (SPException $e) {
                $Log->addDescription(_('Error al actualizar preferencias'));
                $Log->setLogLevel(Log::ERROR);
                $Log->writeLog();
                return false;
            }
        }

        return true;
    }
}