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

namespace SP\Config;

use JsonSerializable;
use SP\Core\DataCollection;

/**
 * Class configData
 *
 * @package SP\Config
 */
final class ConfigData extends DataCollection implements JsonSerializable
{
    const PUBLIC_LINK_MAX_VIEWS = 3;
    const PUBLIC_LINK_MAX_TIME = 600;
    const ACCOUNT_COUNT = 12;
    const DB_PORT = 3306;
    const FILES_ALLOWED_SIZE = 1024;
    const MAIL_PORT = 587;
    const SESSION_TIMEOUT = 300;
    const SITE_THEME = 'material-blue';
    const SYSLOG_PORT = 514;
    const ACCOUNT_EXPIRE_TIME = 10368000;

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return array
     */
    public function getLogEvents(): array
    {
        return $this->get('logEvents', []);
    }

    /**
     * @param array $logEvents
     */
    public function setLogEvents($logEvents)
    {
        $this->set('logEvents', $logEvents ?: []);
    }

    /**
     * @return boolean
     */
    public function isDokuwikiEnabled(): bool
    {
        return $this->get('dokuwikiEnabled', false);
    }

    /**
     * @param boolean $dokuwikiEnabled
     *
     * @return $this
     */
    public function setDokuwikiEnabled($dokuwikiEnabled)
    {
        $this->set('dokuwikiEnabled', (bool)$dokuwikiEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUrl()
    {
        return $this->get('dokuwikiUrl');
    }

    /**
     * @param string $dokuwikiUrl
     *
     * @return $this
     */
    public function setDokuwikiUrl($dokuwikiUrl)
    {
        $this->set('dokuwikiUrl', $dokuwikiUrl);

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUrlBase()
    {
        return $this->get('dokuwikiUrlBase');
    }

    /**
     * @param string $dokuwikiUrlBase
     *
     * @return $this
     */
    public function setDokuwikiUrlBase($dokuwikiUrlBase)
    {
        $this->set('dokuwikiUrlBase', $dokuwikiUrlBase);

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUser()
    {
        return $this->get('dokuwikiUser');
    }

    /**
     * @param string $dokuwikiUser
     *
     * @return $this
     */
    public function setDokuwikiUser($dokuwikiUser)
    {
        $this->set('dokuwikiUser', $dokuwikiUser);

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiPass()
    {
        return $this->get('dokuwikiPass');
    }

    /**
     * @param string $dokuwikiPass
     *
     * @return $this
     */
    public function setDokuwikiPass($dokuwikiPass)
    {
        $this->set('dokuwikiPass', $dokuwikiPass);

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiNamespace()
    {
        return $this->get('dokuwikiNamespace');
    }

    /**
     * @param string $dokuwikiNamespace
     *
     * @return $this
     */
    public function setDokuwikiNamespace($dokuwikiNamespace)
    {
        $this->set('dokuwikiNamespace', $dokuwikiNamespace);

        return $this;
    }

    /**
     * @return int
     */
    public function getLdapDefaultGroup(): int
    {
        return (int)$this->get('ldapDefaultGroup');
    }

    /**
     * @param int $ldapDefaultGroup
     *
     * @return $this
     */
    public function setLdapDefaultGroup($ldapDefaultGroup)
    {
        $this->set('ldapDefaultGroup', (int)$ldapDefaultGroup);

        return $this;
    }

    /**
     * @return int
     */
    public function getLdapDefaultProfile(): int
    {
        return (int)$this->get('ldapDefaultProfile');
    }

    /**
     * @param int $ldapDefaultProfile
     *
     * @return $this
     */
    public function setLdapDefaultProfile($ldapDefaultProfile)
    {
        $this->set('ldapDefaultProfile', (int)$ldapDefaultProfile);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isProxyEnabled()
    {
        return $this->get('proxyEnabled', false);
    }

    /**
     * @param boolean $proxyEnabled
     *
     * @return $this
     */
    public function setProxyEnabled($proxyEnabled)
    {
        $this->set('proxyEnabled', (bool)$proxyEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyServer()
    {
        return $this->get('proxyServer');
    }

    /**
     * @param string $proxyServer
     *
     * @return $this
     */
    public function setProxyServer($proxyServer)
    {
        $this->set('proxyServer', $proxyServer);

        return $this;
    }

    /**
     * @return int
     */
    public function getProxyPort()
    {
        return $this->get('proxyPort', 8080);
    }

    /**
     * @param int $proxyPort
     *
     * @return $this
     */
    public function setProxyPort($proxyPort)
    {
        $this->set('proxyPort', (int)$proxyPort);

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyUser()
    {
        return $this->get('proxyUser');
    }

    /**
     * @param string $proxyUser
     *
     * @return $this
     */
    public function setProxyUser($proxyUser)
    {
        $this->set('proxyUser', $proxyUser);

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyPass()
    {
        return $this->get('proxyPass');
    }

    /**
     * @param string $proxyPass
     *
     * @return $this
     */
    public function setProxyPass($proxyPass)
    {
        $this->set('proxyPass', $proxyPass);

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxViews()
    {
        return $this->get('publinksMaxViews', self::PUBLIC_LINK_MAX_VIEWS);
    }


    /**
     * @param int $publinksMaxViews
     *
     * @return $this
     */
    public function setPublinksMaxViews($publinksMaxViews)
    {
        $this->set('publinksMaxViews', (int)$publinksMaxViews);

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxTime()
    {
        return $this->get('publinksMaxTime', self::PUBLIC_LINK_MAX_TIME);
    }

    /**
     * @param int $publinksMaxTime
     *
     * @return $this
     */
    public function setPublinksMaxTime($publinksMaxTime)
    {
        $this->set('publinksMaxTime', (int)$publinksMaxTime);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogEnabled()
    {
        return $this->get('syslogEnabled', false);
    }

    /**
     * @param boolean $syslogEnabled
     *
     * @return $this
     */
    public function setSyslogEnabled($syslogEnabled)
    {
        $this->set('syslogEnabled', (bool)$syslogEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogRemoteEnabled()
    {
        return $this->get('syslogRemoteEnabled', false);
    }

    /**
     * @param boolean $syslogRemoteEnabled
     *
     * @return $this
     */
    public function setSyslogRemoteEnabled($syslogRemoteEnabled)
    {
        $this->set('syslogRemoteEnabled', (bool)$syslogRemoteEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getSyslogServer()
    {
        return $this->get('syslogServer');
    }

    /**
     * @param string $syslogServer
     *
     * @return $this
     */
    public function setSyslogServer($syslogServer)
    {
        $this->set('syslogServer', $syslogServer);

        return $this;
    }

    /**
     * @return int
     */
    public function getSyslogPort()
    {
        return $this->get('syslogPort', self::SYSLOG_PORT);
    }

    /**
     * @param int $syslogPort
     *
     * @return $this
     */
    public function setSyslogPort($syslogPort)
    {
        $this->set('syslogPort', (int)$syslogPort);

        return $this;
    }

    /**
     * @return string
     */
    public function getBackupHash()
    {
        return $this->get('backup_hash');
    }

    /**
     * @param string $backup_hash
     *
     * @return $this
     */
    public function setBackupHash($backup_hash)
    {
        $this->set('backup_hash', $backup_hash);

        return $this;
    }

    /**
     * @return string
     */
    public function getExportHash()
    {
        return $this->get('export_hash');
    }

    /**
     * @param string $export_hash
     *
     * @return $this
     */
    public function setExportHash($export_hash)
    {
        $this->set('export_hash', $export_hash);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBindUser()
    {
        return $this->get('ldapBindUser');
    }

    /**
     * @param string $ldapBindUser
     *
     * @return $this
     */
    public function setLdapBindUser($ldapBindUser)
    {
        $this->set('ldapBindUser', $ldapBindUser);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapProxyUser()
    {
        return $this->get('ldapProxyUser');
    }

    /**
     * @param string $ldapProxyUser
     *
     * @return $this
     */
    public function setLdapProxyUser($ldapProxyUser)
    {
        $this->get('ldapProxyUser', $ldapProxyUser);

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountCount()
    {
        return $this->get('accountCount', self::ACCOUNT_COUNT);
    }

    /**
     * @param int $accountCount
     *
     * @return $this
     */
    public function setAccountCount($accountCount)
    {
        $this->set('accountCount', (int)$accountCount);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccountLink()
    {
        return $this->get('accountLink', true);
    }

    /**
     * @param boolean $accountLink
     *
     * @return $this
     */
    public function setAccountLink($accountLink)
    {
        $this->set('accountLink', (bool)$accountLink);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCheckUpdates()
    {
        return $this->get('checkUpdates', false);
    }

    /**
     * @param boolean $checkUpdates
     *
     * @return $this
     */
    public function setCheckUpdates($checkUpdates)
    {
        $this->set('checkUpdates', (bool)$checkUpdates);

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigHash()
    {
        return $this->get('configHash');
    }

    /**
     * Generates a hash from current config options
     */
    public function setConfigHash()
    {
        $this->set('configHash', sha1(serialize($this->attributes)));

        return $this;
    }

    /**
     * @return string
     */
    public function getDbHost()
    {
        return $this->get('dbHost');
    }

    /**
     * @param string $dbHost
     *
     * @return $this
     */
    public function setDbHost($dbHost)
    {
        $this->set('dbHost', $dbHost);

        return $this;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->get('dbName');
    }

    /**
     * @param string $dbName
     *
     * @return $this
     */
    public function setDbName($dbName)
    {
        $this->set('dbName', $dbName);

        return $this;
    }

    /**
     * @return string
     */
    public function getDbPass()
    {
        return $this->get('dbPass');
    }

    /**
     * @param string $dbPass
     *
     * @return $this
     */
    public function setDbPass($dbPass)
    {
        $this->set('dbPass', $dbPass);

        return $this;
    }

    /**
     * @return string
     */
    public function getDbUser()
    {
        return $this->get('dbUser');
    }

    /**
     * @param string $dbUser
     *
     * @return $this
     */
    public function setDbUser($dbUser)
    {
        $this->set('dbUser', $dbUser);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->get('debug', false);
    }

    /**
     * @param boolean $debug
     *
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->set('debug', (bool)$debug);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDemoEnabled()
    {
        return $this->get('demoEnabled', false);
    }

    /**
     * @param boolean $demoEnabled
     *
     * @return $this
     */
    public function setDemoEnabled($demoEnabled)
    {
        $this->set('demoEnabled', (bool)$demoEnabled);

        return $this;
    }

    /**
     * @return array
     */
    public function getFilesAllowedExts(): array
    {
        return $this->get('filesAllowedExts', []);
    }

    /**
     * @return int
     */
    public function getFilesAllowedSize()
    {
        return $this->get('filesAllowedSize', self::FILES_ALLOWED_SIZE);
    }

    /**
     * @param int $filesAllowedSize
     *
     * @return $this
     */
    public function setFilesAllowedSize($filesAllowedSize)
    {
        $this->set('filesAllowedSize', (int)$filesAllowedSize);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFilesEnabled()
    {
        return $this->get('filesEnabled', true);
    }

    /**
     * @param boolean $filesEnabled
     *
     * @return $this
     */
    public function setFilesEnabled($filesEnabled)
    {
        $this->set('filesEnabled', (bool)$filesEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGlobalSearch()
    {
        return $this->get('globalSearch', true);
    }

    /**
     * @param boolean $globalSearch
     *
     * @return $this
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->set('globalSearch', (bool)$globalSearch);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->get('installed', false);
    }

    /**
     * @param boolean $installed
     *
     * @return $this
     */
    public function setInstalled($installed)
    {
        $this->set('installed', (bool)$installed);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBase()
    {
        return $this->get('ldapBase');
    }

    /**
     * @param string $ldapBase
     *
     * @return $this
     */
    public function setLdapBase($ldapBase)
    {
        $this->set('ldapBase', $ldapBase);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLdapEnabled()
    {
        return $this->get('ldapEnabled', false);
    }

    /**
     * @param boolean $ldapEnabled
     *
     * @return $this
     */
    public function setLdapEnabled($ldapEnabled)
    {
        $this->set('ldapEnabled', (bool)$ldapEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapGroup()
    {
        return $this->get('ldapGroup');
    }

    /**
     * @param string $ldapGroup
     *
     * @return $this
     */
    public function setLdapGroup($ldapGroup)
    {
        $this->set('ldapGroup', $ldapGroup);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapServer()
    {
        return $this->get('ldapServer');
    }

    /**
     * @param string $ldapServer
     *
     * @return $this
     */
    public function setLdapServer($ldapServer)
    {
        $this->set('ldapServer', $ldapServer);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLogEnabled()
    {
        return $this->get('logEnabled', true);
    }

    /**
     * @param boolean $logEnabled
     *
     * @return $this
     */
    public function setLogEnabled($logEnabled)
    {
        $this->set('logEnabled', (bool)$logEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailAuthenabled()
    {
        return $this->get('mailAuthenabled', false);
    }

    /**
     * @param boolean $mailAuthenabled
     *
     * @return $this
     */
    public function setMailAuthenabled($mailAuthenabled)
    {
        $this->set('mailAuthenabled', (bool)$mailAuthenabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailEnabled()
    {
        return $this->get('mailEnabled', false);
    }

    /**
     * @param boolean $mailEnabled
     *
     * @return $this
     */
    public function setMailEnabled($mailEnabled)
    {
        $this->set('mailEnabled', (bool)$mailEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getMailFrom()
    {
        return $this->get('mailFrom');
    }

    /**
     * @param string $mailFrom
     *
     * @return $this
     */
    public function setMailFrom($mailFrom)
    {
        $this->set('mailFrom', $mailFrom);

        return $this;
    }

    /**
     * @return string
     */
    public function getMailPass()
    {
        return $this->get('mailPass');
    }

    /**
     * @param string $mailPass
     *
     * @return $this
     */
    public function setMailPass($mailPass)
    {
        $this->set('mailPass', $mailPass);

        return $this;
    }

    /**
     * @return int
     */
    public function getMailPort()
    {
        return $this->get('mailPort', self::MAIL_PORT);
    }

    /**
     * @param int $mailPort
     *
     * @return $this
     */
    public function setMailPort($mailPort)
    {
        $this->set('mailPort', (int)$mailPort);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailRequestsEnabled()
    {
        return $this->get('mailRequestsEnabled', false);
    }

    /**
     * @param boolean $mailRequestsEnabled
     *
     * @return $this
     */
    public function setMailRequestsEnabled($mailRequestsEnabled)
    {
        $this->set('mailRequestsEnabled', (bool)$mailRequestsEnabled);

        return $this;
    }

    /**
     * @return string
     */
    public function getMailSecurity()
    {
        return $this->get('mailSecurity');
    }

    /**
     * @param string $mailSecurity
     *
     * @return $this
     */
    public function setMailSecurity($mailSecurity)
    {
        $this->set('mailSecurity', $mailSecurity);

        return $this;
    }

    /**
     * @return string
     */
    public function getMailServer()
    {
        return $this->get('mailServer');
    }

    /**
     * @param string $mailServer
     *
     * @return $this
     */
    public function setMailServer($mailServer)
    {
        $this->set('mailServer', $mailServer);

        return $this;
    }

    /**
     * @return string
     */
    public function getMailUser()
    {
        return $this->get('mailUser');
    }

    /**
     * @param string $mailUser
     *
     * @return $this
     */
    public function setMailUser($mailUser)
    {
        $this->set('mailUser', $mailUser);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMaintenance()
    {
        return $this->get('maintenance', false);
    }

    /**
     * @param boolean $maintenance
     *
     * @return $this
     */
    public function setMaintenance($maintenance)
    {
        $this->set('maintenance', (bool)$maintenance);

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordSalt()
    {
        return $this->get('passwordSalt');
    }

    /**
     * @param string $passwordSalt
     *
     * @return $this
     */
    public function setPasswordSalt($passwordSalt)
    {
        $this->set('passwordSalt', $passwordSalt);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isResultsAsCards()
    {
        return $this->get('resultsAsCards', false);
    }

    /**
     * @param boolean $resultsAsCards
     *
     * @return $this
     */
    public function setResultsAsCards($resultsAsCards)
    {
        $this->set('resultsAsCards', (bool)$resultsAsCards);

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionTimeout()
    {
        return $this->get('sessionTimeout', self::SESSION_TIMEOUT);
    }

    /**
     * @param int $sessionTimeout
     *
     * @return $this
     */
    public function setSessionTimeout($sessionTimeout)
    {
        $this->set('sessionTimeout', (int)$sessionTimeout);

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteLang()
    {
        return $this->get('siteLang');
    }

    /**
     * @param string $siteLang
     *
     * @return $this
     */
    public function setSiteLang($siteLang)
    {
        $this->set('siteLang', $siteLang);

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteTheme()
    {
        return $this->get('siteTheme', self::SITE_THEME);
    }

    /**
     * @param string $siteTheme
     *
     * @return $this
     */
    public function setSiteTheme($siteTheme)
    {
        $this->set('siteTheme', $siteTheme);

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigVersion()
    {
        return (string)$this->get('configVersion');
    }

    /**
     * @param string $configVersion
     *
     * @return $this
     */
    public function setConfigVersion($configVersion)
    {
        $this->set('configVersion', $configVersion);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isWikiEnabled()
    {
        return $this->get('wikiEnabled', false);
    }

    /**
     * @param boolean $wikiEnabled
     *
     * @return $this
     */
    public function setWikiEnabled($wikiEnabled)
    {
        $this->set('wikiEnabled', (bool)$wikiEnabled);

        return $this;
    }

    /**
     * @return array
     */
    public function getWikiFilter()
    {
        return $this->get('wikiFilter', []);
    }

    /**
     * @param array $wikiFilter
     *
     * @return $this
     */
    public function setWikiFilter($wikiFilter)
    {
        $this->set('wikiFilter', $wikiFilter ?: []);

        return $this;
    }

    /**
     * @return string
     */
    public function getWikiPageurl()
    {
        return $this->get('wikiPageurl');
    }

    /**
     * @param string $wikiPageurl
     *
     * @return $this
     */
    public function setWikiPageurl($wikiPageurl)
    {
        $this->set('wikiPageurl', $wikiPageurl);

        return $this;
    }

    /**
     * @return string
     */
    public function getWikiSearchurl()
    {
        return $this->get('wikiSearchurl');
    }

    /**
     * @param string $wikiSearchurl
     *
     * @return $this
     */
    public function setWikiSearchurl($wikiSearchurl)
    {
        $this->set('wikiSearchurl', $wikiSearchurl);

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBindPass()
    {
        return $this->get('ldapBindPass');
    }

    /**
     * @param string $ldapBindPass
     *
     * @return $this
     */
    public function setLdapBindPass($ldapBindPass)
    {
        $this->set('ldapBindPass', $ldapBindPass);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublinksImageEnabled()
    {
        return $this->get('publinksImageEnabled', false);
    }

    /**
     * @param boolean $publinksImageEnabled
     *
     * @return $this
     */
    public function setPublinksImageEnabled($publinksImageEnabled)
    {
        $this->set('publinksImageEnabled', (bool)$publinksImageEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isHttpsEnabled()
    {
        return $this->get('httpsEnabled', false);
    }

    /**
     * @param boolean $httpsEnabled
     *
     * @return $this
     */
    public function setHttpsEnabled($httpsEnabled)
    {
        $this->set('httpsEnabled', (bool)$httpsEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isChecknotices()
    {
        return $this->get('checkNotices', false);
    }

    /**
     * @param boolean $checknotices
     *
     * @return $this
     */
    public function setCheckNotices($checknotices)
    {
        $this->set('checkNotices', $checknotices);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccountPassToImage()
    {
        return $this->get('accountPassToImage', false);
    }

    /**
     * @param boolean $accountPassToImage
     *
     * @return $this
     */
    public function setAccountPassToImage($accountPassToImage)
    {
        $this->set('accountPassToImage', (bool)$accountPassToImage);

        return $this;
    }

    /**
     * @return string
     */
    public function getUpgradeKey()
    {
        return $this->get('upgradeKey');
    }

    /**
     * @param string $upgradeKey
     *
     * @return $this
     */
    public function setUpgradeKey($upgradeKey)
    {
        $this->set('upgradeKey', $upgradeKey);

        return $this;
    }

    /**
     * @return int
     */
    public function getDbPort()
    {
        return $this->get('dbPort', self::DB_PORT);
    }

    /**
     * @param int $dbPort
     *
     * @return $this
     */
    public function setDbPort($dbPort)
    {
        $this->set('dbPort', (int)$dbPort);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublinksEnabled()
    {
        return $this->get('publinksEnabled', false);
    }

    /**
     * @param boolean $publinksEnabled
     *
     * @return $this
     */
    public function setPublinksEnabled($publinksEnabled)
    {
        $this->set('publinksEnabled', (bool)$publinksEnabled);

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->attributes;
    }

    /**
     * @return string
     */
    public function getConfigSaver()
    {
        return $this->get('configSaver');
    }

    /**
     * @param string $configSaver
     *
     * @return $this
     */
    public function setConfigSaver($configSaver)
    {
        $this->set('configSaver', $configSaver);

        return $this;
    }

    /**
     * @return string
     */
    public function getDbSocket()
    {
        return $this->get('dbSocket');
    }

    /**
     * @param string $dbSocket
     */
    public function setDbSocket($dbSocket)
    {
        $this->set('dbSocket', $dbSocket);
    }

    /**
     * @return bool
     */
    public function isEncryptSession()
    {
        return (bool)$this->get('encryptSession', false);
    }

    /**
     * @param bool $encryptSession
     *
     * @return $this
     */
    public function setEncryptSession($encryptSession)
    {
        $this->set('encryptSession', (bool)$encryptSession);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountFullGroupAccess()
    {
        return (bool)$this->get('accountFullGroupAccess', false);
    }

    /**
     * @param bool $accountFullGroupAccess
     *
     * @return $this
     */
    public function setAccountFullGroupAccess($accountFullGroupAccess)
    {
        $this->set('accountFullGroupAccess', (bool)$accountFullGroupAccess);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthBasicEnabled()
    {
        return (bool)$this->get('authBasicEnabled', true);
    }

    /**
     * @param bool $authBasicEnabled
     */
    public function setAuthBasicEnabled($authBasicEnabled)
    {
        $this->set('authBasicEnabled', $authBasicEnabled);
    }

    /**
     * @return string
     */
    public function getAuthBasicDomain()
    {
        return $this->get('authBasicDomain');
    }

    /**
     * @param string $authBasicDomain
     */
    public function setAuthBasicDomain($authBasicDomain)
    {
        $this->set('authBasicDomain', $authBasicDomain);
    }

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled()
    {
        return (bool)$this->get('authBasicAutoLoginEnabled', true);
    }

    /**
     * @param bool $authBasicAutoLoginEnabled
     */
    public function setAuthBasicAutoLoginEnabled($authBasicAutoLoginEnabled)
    {
        $this->set('authBasicAutoLoginEnabled', $authBasicAutoLoginEnabled);
    }

    /**
     * @return int
     */
    public function getSsoDefaultGroup()
    {
        return $this->get('ssoDefaultGroup');
    }

    /**
     * @param int $ssoDefaultGroup
     */
    public function setSsoDefaultGroup($ssoDefaultGroup)
    {
        $this->set('ssoDefaultGroup', $ssoDefaultGroup);
    }

    /**
     * @return int
     */
    public function getSsoDefaultProfile()
    {
        return $this->get('ssoDefaultProfile');
    }

    /**
     * @param int $ssoDefaultProfile
     */
    public function setSsoDefaultProfile($ssoDefaultProfile)
    {
        $this->set('ssoDefaultProfile', $ssoDefaultProfile);
    }

    /**
     * @return array
     */
    public function getMailRecipients()
    {
        return $this->get('mailRecipients', []);
    }

    /**
     * @param array $mailRecipients
     */
    public function setMailRecipients($mailRecipients)
    {
        $this->set('mailRecipients', $mailRecipients ?: []);
    }

    /**
     * @return array
     */
    public function getMailEvents()
    {
        return $this->get('mailEvents', []);
    }

    /**
     * @param array $mailEvents
     */
    public function setMailEvents($mailEvents)
    {
        $this->set('mailEvents', $mailEvents ?: []);
    }

    /**
     * @return string
     */
    public function getDatabaseVersion()
    {
        return (string)$this->get('databaseVersion');
    }

    /**
     * @param string $databaseVersion
     *
     * @return ConfigData
     */
    public function setDatabaseVersion($databaseVersion)
    {
        $this->set('databaseVersion', $databaseVersion);

        return $this;
    }

    /**
     * @return int
     */
    public function getConfigDate()
    {
        return (int)$this->get('configDate');
    }

    /**
     * @param int $configDate
     *
     * @return $this
     */
    public function setConfigDate($configDate)
    {
        $this->set('configDate', (int)$configDate);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountExpireEnabled()
    {
        return (bool)$this->get('accountExpireEnabled', false);
    }

    /**
     * @param bool $accountExpireEnabled
     *
     * @return ConfigData
     */
    public function setAccountExpireEnabled($accountExpireEnabled)
    {
        $this->set('accountExpireEnabled', $accountExpireEnabled);

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountExpireTime()
    {
        return $this->get('accountExpireTime', self::ACCOUNT_EXPIRE_TIME);
    }

    /**
     * @param int $accountExpireTime
     *
     * @return ConfigData
     */
    public function setAccountExpireTime($accountExpireTime)
    {
        $this->set('accountExpireTime', (int)$accountExpireTime);

        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapTlsEnabled(): bool
    {
        return $this->get('ldapTlsEnabled', false);
    }

    /**
     * @param bool $ldapTlsEnabled
     */
    public function setLdapTlsEnabled(bool $ldapTlsEnabled)
    {
        $this->set('ldapTlsEnabled', (int)$ldapTlsEnabled);
    }

    /**
     * @return array
     */
    public function getFilesAllowedMime(): array
    {
        return $this->get('filesAllowedMime', []);
    }

    /**
     * @param array $filesAllowedMime
     */
    public function setFilesAllowedMime($filesAllowedMime)
    {
        $this->set('filesAllowedMime', $filesAllowedMime ?: []);
    }

    /**
     * @return int
     */
    public function getLdapType()
    {
        return (int)$this->get('ldapType');
    }

    /**
     * @param int $ldapType
     */
    public function setLdapType($ldapType)
    {
        $this->set('ldapType', (int)$ldapType);
    }

    /**
     * @return string
     */
    public function getAppVersion()
    {
        return $this->get('appVersion');
    }

    /**
     * @param string $appVersion
     */
    public function setAppVersion(string $appVersion)
    {
        $this->set('appVersion', $appVersion);
    }

    /**
     * @return string
     */
    public function getApplicationUrl()
    {
        return $this->get('applicationUrl');
    }

    /**
     * @param string $applicationUrl
     */
    public function setApplicationUrl(string $applicationUrl = null)
    {
        $this->set('applicationUrl', $applicationUrl ? rtrim($applicationUrl, '/') : null);
    }

    /**
     * @return string
     */
    public function getLdapFilterUserObject()
    {
        return $this->get('ldapFilterUserObject');
    }

    /**
     * @param string $filter
     */
    public function setLdapFilterUserObject($filter)
    {
        $this->set('ldapFilterUserObject', $filter);
    }

    /**
     * @return string
     */
    public function getLdapFilterGroupObject()
    {
        return $this->get('ldapFilterGroupObject');
    }

    /**
     * @param string $filter
     */
    public function setLdapFilterGroupObject($filter)
    {
        $this->set('ldapFilterGroupObject', $filter);
    }

    /**
     * @return array|null
     */
    public function getLdapFilterUserAttributes()
    {
        return $this->get('ldapFilterUserAttributes');
    }

    /**
     * @param array $attributes
     */
    public function setLdapFilterUserAttributes($attributes)
    {
        $this->set('ldapFilterUserAttributes', $attributes ?: []);
    }

    /**
     * @return array|null
     */
    public function getLdapFilterGroupAttributes()
    {
        return $this->get('ldapFilterGroupAttributes');
    }

    /**
     * @param array $attributes
     */
    public function setLdapFilterGroupAttributes($attributes)
    {
        $this->set('ldapFilterGroupAttributes', $attributes ?: []);
    }

    /**
     * @return bool
     */
    public function isLdapDatabaseEnabled(): bool
    {
        return $this->get('ldapDatabaseEnabled', true);
    }

    /**
     * @param bool $ldapDatabaseEnabled
     */
    public function setLdapDatabaseEnabled(bool $ldapDatabaseEnabled)
    {
        $this->set('ldapDatabaseEnabled', (int)$ldapDatabaseEnabled);
    }

}
