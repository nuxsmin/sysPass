<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@$syspass.org
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
 *
 */

namespace SP\Config;
use JsonSerializable;

/**
 * Class ConfigData
 *
 * @package SP\Config
 */
class ConfigData implements JsonSerializable
{
    /**
     * @var string
     */
    private $upgradeKey = '';
    /**
     * @var bool
     */
    private $dokuwikiEnabled = false;
    /**
     * @var string
     */
    private $dokuwikiUrl = '';
    /**
     * @var string
     */
    private $dokuwikiUrlBase = '';
    /**
     * @var string
     */
    private $dokuwikiUser = '';
    /**
     * @var string
     */
    private $dokuwikiPass = '';
    /**
     * @var string
     */
    private $dokuwikiNamespace = '';
    /**
     * @var int
     */
    private $ldapDefaultGroup = 0;
    /**
     * @var int
     */
    private $ldapDefaultProfile = 0;
    /**
     * @var bool
     */
    private $proxyEnabled = false;
    /**
     * @var string
     */
    private $proxyServer = '';
    /**
     * @var int
     */
    private $proxyPort = 0;
    /**
     * @var string
     */
    private $proxyUser = '';
    /**
     * @var string
     */
    private $proxyPass = '';
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
    private $configHash = '';
    /**
     * @var string
     */
    private $dbHost = '';
    /**
     * @var string
     */
    private $dbName = '';
    /**
     * @var string
     */
    private $dbPass = '';
    /**
     * @var string
     */
    private $dbUser = '';
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
    private $filesAllowedExts = ['PDF', 'JPG', 'GIF', 'PNG', 'ODT', 'ODS', 'DOC', 'DOCX', 'XLS', 'XSL', 'VSD', 'TXT', 'CSV', 'BAK'];
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
    private $ldapBase = '';
    /**
     * @var string
     */
    private $ldapBindUser = '';
    /**
     * @var string
     */
    private $ldapBindPass = '';
    /**
     * @var string
     */
    private $ldapProxyUser = '';
    /**
     * @var bool
     */
    private $ldapEnabled = false;
    /**
     * @var bool
     */
    private $ldapAds = false;
    /**
     * @var string
     */
    private $ldapGroup = '*';
    /**
     * @var string
     */
    private $ldapServer = '';
    /**
     * @var string
     */
    private $ldapUserattr = '';
    /**
     * @var bool
     */
    private $logEnabled = true;
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
    private $mailFrom = '';
    /**
     * @var string
     */
    private $mailPass = '';
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
    private $mailSecurity = '';
    /**
     * @var string
     */
    private $mailServer = '';
    /**
     * @var string
     */
    private $mailUser = '';
    /**
     * @var bool
     */
    private $maintenance = false;
    /**
     * @var string
     */
    private $passwordSalt = '';
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
    private $siteLang = 'es_ES';
    /**
     * @var string
     */
    private $siteTheme = 'material-blue';
    /**
     * @var int
     */
    private $configVersion = 0;
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
    private $wikiPageurl = '';
    /**
     * @var string
     */
    private $wikiSearchurl = '';
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
    private $backup_hash = '';
    /**
     * @var string
     */
    private $export_hash = '';
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
    private $syslogServer = '';
    /**
     * @var int
     */
    private $syslogPort = 514;
    /**
     * @var bool
     */
    private $accountPassToImage = false;

    /**
     * @return boolean
     */
    public function isDokuwikiEnabled()
    {
        return $this->dokuwikiEnabled;
    }

    /**
     * @param boolean $dokuwikiEnabled
     */
    public function setDokuwikiEnabled($dokuwikiEnabled)
    {
        $this->dokuwikiEnabled = (bool)$dokuwikiEnabled;
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
     */
    public function setDokuwikiUrl($dokuwikiUrl)
    {
        $this->dokuwikiUrl = $dokuwikiUrl;
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
     */
    public function setDokuwikiUrlBase($dokuwikiUrlBase)
    {
        $this->dokuwikiUrlBase = $dokuwikiUrlBase;
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
     */
    public function setDokuwikiUser($dokuwikiUser)
    {
        $this->dokuwikiUser = $dokuwikiUser;
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
     */
    public function setDokuwikiPass($dokuwikiPass)
    {
        $this->dokuwikiPass = $dokuwikiPass;
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
     */
    public function setDokuwikiNamespace($dokuwikiNamespace)
    {
        $this->dokuwikiNamespace = $dokuwikiNamespace;
    }

    /**
     * @return int
     */
    public function getLdapDefaultGroup()
    {
        return $this->ldapDefaultGroup;
    }

    /**
     * @param int $ldapDefaultGroup
     */
    public function setLdapDefaultGroup($ldapDefaultGroup)
    {
        $this->ldapDefaultGroup = intval($ldapDefaultGroup);
    }

    /**
     * @return int
     */
    public function getLdapDefaultProfile()
    {
        return $this->ldapDefaultProfile;
    }

    /**
     * @param int $ldapDefaultProfile
     */
    public function setLdapDefaultProfile($ldapDefaultProfile)
    {
        $this->ldapDefaultProfile = intval($ldapDefaultProfile);
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
     */
    public function setProxyEnabled($proxyEnabled)
    {
        $this->proxyEnabled = (bool)$proxyEnabled;
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
     */
    public function setProxyServer($proxyServer)
    {
        $this->proxyServer = $proxyServer;
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
     */
    public function setProxyPort($proxyPort)
    {
        $this->proxyPort = intval($proxyPort);
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
     */
    public function setProxyUser($proxyUser)
    {
        $this->proxyUser = $proxyUser;
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
     */
    public function setProxyPass($proxyPass)
    {
        $this->proxyPass = $proxyPass;
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
     */
    public function setPublinksMaxViews($publinksMaxViews)
    {
        $this->publinksMaxViews = intval($publinksMaxViews);
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
     */
    public function setPublinksMaxTime($publinksMaxTime)
    {
        $this->publinksMaxTime = intval($publinksMaxTime);
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
     */
    public function setSyslogEnabled($syslogEnabled)
    {
        $this->syslogEnabled = (bool)$syslogEnabled;
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
     */
    public function setSyslogRemoteEnabled($syslogRemoteEnabled)
    {
        $this->syslogRemoteEnabled = (bool)$syslogRemoteEnabled;
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
     */
    public function setSyslogServer($syslogServer)
    {
        $this->syslogServer = $syslogServer;
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
     */
    public function setSyslogPort($syslogPort)
    {
        $this->syslogPort = intval($syslogPort);
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
     */
    public function setBackupHash($backup_hash)
    {
        $this->backup_hash = $backup_hash;
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
     */
    public function setExportHash($export_hash)
    {
        $this->export_hash = $export_hash;
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
     */
    public function setLdapBindUser($ldapBindUser)
    {
        $this->ldapBindUser = $ldapBindUser;
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
     */
    public function setLdapProxyUser($ldapProxyUser)
    {
        $this->ldapProxyUser = $ldapProxyUser;
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
     */
    public function setAccountCount($accountCount)
    {
        $this->accountCount = intval($accountCount);
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
     */
    public function setAccountLink($accountLink)
    {
        $this->accountLink = (bool)$accountLink;
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
     */
    public function setCheckUpdates($checkUpdates)
    {
        $this->checkUpdates = (bool)$checkUpdates;
    }

    /**
     * @return string
     */
    public function getConfigHash()
    {
        return $this->configHash;
    }

    /**
     * @param string $configHash
     */
    public function setConfigHash($configHash)
    {
        $this->configHash = $configHash;
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
     */
    public function setDbHost($dbHost)
    {
        $this->dbHost = $dbHost;
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
     */
    public function setDbName($dbName)
    {
        $this->dbName = $dbName;
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
     */
    public function setDbPass($dbPass)
    {
        $this->dbPass = $dbPass;
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
     */
    public function setDbUser($dbUser)
    {
        $this->dbUser = $dbUser;
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
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
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
     */
    public function setDemoEnabled($demoEnabled)
    {
        $this->demoEnabled = (bool)$demoEnabled;
    }

    /**
     * @return array
     */
    public function getFilesAllowedExts()
    {
        return is_array($this->filesAllowedExts) ? $this->filesAllowedExts : array();
    }

    /**
     * @param array $filesAllowedExts
     */
    public function setFilesAllowedExts($filesAllowedExts)
    {
        $this->filesAllowedExts = $filesAllowedExts;
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
     */
    public function setFilesAllowedSize($filesAllowedSize)
    {
        $this->filesAllowedSize = intval($filesAllowedSize);
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
     */
    public function setFilesEnabled($filesEnabled)
    {
        $this->filesEnabled = (bool)$filesEnabled;
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
     */
    public function setGlobalSearch($globalSearch)
    {
        $this->globalSearch = (bool)$globalSearch;
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
     */
    public function setInstalled($installed)
    {
        $this->installed = (bool)$installed;
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
     */
    public function setLdapBase($ldapBase)
    {
        $this->ldapBase = $ldapBase;
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
     */
    public function setLdapEnabled($ldapEnabled)
    {
        $this->ldapEnabled = (bool)$ldapEnabled;
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
     */
    public function setLdapGroup($ldapGroup)
    {
        $this->ldapGroup = $ldapGroup;
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
     */
    public function setLdapServer($ldapServer)
    {
        $this->ldapServer = $ldapServer;
    }

    /**
     * @return string
     */
    public function getLdapUserattr()
    {
        return $this->ldapUserattr;
    }

    /**
     * @param string $ldapUserattr
     */
    public function setLdapUserattr($ldapUserattr)
    {
        $this->ldapUserattr = $ldapUserattr;
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
     */
    public function setLogEnabled($logEnabled)
    {
        $this->logEnabled = (bool)$logEnabled;
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
     */
    public function setMailAuthenabled($mailAuthenabled)
    {
        $this->mailAuthenabled = (bool)$mailAuthenabled;
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
     */
    public function setMailEnabled($mailEnabled)
    {
        $this->mailEnabled = (bool)$mailEnabled;
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
     */
    public function setMailFrom($mailFrom)
    {
        $this->mailFrom = $mailFrom;
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
     */
    public function setMailPass($mailPass)
    {
        $this->mailPass = $mailPass;
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
     */
    public function setMailPort($mailPort)
    {
        $this->mailPort = intval($mailPort);
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
     */
    public function setMailRequestsEnabled($mailRequestsEnabled)
    {
        $this->mailRequestsEnabled = (bool)$mailRequestsEnabled;
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
     */
    public function setMailSecurity($mailSecurity)
    {
        $this->mailSecurity = $mailSecurity;
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
     */
    public function setMailServer($mailServer)
    {
        $this->mailServer = $mailServer;
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
     */
    public function setMailUser($mailUser)
    {
        $this->mailUser = $mailUser;
    }

    /**
     * @return boolean
     */
    public function isMaintenance()
    {
        return $this->maintenance;
    }

    /**
     * @param boolean $maintenance
     */
    public function setMaintenance($maintenance)
    {
        $this->maintenance = (bool)$maintenance;
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
     */
    public function setPasswordSalt($passwordSalt)
    {
        $this->passwordSalt = $passwordSalt;
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
     */
    public function setResultsAsCards($resultsAsCards)
    {
        $this->resultsAsCards = (bool)$resultsAsCards;
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
     */
    public function setSessionTimeout($sessionTimeout)
    {
        $this->sessionTimeout = intval($sessionTimeout);
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
     */
    public function setSiteLang($siteLang)
    {
        $this->siteLang = $siteLang;
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
     */
    public function setSiteTheme($siteTheme)
    {
        $this->siteTheme = $siteTheme;
    }

    /**
     * @return int
     */
    public function getConfigVersion()
    {
        return $this->configVersion;
    }

    /**
     * @param int $configVersion
     */
    public function setConfigVersion($configVersion)
    {
        $this->configVersion = intval($configVersion);
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
     */
    public function setWikiEnabled($wikiEnabled)
    {
        $this->wikiEnabled = (bool)$wikiEnabled;
    }

    /**
     * @return array
     */
    public function getWikiFilter()
    {
        return is_array($this->wikiFilter) ? $this->wikiFilter : array();
    }

    /**
     * @param array $wikiFilter
     */
    public function setWikiFilter($wikiFilter)
    {
        $this->wikiFilter = $wikiFilter;
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
     */
    public function setWikiPageurl($wikiPageurl)
    {
        $this->wikiPageurl = $wikiPageurl;
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
     */
    public function setWikiSearchurl($wikiSearchurl)
    {
        $this->wikiSearchurl = $wikiSearchurl;
    }

    /**
     * @param int $configDate
     */
    public function setConfigDate($configDate)
    {
        $this->configDate = intval($configDate);
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
     */
    public function setLdapAds($ldapAds)
    {
        $this->ldapAds = (bool)$ldapAds;
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
     */
    public function setLdapBindPass($ldapBindPass)
    {
        $this->ldapBindPass = $ldapBindPass;
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
     */
    public function setPublinksImageEnabled($publinksImageEnabled)
    {
        $this->publinksImageEnabled = (bool)$publinksImageEnabled;
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
     */
    public function setHttpsEnabled($httpsEnabled)
    {
        $this->httpsEnabled = (bool)$httpsEnabled;
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
     */
    public function setChecknotices($checknotices)
    {
        $this->checknotices = $checknotices;
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
     */
    public function setAccountPassToImage($accountPassToImage)
    {
        $this->accountPassToImage = (bool)$accountPassToImage;
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
     */
    public function setUpgradeKey($upgradeKey)
    {
        $this->upgradeKey = $upgradeKey;
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
     */
    public function setDbPort($dbPort)
    {
        $this->dbPort = intval($dbPort);
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
     */
    public function setPublinksEnabled($publinksEnabled)
    {
        $this->publinksEnabled = (bool)$publinksEnabled;
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
}