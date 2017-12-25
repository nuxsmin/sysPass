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

use Plugins\Authenticator\AuthenticatorData;
use Plugins\Authenticator\AuthenticatorPlugin;
use SP\Bootstrap;
use SP\Config\ConfigData;
use SP\Core\Exceptions\SPException;
use SP\Core\Plugin\PluginUtil;
use SP\DataModel\PluginData;
use SP\DataModel\UserData;
use SP\DataModel\UserPreferencesData;
use SP\Log\Log;
use SP\Mgmt\Plugins\Plugin;
use SP\Util\Util;

/**
 * Class UserPreferencesUtil
 *
 * @package SP\Mgmt\Users
 */
class UserPreferencesUtil
{
    /**
     * Migrar las preferencias
     *
     * @return bool
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function migrate()
    {
        $Container = Bootstrap::getDic();
        /** @var ConfigData $ConfigData */
        $ConfigData = $Container->get(ConfigData::class);
        /** @var Log $Log */
        $Log = $Container->get(Log::class);

        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__FUNCTION__);
        $LogMessage->addDescription(__('Actualizando preferencias', false));

        foreach (User::getItem()->getAll() as $User) {
            try {
                $Preferences = $User->getUserPreferences();

                if (!empty($Preferences)) {
                    $LogMessage->addDetails(__('Usuario', false), $User->getUserLogin());

                    /** @var UserPreferencesData $Preferences */
                    $Preferences = Util::unserialize(UserPreferencesData::class, $Preferences, 'SP\UserPreferences');
                    $User->setUserPreferences($Preferences);

                    // FIXME
                    $Preferences->setTheme($ConfigData->getSiteTheme());

                    if ($Preferences->isUse2Fa()) {
                        self::migrateTwoFA($User);

                        $Preferences->setUse2Fa(0);
                    }

                    $Preferences->setUserId($User->getUserId());

                    UserPreferences::getItem($Preferences)->update();
                }
            } catch (SPException $e) {
                $LogMessage->addDescription($e->getMessage());
                $Log->setLogLevel(Log::ERROR);
                $Log->writeLog();
            }
        }

        $LogMessage->addDescription(__('Preferencias actualizadas', false));
        $Log->writeLog();

        return true;
    }

    /**
     * Migrar la función de 2FA a plugin Authenticator
     *
     * @param UserData $UserData
     * @throws \SP\Core\Exceptions\SPException
     */
    protected static function migrateTwoFA(UserData $UserData)
    {
        PluginUtil::loadPlugins();

        /** @var AuthenticatorData $AuthenticatorData */
        $AuthenticatorData = new AuthenticatorData();
        $AuthenticatorData->setUserId($UserData->getUserId());
        $AuthenticatorData->setIV(UserPass::getUserIVById($UserData->getId()));
        $AuthenticatorData->setTwofaEnabled(1);
        $AuthenticatorData->setDate(time());

        $data[$UserData->getUserId()] = $AuthenticatorData;

        $PluginData = new PluginData();
        $PluginData->setPluginName(AuthenticatorPlugin::PLUGIN_NAME);
        $PluginData->setPluginEnabled(1);
        $PluginData->setPluginData(serialize($data));

        Plugin::getItem($PluginData)->update();
    }
}