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

namespace SP\Services\Upgrade;

use Exception;
use SP\Config\ConfigData;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\MimeTypes;
use SP\Providers\Auth\Ldap\LdapTypeInterface;
use SP\Providers\Log\FileLogHandler;
use SP\Services\Service;
use SP\Storage\File\FileException;
use SP\Util\VersionUtil;

/**
 * Class UpgradeService
 *
 * @package SP\Services\Upgrade
 */
final class UpgradeConfigService extends Service implements UpgradeInterface
{
    /**
     * @var array Versiones actualizables
     */
    const UPGRADES = [
        '112.4',
        '130.16020501',
        '200.17011202',
        '300.18111001',
        '300.18112501'
    ];
    /**
     * @var ConfigData
     */
    protected $configData;

    /**
     * @param $version
     *
     * @return bool
     */
    public static function needsUpgrade($version)
    {
        return VersionUtil::checkVersion($version, self::UPGRADES);
    }

    /**
     * Actualizar el archivo de configuración a formato XML
     *
     * @param $version
     *
     * @throws UpgradeException
     */
    public function upgradeOldConfigFile($version)
    {
        $configData = $this->config->getConfigData();

        $message = EventMessage::factory()->addDescription(__u('Update Configuration'));

        $this->eventDispatcher->notifyEvent('upgrade.config.old.start', new Event($this, $message));

        // Include the file, save the data from $CONFIG
        include OLD_CONFIG_FILE;

        $message = EventMessage::factory();

        if (isset($CONFIG) && is_array($CONFIG)) {
            $paramMapper = function ($mapFrom, $mapTo) use ($CONFIG, $message, $configData) {
                if (isset($CONFIG[$mapFrom])) {
                    $message->addDetail(__u('Parameter'), $mapFrom);
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

            $message->addDetail(__u('Version'), $version);

            $this->eventDispatcher->notifyEvent('upgrade.config.old.end', new Event($this, $message));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error while updating the configuration'))
                    ->addDetail(__u('File'), $oldFile))
            );

            throw new UpgradeException(__u('Error while updating the configuration'));
        }
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

    /**
     * Migrar valores de configuración.
     *
     * @param            $version
     * @param ConfigData $configData
     *
     * @throws FileException
     */
    public function upgrade($version, ConfigData $configData)
    {
        $this->configData = $configData;

        $message = EventMessage::factory()->addDescription(__u('Update Configuration'));
        $this->eventDispatcher->notifyEvent('upgrade.config.start', new Event($this, $message));

        foreach (self::UPGRADES as $upgradeVersion) {
            if (VersionUtil::checkVersion($version, $upgradeVersion)) {
                $this->applyUpgrade($upgradeVersion);
            }
        }

        $this->eventDispatcher->notifyEvent('upgrade.config.end', new Event($this, $message));
    }

    /**
     * @param $version
     *
     * @throws FileException
     */
    private function applyUpgrade($version)
    {
        switch ($version) {
            case '200.17011202':
                $this->upgrade_200_17011202($version);
                break;
            case '300.18111001':
                $this->upgrade_300_18111001($version);
                break;
            case '300.18112501':
                $this->upgrade_300_18112501($version);
                break;
        }
    }

    /**
     * @param $version
     *
     * @throws FileException
     */
    private function upgrade_200_17011202($version)
    {
        $this->configData->setSiteTheme('material-blue');
        $this->configData->setConfigVersion($version);

        $this->config->saveConfig($this->configData, false);

        $this->eventDispatcher->notifyEvent('upgrade.config.process',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Update Configuration'))
                ->addDetail(__u('Version'), $version))
        );
    }

    /**
     * @param $version
     *
     * @throws FileException
     */
    private function upgrade_300_18111001($version)
    {
        $extensions = array_map('strtolower', $this->configData->getFilesAllowedExts());
        $mimeTypes = $this->dic->get(MimeTypes::class)->getMimeTypes();
        $configMimeTypes = [];

        foreach ($extensions as $extension) {
            $exists = false;

            foreach ($mimeTypes as $mimeType) {
                if (strtolower($mimeType['extension']) === $extension) {
                    $configMimeTypes[] = $mimeType['type'];
                    $exists = true;

                    $this->eventDispatcher->notifyEvent('upgrade.config.process',
                        new Event($this, EventMessage::factory()
                            ->addDescription(__u('MIME type set for this extension'))
                            ->addDetail(__u('MIME type'), $mimeType['type'])
                            ->addDetail(__u('Extension'), $extension))
                    );
                }
            }

            if (!$exists) {
                $this->eventDispatcher->notifyEvent('upgrade.config.process',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('MIME type not found for this extension'))
                        ->addDetail(__u('Extension'), $extension))
                );
            }
        }

        $this->configData->setFilesAllowedMime($configMimeTypes);
        $this->configData->setConfigVersion($version);

        $this->config->saveConfig($this->configData, false);

        $this->eventDispatcher->notifyEvent('upgrade.config.process',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Update Configuration'))
                ->addDetail(__u('Version'), $version))
        );
    }

    /**
     * @param $version
     *
     * @throws FileException
     */
    private function upgrade_300_18112501($version)
    {
        if ($this->configData->isLdapEnabled()) {
            if ($this->configData->isLdapAds()) {
                $this->configData->setLdapType(LdapTypeInterface::LDAP_ADS);
            } else {
                $this->configData->setLdapType(LdapTypeInterface::LDAP_STD);
            }

            $this->configData->setConfigVersion($version);

            $this->config->saveConfig($this->configData, false);

            $this->eventDispatcher->notifyEvent('upgrade.config.process',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Update Configuration'))
                    ->addDetail(__u('Version'), $version))
            );
        }
    }

    /**
     * initialize
     */
    protected function initialize()
    {
        $this->eventDispatcher->attach($this->dic->get(FileLogHandler::class));
    }
}