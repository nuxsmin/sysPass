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

/**
 * Class configData
 *
 * @package SP\Config
 */
final class ConfigData implements JsonSerializable
{
    /**
     * @var string
     */
    private $upgradeKey;
    /**
     * @var bool
     */
    private $dokuwikiEnabled = false;
    /**
     * @var string
     */
    private $dokuwikiUrl;
    /**
     * @var string
     */
    private $dokuwikiUrlBase;
    /**
     * @var string
     */
    private $dokuwikiUser;
    /**
     * @var string
     */
    private $dokuwikiPass;
    /**
     * @var string
     */
    private $dokuwikiNamespace;
    /**
     * @var int
     */
    private $ldapDefaultGroup;
    /**
     * @var int
     */
    private $ldapDefaultProfile;
    /**
     * @var bool
     */
    private $proxyEnabled = false;
    /**
     * @var string
     */
    private $proxyServer;
    /**
     * @var int
     */
    private $proxyPort = 8080;
    /**
     * @var string
     */
    private $proxyUser;
    /**
     * @var string
     */
    private $proxyPass;
    /**
     * @var int
     */
    private $publinksMaxViews = 3;
    /**
     * @var int
     */
    private $publinksMaxTime = 600;
    /**
     * @var bool
     */
    private $publinksEnabled = false;
    /**
     * @var int
     */
    private $accountCount = 12;
    /**
     * @var bool
     */
    private $accountLink = true;
    /**
     * @var bool
     */
    private $checkUpdates = false;
    /**
     * @var bool
     */
    private $checknotices = false;
    /**
     * @var string
     */
    private $configHash;
    /**
     * @var string
     */
    private $dbHost;
    /**
     * @var string
     */
    private $dbSocket;
    /**
     * @var string
     */
    private $dbName;
    /**
     * @var string
     */
    private $dbPass;
    /**
     * @var string
     */
    private $dbUser;
    /**
     * @var int
     */
    private $dbPort = 3306;
    /**
     * @var bool
     */
    private $debug = false;
    /**
     * @var bool
     */
    private $demoEnabled = false;
    /**
     * @var array
     */
    private $filesAllowedExts = [];
    /**
     * @var array
     */
    private $filesAllowedMime = [];
    /**
     * @var int
     */
    private $filesAllowedSize = 1024;
    /**
     * @var bool
     */
    private $filesEnabled = true;
    /**
     * @var bool
     */
    private $globalSearch = true;
    /**
     * @var bool
     */
    private $installed = false;
    /**
     * @var string
     */
    private $ldapBase;
    /**
     * @var string
     */
    private $ldapBindUser;
    /**
     * @var string
     */
    private $ldapBindPass;
    /**
     * @var string
     */
    private $ldapProxyUser;
    /**
     * @var bool
     */
    private $ldapEnabled = false;
    /**
     * @var bool
     */
    private $ldapAds = false;
    /**
     * @var int
     */
    private $ldapType;
    /**
     * @var string
     */
    private $ldapGroup;
    /**
     * @var string
     */
    private $ldapServer;
    /**
     * @var bool
     */
    private $logEnabled = true;
    /**
     * @var array
     */
    private $logEvents = [];
    /**
     * @var bool
     */
    private $mailAuthenabled = false;
    /**
     * @var bool
     */
    private $mailEnabled = false;
    /**
     * @var string
     */
    private $mailFrom;
    /**
     * @var string
     */
    private $mailPass;
    /**
     * @var int
     */
    private $mailPort = 25;
    /**
     * @var bool
     */
    private $mailRequestsEnabled = false;
    /**
     * @var string
     */
    private $mailSecurity;
    /**
     * @var string
     */
    private $mailServer;
    /**
     * @var string
     */
    private $mailUser;
    /**
     * @var array
     */
    private $mailRecipients = [];
    /**
     * @var array
     */
    private $mailEvents = [];
    /**
     * @var bool
     */
    private $maintenance = false;
    /**
     * @var string
     */
    private $passwordSalt;
    /**
     * @var bool
     */
    private $resultsAsCards = false;
    /**
     * @var int
     */
    private $sessionTimeout = 300;
    /**
     * @var string
     */
    private $siteLang;
    /**
     * @var string
     */
    private $siteTheme = 'material-blue';
    /**
     * @var string
     */
    private $configVersion;
    /**
     * @var string
     */
    private $appVersion;
    /**
     * @var string
     */
    private $databaseVersion;
    /**
     * @var bool
     */
    private $wikiEnabled = false;
    /**
     * @var array
     */
    private $wikiFilter = [];
    /**
     * @var string
     */
    private $wikiPageurl;
    /**
     * @var string
     */
    private $wikiSearchurl;
    /**
     * @var int
     */
    private $configDate = 0;
    /**
     * @var bool
     */
    private $publinksImageEnabled = false;
    /**
     * @var string
     */
    private $backup_hash;
    /**
     * @var string
     */
    private $export_hash;
    /**
     * @var bool
     */
    private $httpsEnabled = false;
    /**
     * @var bool
     */
    private $syslogEnabled = false;
    /**
     * @var bool
     */
    private $syslogRemoteEnabled = false;
    /**
     * @var string
     */
    private $syslogServer;
    /**
     * @var int
     */
    private $syslogPort = 514;
    /**
     * @var bool
     */
    private $accountPassToImage = false;
    /**
     * @var string
     */
    private $configSaver;
    /**
     * @var bool
     */
    private $encryptSession = false;
    /**
     * @var bool
     */
    private $accountFullGroupAccess = false;
    /**
     * @var bool
     */
    private $authBasicEnabled = true;
    /**
     * @var bool
     */
    private $authBasicAutoLoginEnabled = true;
    /**
     * @var string
     */
    private $authBasicDomain;
    /**
     * @var int
     */
    private $ssoDefaultGroup;
    /**
     * @var int
     */
    private $ssoDefaultProfile;
    /**
     * @var bool
     */
    private $accountExpireEnabled = false;
    /**
     * @var int
     */
    private $accountExpireTime = 10368000;
    /**
     * @var bool
     */
    private $ldapTlsEnabled = false;
    /**
     * @var string
     */
    private $applicationUrl;

    /**
     * @return array
     */
    public function getLogEvents()
    {
        return is_array($this->logEvents) ? $this->logEvents : [];
    }

    /**
     * @param array $logEvents
     */
    public function setLogEvents(array $logEvents)
    {
        $this->logEvents = $logEvents;
    }

    /**
     * @return boolean
     */
    public function isDokuwikiEnabled()
    {
        return $this->dokuwikiEnabled;
    }

    /**
     * @param boolean $dokuwikiEnabled
     *
     * @return $this
     */
    public function setDokuwikiEnabled($dokuwikiEnabled)
    {
        $this->dokuwikiEnabled = (bool)$dokuwikiEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUrl()
    {
        return $this->dokuwikiUrl;
    }

    /**
     * @param string $dokuwikiUrl
     *
     * @return $this
     */
    public function setDokuwikiUrl($dokuwikiUrl)
    {
        $this->dokuwikiUrl = $dokuwikiUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUrlBase()
    {
        return $this->dokuwikiUrlBase;
    }

    /**
     * @param string $dokuwikiUrlBase
     *
     * @return $this
     */
    public function setDokuwikiUrlBase($dokuwikiUrlBase)
    {
        $this->dokuwikiUrlBase = $dokuwikiUrlBase;

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiUser()
    {
        return $this->dokuwikiUser;
    }

    /**
     * @param string $dokuwikiUser
     *
     * @return $this
     */
    public function setDokuwikiUser($dokuwikiUser)
    {
        $this->dokuwikiUser = $dokuwikiUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiPass()
    {
        return $this->dokuwikiPass;
    }

    /**
     * @param string $dokuwikiPass
     *
     * @return $this
     */
    public function setDokuwikiPass($dokuwikiPass)
    {
        $this->dokuwikiPass = $dokuwikiPass;

        return $this;
    }

    /**
     * @return string
     */
    public function getDokuwikiNamespace()
    {
        return $this->dokuwikiNamespace;
    }

    /**
     * @param string $dokuwikiNamespace
     *
     * @return $this
     */
    public function setDokuwikiNamespace($dokuwikiNamespace)
    {
        $this->dokuwikiNamespace = $dokuwikiNamespace;

        return $this;
    }

    /**
     * @return int
     */
    public function getLdapDefaultGroup()
    {
        return (int)$this->ldapDefaultGroup;
    }

    /**
     * @param int $ldapDefaultGroup
     *
     * @return $this
     */
    public function setLdapDefaultGroup($ldapDefaultGroup)
    {
        $this->ldapDefaultGroup = (int)$ldapDefaultGroup;

        return $this;
    }

    /**
     * @return int
     */
    public function getLdapDefaultProfile()
    {
        return (int)$this->ldapDefaultProfile;
    }

    /**
     * @param int $ldapDefaultProfile
     *
     * @return $this
     */
    public function setLdapDefaultProfile($ldapDefaultProfile)
    {
        $this->ldapDefaultProfile = (int)$ldapDefaultProfile;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isProxyEnabled()
    {
        return $this->proxyEnabled;
    }

    /**
     * @param boolean $proxyEnabled
     *
     * @return $this
     */
    public function setProxyEnabled($proxyEnabled)
    {
        $this->proxyEnabled = (bool)$proxyEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyServer()
    {
        return $this->proxyServer;
    }

    /**
     * @param string $proxyServer
     *
     * @return $this
     */
    public function setProxyServer($proxyServer)
    {
        $this->proxyServer = $proxyServer;

        return $this;
    }

    /**
     * @return int
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * @param int $proxyPort
     *
     * @return $this
     */
    public function setProxyPort($proxyPort)
    {
        $this->proxyPort = (int)$proxyPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyUser()
    {
        return $this->proxyUser;
    }

    /**
     * @param string $proxyUser
     *
     * @return $this
     */
    public function setProxyUser($proxyUser)
    {
        $this->proxyUser = $proxyUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getProxyPass()
    {
        return $this->proxyPass;
    }

    /**
     * @param string $proxyPass
     *
     * @return $this
     */
    public function setProxyPass($proxyPass)
    {
        $this->proxyPass = $proxyPass;

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxViews()
    {
        return $this->publinksMaxViews;
    }


    /**
     * @param int $publinksMaxViews
     *
     * @return $this
     */
    public function setPublinksMaxViews($publinksMaxViews)
    {
        $this->publinksMaxViews = (int)$publinksMaxViews;

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxTime()
    {
        return $this->publinksMaxTime;
    }

    /**
     * @param int $publinksMaxTime
     *
     * @return $this
     */
    public function setPublinksMaxTime($publinksMaxTime)
    {
        $this->publinksMaxTime = (int)$publinksMaxTime;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogEnabled()
    {
        return $this->syslogEnabled;
    }

    /**
     * @param boolean $syslogEnabled
     *
     * @return $this
     */
    public function setSyslogEnabled($syslogEnabled)
    {
        $this->syslogEnabled = (bool)$syslogEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogRemoteEnabled()
    {
        return $this->syslogRemoteEnabled;
    }

    /**
     * @param boolean $syslogRemoteEnabled
     *
     * @return $this
     */
    public function setSyslogRemoteEnabled($syslogRemoteEnabled)
    {
        $this->syslogRemoteEnabled = (bool)$syslogRemoteEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getSyslogServer()
    {
        return $this->syslogServer;
    }

    /**
     * @param string $syslogServer
     *
     * @return $this
     */
    public function setSyslogServer($syslogServer)
    {
        $this->syslogServer = $syslogServer;

        return $this;
    }

    /**
     * @return int
     */
    public function getSyslogPort()
    {
        return $this->syslogPort;
    }

    /**
     * @param int $syslogPort
     *
     * @return $this
     */
    public function setSyslogPort($syslogPort)
    {
        $this->syslogPort = (int)$syslogPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getBackupHash()
    {
        return $this->backup_hash;
    }

    /**
     * @param string $backup_hash
     *
     * @return $this
     */
    public function setBackupHash($backup_hash)
    {
        $this->backup_hash = $backup_hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getExportHash()
    {
        return $this->export_hash;
    }

    /**
     * @param string $export_hash
     *
     * @return $this
     */
    public function setExportHash($export_hash)
    {
        $this->export_hash = $export_hash;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBindUser()
    {
        return $this->ldapBindUser;
    }

    /**
     * @param string $ldapBindUser
     *
     * @return $this
     */
    public function setLdapBindUser($ldapBindUser)
    {
        $this->ldapBindUser = $ldapBindUser;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapProxyUser()
    {
        return $this->ldapProxyUser;
    }

    /**
     * @param string $ldapProxyUser
     *
     * @return $this
     */
    public function setLdapProxyUser($ldapProxyUser)
    {
        $this->ldapProxyUser = $ldapProxyUser;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountCount()
    {
        return $this->accountCount;
    }

    /**
     * @param int $accountCount
     *
     * @return $this
     */
    public function setAccountCount($accountCount)
    {
        $this->accountCount = (int)$accountCount;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccountLink()
    {
        return $this->accountLink;
    }

    /**
     * @param boolean $accountLink
     *
     * @return $this
     */
    public function setAccountLink($accountLink)
    {
        $this->accountLink = (bool)$accountLink;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isCheckUpdates()
    {
        return $this->checkUpdates;
    }

    /**
     * @param boolean $checkUpdates
     *
     * @return $this
     */
    public function setCheckUpdates($checkUpdates)
    {
        $this->checkUpdates = (bool)$checkUpdates;

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigHash()
    {
        return $this->configHash;
    }

    /**
     * Generates a hash from current config options
     */
    public function setConfigHash()
    {
        $this->configHash = sha1(serialize($this));

        return $this;
    }

    /**
     * @return string
     */
    public function getDbHost()
    {
        return $this->dbHost;
    }

    /**
     * @param string $dbHost
     *
     * @return $this
     */
    public function setDbHost($dbHost)
    {
        $this->dbHost = $dbHost;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * @param string $dbName
     *
     * @return $this
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbPass()
    {
        return $this->dbPass;
    }

    /**
     * @param string $dbPass
     *
     * @return $this
     */
    public function setDbPass($dbPass)
    {
        $this->dbPass = $dbPass;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbUser()
    {
        return $this->dbUser;
    }

    /**
     * @param string $dbUser
     *
     * @return $this
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param boolean $debug
     *
     * @return $this
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isDemoEnabled()
    {
        return $this->demoEnabled;
    }

    /**
     * @param boolean $demoEnabled
     *
     * @return $this
     */
    public function setDemoEnabled($demoEnabled)
    {
        $this->demoEnabled = (bool)$demoEnabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getFilesAllowedExts()
    {
        return (array)$this->filesAllowedExts;
    }

    /**
     * @return int
     */
    public function getFilesAllowedSize()
    {
        return $this->filesAllowedSize;
    }

    /**
     * @param int $filesAllowedSize
     *
     * @return $this
     */
    public function setFilesAllowedSize($filesAllowedSize)
    {
        $this->filesAllowedSize = (int)$filesAllowedSize;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isFilesEnabled()
    {
        return $this->filesEnabled;
    }

    /**
     * @param boolean $filesEnabled
     *
     * @return $this
     */
    public function setFilesEnabled($filesEnabled)
    {
        $this->filesEnabled = (bool)$filesEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isGlobalSearch()
    {
        return $this->globalSearch;
    }

    /**
     * @param boolean $globalSearch
     *
     * @return $this
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = (bool)$globalSearch;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isInstalled()
    {
        return $this->installed;
    }

    /**
     * @param boolean $installed
     *
     * @return $this
     */
    public function setInstalled($installed)
    {
        $this->installed = (bool)$installed;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBase()
    {
        return $this->ldapBase;
    }

    /**
     * @param string $ldapBase
     *
     * @return $this
     */
    public function setLdapBase($ldapBase)
    {
        $this->ldapBase = $ldapBase;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLdapEnabled()
    {
        return $this->ldapEnabled;
    }

    /**
     * @param boolean $ldapEnabled
     *
     * @return $this
     */
    public function setLdapEnabled($ldapEnabled)
    {
        $this->ldapEnabled = (bool)$ldapEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapGroup()
    {
        return $this->ldapGroup;
    }

    /**
     * @param string $ldapGroup
     *
     * @return $this
     */
    public function setLdapGroup($ldapGroup)
    {
        $this->ldapGroup = $ldapGroup;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapServer()
    {
        return $this->ldapServer;
    }

    /**
     * @param string $ldapServer
     *
     * @return $this
     */
    public function setLdapServer($ldapServer)
    {
        $this->ldapServer = $ldapServer;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLogEnabled()
    {
        return $this->logEnabled;
    }

    /**
     * @param boolean $logEnabled
     *
     * @return $this
     */
    public function setLogEnabled($logEnabled)
    {
        $this->logEnabled = (bool)$logEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailAuthenabled()
    {
        return $this->mailAuthenabled;
    }

    /**
     * @param boolean $mailAuthenabled
     *
     * @return $this
     */
    public function setMailAuthenabled($mailAuthenabled)
    {
        $this->mailAuthenabled = (bool)$mailAuthenabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailEnabled()
    {
        return $this->mailEnabled;
    }

    /**
     * @param boolean $mailEnabled
     *
     * @return $this
     */
    public function setMailEnabled($mailEnabled)
    {
        $this->mailEnabled = (bool)$mailEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailFrom()
    {
        return $this->mailFrom;
    }

    /**
     * @param string $mailFrom
     *
     * @return $this
     */
    public function setMailFrom($mailFrom)
    {
        $this->mailFrom = $mailFrom;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailPass()
    {
        return $this->mailPass;
    }

    /**
     * @param string $mailPass
     *
     * @return $this
     */
    public function setMailPass($mailPass)
    {
        $this->mailPass = $mailPass;

        return $this;
    }

    /**
     * @return int
     */
    public function getMailPort()
    {
        return $this->mailPort;
    }

    /**
     * @param int $mailPort
     *
     * @return $this
     */
    public function setMailPort($mailPort)
    {
        $this->mailPort = (int)$mailPort;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMailRequestsEnabled()
    {
        return $this->mailRequestsEnabled;
    }

    /**
     * @param boolean $mailRequestsEnabled
     *
     * @return $this
     */
    public function setMailRequestsEnabled($mailRequestsEnabled)
    {
        $this->mailRequestsEnabled = (bool)$mailRequestsEnabled;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailSecurity()
    {
        return $this->mailSecurity;
    }

    /**
     * @param string $mailSecurity
     *
     * @return $this
     */
    public function setMailSecurity($mailSecurity)
    {
        $this->mailSecurity = $mailSecurity;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailServer()
    {
        return $this->mailServer;
    }

    /**
     * @param string $mailServer
     *
     * @return $this
     */
    public function setMailServer($mailServer)
    {
        $this->mailServer = $mailServer;

        return $this;
    }

    /**
     * @return string
     */
    public function getMailUser()
    {
        return $this->mailUser;
    }

    /**
     * @param string $mailUser
     *
     * @return $this
     */
    public function setMailUser($mailUser)
    {
        $this->mailUser = $mailUser;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isMaintenance()
    {
        return (bool)$this->maintenance;
    }

    /**
     * @param boolean $maintenance
     *
     * @return $this
     */
    public function setMaintenance($maintenance)
    {
        $this->maintenance = (bool)$maintenance;

        return $this;
    }

    /**
     * @return string
     */
    public function getPasswordSalt()
    {
        return $this->passwordSalt;
    }

    /**
     * @param string $passwordSalt
     *
     * @return $this
     */
    public function setPasswordSalt($passwordSalt)
    {
        $this->passwordSalt = $passwordSalt;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isResultsAsCards()
    {
        return $this->resultsAsCards;
    }

    /**
     * @param boolean $resultsAsCards
     *
     * @return $this
     */
    public function setResultsAsCards($resultsAsCards)
    {
        $this->resultsAsCards = (bool)$resultsAsCards;

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionTimeout()
    {
        return $this->sessionTimeout;
    }

    /**
     * @param int $sessionTimeout
     *
     * @return $this
     */
    public function setSessionTimeout($sessionTimeout)
    {
        $this->sessionTimeout = (int)$sessionTimeout;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteLang()
    {
        return $this->siteLang;
    }

    /**
     * @param string $siteLang
     *
     * @return $this
     */
    public function setSiteLang($siteLang)
    {
        $this->siteLang = $siteLang;

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteTheme()
    {
        return $this->siteTheme;
    }

    /**
     * @param string $siteTheme
     *
     * @return $this
     */
    public function setSiteTheme($siteTheme)
    {
        $this->siteTheme = $siteTheme;

        return $this;
    }

    /**
     * @return int
     */
    public function getConfigVersion()
    {
        return (string)$this->configVersion;
    }

    /**
     * @param string $configVersion
     *
     * @return $this
     */
    public function setConfigVersion($configVersion)
    {
        $this->configVersion = $configVersion;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isWikiEnabled()
    {
        return $this->wikiEnabled;
    }

    /**
     * @param boolean $wikiEnabled
     *
     * @return $this
     */
    public function setWikiEnabled($wikiEnabled)
    {
        $this->wikiEnabled = (bool)$wikiEnabled;

        return $this;
    }

    /**
     * @return array
     */
    public function getWikiFilter()
    {
        return is_array($this->wikiFilter) ? $this->wikiFilter : [];
    }

    /**
     * @param array $wikiFilter
     *
     * @return $this
     */
    public function setWikiFilter($wikiFilter)
    {
        $this->wikiFilter = $wikiFilter;

        return $this;
    }

    /**
     * @return string
     */
    public function getWikiPageurl()
    {
        return $this->wikiPageurl;
    }

    /**
     * @param string $wikiPageurl
     *
     * @return $this
     */
    public function setWikiPageurl($wikiPageurl)
    {
        $this->wikiPageurl = $wikiPageurl;

        return $this;
    }

    /**
     * @return string
     */
    public function getWikiSearchurl()
    {
        return $this->wikiSearchurl;
    }

    /**
     * @param string $wikiSearchurl
     *
     * @return $this
     */
    public function setWikiSearchurl($wikiSearchurl)
    {
        $this->wikiSearchurl = $wikiSearchurl;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isLdapAds()
    {
        return $this->ldapAds;
    }

    /**
     * @param boolean $ldapAds
     *
     * @return $this
     */
    public function setLdapAds($ldapAds)
    {
        $this->ldapAds = (bool)$ldapAds;

        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBindPass()
    {
        return $this->ldapBindPass;
    }

    /**
     * @param string $ldapBindPass
     *
     * @return $this
     */
    public function setLdapBindPass($ldapBindPass)
    {
        $this->ldapBindPass = $ldapBindPass;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublinksImageEnabled()
    {
        return $this->publinksImageEnabled;
    }

    /**
     * @param boolean $publinksImageEnabled
     *
     * @return $this
     */
    public function setPublinksImageEnabled($publinksImageEnabled)
    {
        $this->publinksImageEnabled = (bool)$publinksImageEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isHttpsEnabled()
    {
        return $this->httpsEnabled;
    }

    /**
     * @param boolean $httpsEnabled
     *
     * @return $this
     */
    public function setHttpsEnabled($httpsEnabled)
    {
        $this->httpsEnabled = (bool)$httpsEnabled;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isChecknotices()
    {
        return $this->checknotices;
    }

    /**
     * @param boolean $checknotices
     *
     * @return $this
     */
    public function setChecknotices($checknotices)
    {
        $this->checknotices = $checknotices;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccountPassToImage()
    {
        return $this->accountPassToImage;
    }

    /**
     * @param boolean $accountPassToImage
     *
     * @return $this
     */
    public function setAccountPassToImage($accountPassToImage)
    {
        $this->accountPassToImage = (bool)$accountPassToImage;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpgradeKey()
    {
        return $this->upgradeKey;
    }

    /**
     * @param string $upgradeKey
     *
     * @return $this
     */
    public function setUpgradeKey($upgradeKey)
    {
        $this->upgradeKey = $upgradeKey;

        return $this;
    }

    /**
     * @return int
     */
    public function getDbPort()
    {
        return $this->dbPort;
    }

    /**
     * @param int $dbPort
     *
     * @return $this
     */
    public function setDbPort($dbPort)
    {
        $this->dbPort = (int)$dbPort;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isPublinksEnabled()
    {
        return $this->publinksEnabled;
    }

    /**
     * @param boolean $publinksEnabled
     *
     * @return $this
     */
    public function setPublinksEnabled($publinksEnabled)
    {
        $this->publinksEnabled = (bool)$publinksEnabled;

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
        return get_object_vars($this);
    }

    /**
     * @return string
     */
    public function getConfigSaver()
    {
        return $this->configSaver;
    }

    /**
     * @param string $configSaver
     *
     * @return $this
     */
    public function setConfigSaver($configSaver)
    {
        $this->configSaver = $configSaver;

        return $this;
    }

    /**
     * @return string
     */
    public function getDbSocket()
    {
        return $this->dbSocket;
    }

    /**
     * @param string $dbSocket
     */
    public function setDbSocket($dbSocket)
    {
        $this->dbSocket = $dbSocket;
    }

    /**
     * @return bool
     */
    public function isEncryptSession()
    {
        return (bool)$this->encryptSession;
    }

    /**
     * @param bool $encryptSession
     *
     * @return $this
     */
    public function setEncryptSession($encryptSession)
    {
        $this->encryptSession = (bool)$encryptSession;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountFullGroupAccess()
    {
        return (bool)$this->accountFullGroupAccess;
    }

    /**
     * @param bool $accountFullGroupAccess
     *
     * @return $this
     */
    public function setAccountFullGroupAccess($accountFullGroupAccess)
    {
        $this->accountFullGroupAccess = (bool)$accountFullGroupAccess;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthBasicEnabled()
    {
        return (bool)$this->authBasicEnabled;
    }

    /**
     * @param bool $authBasicEnabled
     */
    public function setAuthBasicEnabled($authBasicEnabled)
    {
        $this->authBasicEnabled = $authBasicEnabled;
    }

    /**
     * @return string
     */
    public function getAuthBasicDomain()
    {
        return $this->authBasicDomain;
    }

    /**
     * @param string $authBasicDomain
     */
    public function setAuthBasicDomain($authBasicDomain)
    {
        $this->authBasicDomain = $authBasicDomain;
    }

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled()
    {
        return (bool)$this->authBasicAutoLoginEnabled;
    }

    /**
     * @param bool $authBasicAutoLoginEnabled
     */
    public function setAuthBasicAutoLoginEnabled($authBasicAutoLoginEnabled)
    {
        $this->authBasicAutoLoginEnabled = $authBasicAutoLoginEnabled;
    }

    /**
     * @return int
     */
    public function getSsoDefaultGroup()
    {
        return $this->ssoDefaultGroup;
    }

    /**
     * @param int $ssoDefaultGroup
     */
    public function setSsoDefaultGroup($ssoDefaultGroup)
    {
        $this->ssoDefaultGroup = $ssoDefaultGroup;
    }

    /**
     * @return int
     */
    public function getSsoDefaultProfile()
    {
        return $this->ssoDefaultProfile;
    }

    /**
     * @param int $ssoDefaultProfile
     */
    public function setSsoDefaultProfile($ssoDefaultProfile)
    {
        $this->ssoDefaultProfile = $ssoDefaultProfile;
    }

    /**
     * @return array
     */
    public function getMailRecipients()
    {
        return (array)$this->mailRecipients;
    }

    /**
     * @param array $mailRecipients
     */
    public function setMailRecipients(array $mailRecipients)
    {
        $this->mailRecipients = $mailRecipients;
    }

    /**
     * @return array
     */
    public function getMailEvents()
    {
        return is_array($this->mailEvents) ? $this->mailEvents : [];
    }

    /**
     * @param array $mailEvents
     */
    public function setMailEvents(array $mailEvents)
    {
        $this->mailEvents = $mailEvents;
    }

    /**
     * @return string
     */
    public function getDatabaseVersion()
    {
        return (string)$this->databaseVersion;
    }

    /**
     * @param string $databaseVersion
     *
     * @return ConfigData
     */
    public function setDatabaseVersion($databaseVersion)
    {
        $this->databaseVersion = $databaseVersion;

        return $this;
    }

    /**
     * @return int
     */
    public function getConfigDate()
    {
        return $this->configDate;
    }

    /**
     * @param int $configDate
     *
     * @return $this
     */
    public function setConfigDate($configDate)
    {
        $this->configDate = (int)$configDate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountExpireEnabled()
    {
        return (int)$this->accountExpireEnabled;
    }

    /**
     * @param bool $accountExpireEnabled
     *
     * @return ConfigData
     */
    public function setAccountExpireEnabled($accountExpireEnabled)
    {
        $this->accountExpireEnabled = $accountExpireEnabled;

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountExpireTime()
    {
        return $this->accountExpireTime;
    }

    /**
     * @param int $accountExpireTime
     *
     * @return ConfigData
     */
    public function setAccountExpireTime($accountExpireTime)
    {
        $this->accountExpireTime = (int)$accountExpireTime;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapTlsEnabled(): bool
    {
        return (bool)$this->ldapTlsEnabled;
    }

    /**
     * @param bool $ldapTlsEnabled
     */
    public function setLdapTlsEnabled(bool $ldapTlsEnabled)
    {
        $this->ldapTlsEnabled = (int)$ldapTlsEnabled;
    }

    /**
     * @return array
     */
    public function getFilesAllowedMime(): array
    {
        return (array)$this->filesAllowedMime;
    }

    /**
     * @param array $filesAllowedMime
     */
    public function setFilesAllowedMime(array $filesAllowedMime)
    {
        $this->filesAllowedMime = $filesAllowedMime;
    }

    /**
     * @return int
     */
    public function getLdapType()
    {
        return (int)$this->ldapType;
    }

    /**
     * @param int $ldapType
     */
    public function setLdapType(int $ldapType)
    {
        $this->ldapType = $ldapType;
    }

    /**
     * @return string
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }

    /**
     * @param string $appVersion
     */
    public function setAppVersion(string $appVersion)
    {
        $this->appVersion = $appVersion;
    }

    /**
     * @return string
     */
    public function getApplicationUrl()
    {
        return $this->applicationUrl;
    }

    /**
     * @param string $applicationUrl
     */
    public function setApplicationUrl(string $applicationUrl = null)
    {
        $this->applicationUrl = $applicationUrl ? rtrim($applicationUrl, '/') : null;
    }
}
