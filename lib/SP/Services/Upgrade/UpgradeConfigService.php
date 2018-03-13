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

namespace SP\Services\Upgrade;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Services\Service;
use SP\Util\Util;

/**
 * Class UpgradeService
 *
 * @package SP\Services\Upgrade
 */
class UpgradeConfigService extends Service
{
    /**
     * @var array Versiones actualizables
     */
    const UPGRADES = ['112.4', '130.16020501', '200.17011202'];

    /**
     * Comprueba si es necesario actualizar la configuración.
     *
     * @param int $version con el número de versión actual
     * @returns bool
     */
    public static function needConfigUpgrade($version)
    {
        return Util::checkVersion($version, self::UPGRADES);
    }

    /**
     * Migrar valores de configuración.
     *
     * @param int $version El número de versión
     * @return bool
     */
    public function upgradeConfig($version)
    {
        $message = EventMessage::factory()->addDescription(__u('Actualizar Configuración'));

        $this->eventDispatcher->notifyEvent('upgrade.config.start', new Event($this, $message));

        $configData = $this->config->getConfigData();
        $count = 0;

        foreach (self::UPGRADES as $upgradeVersion) {
            if (Util::checkVersion($version, $upgradeVersion)) {
                switch ($upgradeVersion) {
                    case '200.17011202':
                        $message->addDetail(__u('Versión'), $version);

                        $configData->setSiteTheme('material-blue');
                        $configData->setConfigVersion($upgradeVersion);

                        $this->config->saveConfig($configData, false);
                        $count++;
                        break;
                }
            }
        }

        $this->eventDispatcher->notifyEvent('upgrade.config.end', new Event($this, $message));

        return $count > 0;
    }

    /**
     * Actualizar el archivo de configuración a formato XML
     *
     * @param $version
     * @return bool
     */
    public function upgradeOldConfigFile($version)
    {
        $message = EventMessage::factory()->addDescription(__u('Actualizar Configuración'));

        $this->eventDispatcher->notifyEvent('upgrade.config.old.start', new Event($this, $message));

        // Include the file, save the data from $CONFIG
        include OLD_CONFIG_FILE;

        $configData = $this->config->getConfigData();
        $message = EventMessage::factory();

        if (isset($CONFIG) && is_array($CONFIG)) {
            $paramMapper = function ($mapFrom, $mapTo) use ($CONFIG, $message, $configData) {
                if (isset($CONFIG[$mapFrom])) {
                    $message->addDetail(__u('Parámetro'), $mapFrom);
                    $configData->{$mapTo}($CONFIG[$mapFrom]);
                }
            };

            foreach (self::getConfigParams() as $mapTo => $mapFrom) {
                if (method_exists($configData, $mapTo)) {
                    if (is_array($mapFrom)) {
                        /** @var array $mapFrom */
                        foreach ($mapFrom as $param) {
                            $paramMapper($mapFrom, $param);
                        }
                    } else {
                        if (isset($CONFIG[$mapFrom])) {
                            $paramMapper($mapFrom, $mapTo);
                        }
                    }
                }
            }
        }

        $oldFile = OLD_CONFIG_FILE . '.old.' . time();

        try {
            $configData->setSiteTheme('material-blue');
            $configData->setConfigVersion($version);

            $this->config->saveConfig($configData, false);

            rename(OLD_CONFIG_FILE, $oldFile);

            $message->addDetail(__u('Versión'), $version);

            $this->eventDispatcher->notifyEvent('upgrade.config.old.end', new Event($this, $message));

            return true;
        } catch (\Exception $e) {
            $this->eventDispatcher->notifyEvent('exception',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error al actualizar la configuración'))
                    ->addDetail(__u('Archivo'), $oldFile))
            );
        }

        // We are here...wrong
        return false;
    }

    /**
     * Devuelve array de métodos y parámetros de configuración
     *
     * @return array
     */
    private static function getConfigParams()
    {
        return [
            'setAccountCount' => 'account_count',
            'setAccountLink' => 'account_link',
            'setCheckUpdates' => 'checkupdates',
            'setCheckNotices' => 'checknotices',
            'setDbHost' => 'dbhost',
            'setDbName' => 'dbname',
            'setDbPass' => 'dbpass',
            'setDbUser' => 'dbuser',
            'setDebug' => 'debug',
            'setDemoEnabled' => 'demo_enabled',
            'setGlobalSearch' => 'globalsearch',
            'setInstalled' => 'installed',
            'setMaintenance' => 'maintenance',
            'setPasswordSalt' => 'passwordsalt',
            'setSessionTimeout' => 'session_timeout',
            'setSiteLang' => 'sitelang',
            'setConfigVersion' => 'version',
            'setConfigHash' => 'config_hash',
            'setProxyEnabled' => 'proxy_enabled',
            'setProxyPass' => 'proxy_pass',
            'setProxyPort' => 'proxy_port',
            'setProxyServer' => 'proxy_server',
            'setProxyUser' => 'proxy_user',
            'setResultsAsCards' => 'resultsascards',
            'setSiteTheme' => 'sitetheme',
            'setAccountPassToImage' => 'account_passtoimage',
            'setFilesAllowedExts' => ['allowed_exts', 'files_allowed_exts'],
            'setFilesAllowedSize' => ['allowed_size', 'files_allowed_size'],
            'setFilesEnabled' => ['filesenabled', 'files_enabled'],
            'setLdapBase' => ['ldapbase', 'ldap_base'],
            'setLdapBindPass' => ['ldapbindpass', 'ldap_bindpass'],
            'setLdapBindUser' => ['ldapbinduser', 'ldap_binduser'],
            'setLdapEnabled' => ['ldapenabled', 'ldap_enabled'],
            'setLdapGroup' => ['ldapgroup', 'ldap_group'],
            'setLdapServer' => ['ldapserver', 'ldap_server'],
            'setLdapAds' => 'ldap_ads',
            'setLdapDefaultGroup' => 'ldap_defaultgroup',
            'setLdapDefaultProfile' => 'ldap_defaultprofile',
            'setLogEnabled' => ['logenabled', 'log_enabled'],
            'setMailEnabled' => ['mailenabled', 'mail_enabled'],
            'setMailFrom' => ['mailfrom', 'mail_from'],
            'setMailPass' => ['mailpass', 'mail_pass'],
            'setMailPort' => ['mailport', 'mail_port'],
            'setMailRequestsEnabled' => ['mailrequestsenabled', 'mail_requestsenabled'],
            'setMailAuthenabled' => 'mail_authenabled',
            'setMailSecurity' => ['mailsecurity', 'mail_security'],
            'setMailServer' => ['mailserver', 'mail_server'],
            'setMailUser' => ['mailuser', 'mail_user'],
            'setWikiEnabled' => ['wikienabled', 'wiki_enabled'],
            'setWikiFilter' => ['wikifilter', 'wiki_filter'],
            'setWikiPageUrl' => ['wikipageurl' . 'wiki_pageurl'],
            'setWikiSearchUrl' => ['wikisearchurl', 'wiki_searchurl']
        ];
    }
}