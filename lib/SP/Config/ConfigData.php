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
     * @param array|null $logEvents
     */
    public function setLogEvents(?array $logEvents)
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
     * @param bool|null $dokuwikiEnabled
     *
     * @return $this
     */
    public function setDokuwikiEnabled(?bool $dokuwikiEnabled): ConfigData
    {
        $this->set('dokuwikiEnabled', (bool)$dokuwikiEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDokuwikiUrl(): ?string
    {
        return $this->get('dokuwikiUrl');
    }

    /**
     * @param string|null $dokuwikiUrl
     *
     * @return $this
     */
    public function setDokuwikiUrl(?string $dokuwikiUrl): ConfigData
    {
        $this->set('dokuwikiUrl', $dokuwikiUrl);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDokuwikiUrlBase(): ?string
    {
        return $this->get('dokuwikiUrlBase');
    }

    /**
     * @param string|null $dokuwikiUrlBase
     *
     * @return $this
     */
    public function setDokuwikiUrlBase(?string $dokuwikiUrlBase): ConfigData
    {
        $this->set('dokuwikiUrlBase', $dokuwikiUrlBase);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDokuwikiUser(): ?string
    {
        return $this->get('dokuwikiUser');
    }

    /**
     * @param string|null $dokuwikiUser
     *
     * @return $this
     */
    public function setDokuwikiUser(?string $dokuwikiUser): ConfigData
    {
        $this->set('dokuwikiUser', $dokuwikiUser);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDokuwikiPass(): ?string
    {
        return $this->get('dokuwikiPass');
    }

    /**
     * @param string|null $dokuwikiPass
     *
     * @return $this
     */
    public function setDokuwikiPass(?string $dokuwikiPass): ConfigData
    {
        $this->set('dokuwikiPass', $dokuwikiPass);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDokuwikiNamespace(): ?string
    {
        return $this->get('dokuwikiNamespace');
    }

    /**
     * @param string|null $dokuwikiNamespace
     *
     * @return $this
     */
    public function setDokuwikiNamespace(?string $dokuwikiNamespace): ConfigData
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
     * @param int|null $ldapDefaultGroup
     *
     * @return $this
     */
    public function setLdapDefaultGroup(?int $ldapDefaultGroup): ConfigData
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
     * @param int|null $ldapDefaultProfile
     *
     * @return $this
     */
    public function setLdapDefaultProfile(?int $ldapDefaultProfile): ConfigData
    {
        $this->set('ldapDefaultProfile', (int)$ldapDefaultProfile);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isProxyEnabled(): bool
    {
        return $this->get('proxyEnabled', false);
    }

    /**
     * @param boolean|null $proxyEnabled
     *
     * @return $this
     */
    public function setProxyEnabled(?bool $proxyEnabled): ConfigData
    {
        $this->set('proxyEnabled', (bool)$proxyEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProxyServer(): ?string
    {
        return $this->get('proxyServer');
    }

    /**
     * @param string|null $proxyServer
     *
     * @return $this
     */
    public function setProxyServer(?string $proxyServer): ConfigData
    {
        $this->set('proxyServer', $proxyServer);

        return $this;
    }

    /**
     * @return int
     */
    public function getProxyPort(): int
    {
        return $this->get('proxyPort', 8080);
    }

    /**
     * @param int|null $proxyPort
     *
     * @return $this
     */
    public function setProxyPort(?int $proxyPort): ConfigData
    {
        $this->set('proxyPort', (int)$proxyPort);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProxyUser(): ?string
    {
        return $this->get('proxyUser');
    }

    /**
     * @param string|null $proxyUser
     *
     * @return $this
     */
    public function setProxyUser(?string $proxyUser): ConfigData
    {
        $this->set('proxyUser', $proxyUser);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getProxyPass(): ?string
    {
        return $this->get('proxyPass');
    }

    /**
     * @param string|null $proxyPass
     *
     * @return $this
     */
    public function setProxyPass(?string $proxyPass): ConfigData
    {
        $this->set('proxyPass', $proxyPass);

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxViews(): int
    {
        return $this->get('publinksMaxViews', self::PUBLIC_LINK_MAX_VIEWS);
    }


    /**
     * @param int|null $publinksMaxViews
     *
     * @return $this
     */
    public function setPublinksMaxViews(?int $publinksMaxViews): ConfigData
    {
        $this->set('publinksMaxViews', (int)$publinksMaxViews);

        return $this;
    }

    /**
     * @return int
     */
    public function getPublinksMaxTime(): int
    {
        return $this->get('publinksMaxTime', self::PUBLIC_LINK_MAX_TIME);
    }

    /**
     * @param int|null $publinksMaxTime
     *
     * @return $this
     */
    public function setPublinksMaxTime(?int $publinksMaxTime): ConfigData
    {
        $this->set('publinksMaxTime', (int)$publinksMaxTime);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogEnabled(): bool
    {
        return $this->get('syslogEnabled', false);
    }

    /**
     * @param boolean|null $syslogEnabled
     *
     * @return $this
     */
    public function setSyslogEnabled(?bool $syslogEnabled): ConfigData
    {
        $this->set('syslogEnabled', (bool)$syslogEnabled);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isSyslogRemoteEnabled(): bool
    {
        return $this->get('syslogRemoteEnabled', false);
    }

    /**
     * @param boolean|null $syslogRemoteEnabled
     *
     * @return $this
     */
    public function setSyslogRemoteEnabled(?bool $syslogRemoteEnabled): ConfigData
    {
        $this->set('syslogRemoteEnabled', (bool)$syslogRemoteEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSyslogServer(): ?string
    {
        return $this->get('syslogServer');
    }

    /**
     * @param string|null $syslogServer
     *
     * @return $this
     */
    public function setSyslogServer(?string $syslogServer): ConfigData
    {
        $this->set('syslogServer', $syslogServer);

        return $this;
    }

    /**
     * @return int
     */
    public function getSyslogPort(): int
    {
        return $this->get('syslogPort', self::SYSLOG_PORT);
    }

    /**
     * @param int|null $syslogPort
     *
     * @return $this
     */
    public function setSyslogPort(?int $syslogPort): ConfigData
    {
        $this->set('syslogPort', (int)$syslogPort);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBackupHash(): ?string
    {
        return $this->get('backup_hash');
    }

    /**
     * @param string|null $backup_hash
     *
     * @return $this
     */
    public function setBackupHash(?string $backup_hash): ConfigData
    {
        $this->set('backup_hash', $backup_hash);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExportHash(): ?string
    {
        return $this->get('export_hash');
    }

    /**
     * @param string|null $export_hash
     *
     * @return $this
     */
    public function setExportHash(?string $export_hash): ConfigData
    {
        $this->set('export_hash', $export_hash);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLdapBindUser(): ?string
    {
        return $this->get('ldapBindUser');
    }

    /**
     * @param string|null $ldapBindUser
     *
     * @return $this
     */
    public function setLdapBindUser(?string $ldapBindUser): ConfigData
    {
        $this->set('ldapBindUser', $ldapBindUser);

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountCount(): int
    {
        return $this->get('accountCount', self::ACCOUNT_COUNT);
    }

    /**
     * @param int|null $accountCount
     *
     * @return $this
     */
    public function setAccountCount(?int $accountCount): ConfigData
    {
        $this->set('accountCount', (int)$accountCount);

        return $this;
    }

    /**
     * @return boolean
     */
    public function isAccountLink(): bool
    {
        return $this->get('accountLink', true);
    }

    /**
     * @param bool|null $accountLink
     *
     * @return $this
     */
    public function setAccountLink(?bool $accountLink): ConfigData
    {
        $this->set('accountLink', (bool)$accountLink);

        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckUpdates(): bool
    {
        return $this->get('checkUpdates', false);
    }

    /**
     * @param bool|null $checkUpdates
     *
     * @return $this
     */
    public function setCheckUpdates(?bool $checkUpdates): ConfigData
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
    public function setConfigHash(): ConfigData
    {
        $this->set('configHash', sha1(serialize($this->attributes)));

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbHost(): ?string
    {
        return $this->get('dbHost');
    }

    /**
     * @param string|null $dbHost
     *
     * @return $this
     */
    public function setDbHost(?string $dbHost): ConfigData
    {
        $this->set('dbHost', $dbHost);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbName(): ?string
    {
        return $this->get('dbName');
    }

    /**
     * @param string|null $dbName
     *
     * @return $this
     */
    public function setDbName(?string $dbName): ConfigData
    {
        $this->set('dbName', $dbName);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbPass(): ?string
    {
        return $this->get('dbPass');
    }

    /**
     * @param string|null $dbPass
     *
     * @return $this
     */
    public function setDbPass(?string $dbPass): ConfigData
    {
        $this->set('dbPass', $dbPass);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbUser(): ?string
    {
        return $this->get('dbUser');
    }

    /**
     * @param string|null $dbUser
     *
     * @return $this
     */
    public function setDbUser(?string $dbUser): ConfigData
    {
        $this->set('dbUser', $dbUser);

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->get('debug', false);
    }

    /**
     * @param bool|null $debug
     *
     * @return $this
     */
    public function setDebug(?bool $debug): ConfigData
    {
        $this->set('debug', (bool)$debug);

        return $this;
    }

    /**
     * @return bool
     */
    public function isDemoEnabled(): bool
    {
        return $this->get('demoEnabled', false);
    }

    /**
     * @param bool|null $demoEnabled
     *
     * @return $this
     */
    public function setDemoEnabled(?bool $demoEnabled): ConfigData
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
    public function getFilesAllowedSize(): int
    {
        return $this->get('filesAllowedSize', self::FILES_ALLOWED_SIZE);
    }

    /**
     * @param int|null $filesAllowedSize
     *
     * @return $this
     */
    public function setFilesAllowedSize(?int $filesAllowedSize): ConfigData
    {
        $this->set('filesAllowedSize', (int)$filesAllowedSize);

        return $this;
    }

    /**
     * @return bool
     */
    public function isFilesEnabled(): bool
    {
        return $this->get('filesEnabled', true);
    }

    /**
     * @param bool|null $filesEnabled
     *
     * @return $this
     */
    public function setFilesEnabled(?bool $filesEnabled): ConfigData
    {
        $this->set('filesEnabled', (bool)$filesEnabled);

        return $this;
    }

    /**
     * @return bool
     */
    public function isGlobalSearch(): bool
    {
        return $this->get('globalSearch', true);
    }

    /**
     * @param bool|null $globalSearch
     *
     * @return $this
     */
    public function setGlobalSearch(?bool $globalSearch): ConfigData
    {
        $this->set('globalSearch', (bool)$globalSearch);

        return $this;
    }

    /**
     * @return bool
     */
    public function isInstalled(): bool
    {
        return $this->get('installed', false);
    }

    /**
     * @param bool|null $installed
     *
     * @return $this
     */
    public function setInstalled(?bool $installed): ConfigData
    {
        $this->set('installed', (bool)$installed);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLdapBase(): ?string
    {
        return $this->get('ldapBase');
    }

    /**
     * @param string|null $ldapBase
     *
     * @return $this
     */
    public function setLdapBase(?string $ldapBase): ConfigData
    {
        $this->set('ldapBase', $ldapBase);

        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapEnabled(): bool
    {
        return $this->get('ldapEnabled', false);
    }

    /**
     * @param bool|null $ldapEnabled
     *
     * @return $this
     */
    public function setLdapEnabled(?bool $ldapEnabled): ConfigData
    {
        $this->set('ldapEnabled', (bool)$ldapEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLdapGroup(): ?string
    {
        return $this->get('ldapGroup');
    }

    /**
     * @param string|null $ldapGroup
     *
     * @return $this
     */
    public function setLdapGroup(?string $ldapGroup): ConfigData
    {
        $this->set('ldapGroup', $ldapGroup);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLdapServer(): ?string
    {
        return $this->get('ldapServer');
    }

    /**
     * @param string|null $ldapServer
     *
     * @return $this
     */
    public function setLdapServer(?string $ldapServer): ConfigData
    {
        $this->set('ldapServer', $ldapServer);

        return $this;
    }

    /**
     * @return bool
     */
    public function isLogEnabled(): bool
    {
        return $this->get('logEnabled', true);
    }

    /**
     * @param bool|null $logEnabled
     *
     * @return $this
     */
    public function setLogEnabled(?bool $logEnabled): ConfigData
    {
        $this->set('logEnabled', (bool)$logEnabled);

        return $this;
    }

    /**
     * @return bool
     */
    public function isMailAuthenabled(): bool
    {
        return $this->get('mailAuthenabled', false);
    }

    /**
     * @param bool|null $mailAuthenabled
     *
     * @return $this
     */
    public function setMailAuthenabled(?bool $mailAuthenabled): ConfigData
    {
        $this->set('mailAuthenabled', (bool)$mailAuthenabled);

        return $this;
    }

    /**
     * @return bool
     */
    public function isMailEnabled(): bool
    {
        return $this->get('mailEnabled', false);
    }

    /**
     * @param bool|null $mailEnabled
     *
     * @return $this
     */
    public function setMailEnabled(?bool $mailEnabled): ConfigData
    {
        $this->set('mailEnabled', (bool)$mailEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailFrom(): ?string
    {
        return $this->get('mailFrom');
    }

    /**
     * @param string|null $mailFrom
     *
     * @return $this
     */
    public function setMailFrom(?string $mailFrom): ConfigData
    {
        $this->set('mailFrom', $mailFrom);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailPass(): ?string
    {
        return $this->get('mailPass');
    }

    /**
     * @param string|null $mailPass
     *
     * @return $this
     */
    public function setMailPass(?string $mailPass): ConfigData
    {
        $this->set('mailPass', $mailPass);

        return $this;
    }

    /**
     * @return int
     */
    public function getMailPort(): int
    {
        return $this->get('mailPort', self::MAIL_PORT);
    }

    /**
     * @param int|null $mailPort
     *
     * @return $this
     */
    public function setMailPort(?int $mailPort): ConfigData
    {
        $this->set('mailPort', (int)$mailPort);

        return $this;
    }

    /**
     * @return bool
     */
    public function isMailRequestsEnabled(): bool
    {
        return $this->get('mailRequestsEnabled', false);
    }

    /**
     * @param bool|null $mailRequestsEnabled
     *
     * @return $this
     */
    public function setMailRequestsEnabled(?bool $mailRequestsEnabled): ConfigData
    {
        $this->set('mailRequestsEnabled', (bool)$mailRequestsEnabled);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailSecurity(): ?string
    {
        return $this->get('mailSecurity');
    }

    /**
     * @param string|null $mailSecurity
     *
     * @return $this
     */
    public function setMailSecurity(?string $mailSecurity): ConfigData
    {
        $this->set('mailSecurity', $mailSecurity);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailServer(): ?string
    {
        return $this->get('mailServer');
    }

    /**
     * @param string|null $mailServer
     *
     * @return $this
     */
    public function setMailServer(?string $mailServer): ConfigData
    {
        $this->set('mailServer', $mailServer);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailUser(): ?string
    {
        return $this->get('mailUser');
    }

    /**
     * @param string|null $mailUser
     *
     * @return $this
     */
    public function setMailUser(?string $mailUser): ConfigData
    {
        $this->set('mailUser', $mailUser);

        return $this;
    }

    /**
     * @return bool
     */
    public function isMaintenance(): bool
    {
        return $this->get('maintenance', false);
    }

    /**
     * @param bool|null $maintenance
     *
     * @return $this
     */
    public function setMaintenance(?bool $maintenance): ConfigData
    {
        $this->set('maintenance', (bool)$maintenance);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPasswordSalt(): ?string
    {
        return $this->get('passwordSalt');
    }

    /**
     * @param string|null $passwordSalt
     *
     * @return $this
     */
    public function setPasswordSalt(?string $passwordSalt): ConfigData
    {
        $this->set('passwordSalt', $passwordSalt);

        return $this;
    }

    /**
     * @return bool
     */
    public function isResultsAsCards(): bool
    {
        return $this->get('resultsAsCards', false);
    }

    /**
     * @param bool|null $resultsAsCards
     *
     * @return $this
     */
    public function setResultsAsCards(?bool $resultsAsCards): ConfigData
    {
        $this->set('resultsAsCards', (bool)$resultsAsCards);

        return $this;
    }

    /**
     * @return int
     */
    public function getSessionTimeout(): int
    {
        return $this->get('sessionTimeout', self::SESSION_TIMEOUT);
    }

    /**
     * @param int|null $sessionTimeout
     *
     * @return $this
     */
    public function setSessionTimeout(?int $sessionTimeout): ConfigData
    {
        $this->set('sessionTimeout', (int)$sessionTimeout);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSiteLang(): ?string
    {
        return $this->get('siteLang');
    }

    /**
     * @param string|null $siteLang
     *
     * @return $this
     */
    public function setSiteLang(?string $siteLang): ConfigData
    {
        $this->set('siteLang', $siteLang);

        return $this;
    }

    /**
     * @return string
     */
    public function getSiteTheme(): string
    {
        return $this->get('siteTheme', self::SITE_THEME);
    }

    /**
     * @param string|null $siteTheme
     *
     * @return $this
     */
    public function setSiteTheme(?string $siteTheme): ConfigData
    {
        $this->set('siteTheme', $siteTheme);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getConfigVersion(): ?string
    {
        return (string)$this->get('configVersion');
    }

    /**
     * @param string|null $configVersion
     *
     * @return $this
     */
    public function setConfigVersion(?string $configVersion): ConfigData
    {
        $this->set('configVersion', $configVersion);

        return $this;
    }

    /**
     * @return bool
     */
    public function isWikiEnabled(): bool
    {
        return $this->get('wikiEnabled', false);
    }

    /**
     * @param bool|null $wikiEnabled
     *
     * @return $this
     */
    public function setWikiEnabled(?bool $wikiEnabled): ConfigData
    {
        $this->set('wikiEnabled', (bool)$wikiEnabled);

        return $this;
    }

    /**
     * @return array
     */
    public function getWikiFilter(): array
    {
        return $this->get('wikiFilter', []);
    }

    /**
     * @param array|null $wikiFilter
     *
     * @return $this
     */
    public function setWikiFilter(?array $wikiFilter): ConfigData
    {
        $this->set('wikiFilter', $wikiFilter ?: []);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWikiPageurl(): ?string
    {
        return $this->get('wikiPageurl');
    }

    /**
     * @param string|null $wikiPageurl
     *
     * @return $this
     */
    public function setWikiPageurl(?string $wikiPageurl): ConfigData
    {
        $this->set('wikiPageurl', $wikiPageurl);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWikiSearchurl(): ?string
    {
        return $this->get('wikiSearchurl');
    }

    /**
     * @param string|null $wikiSearchurl
     *
     * @return $this
     */
    public function setWikiSearchurl(?string $wikiSearchurl): ConfigData
    {
        $this->set('wikiSearchurl', $wikiSearchurl);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLdapBindPass(): ?string
    {
        return $this->get('ldapBindPass');
    }

    /**
     * @param string|null $ldapBindPass
     *
     * @return $this
     */
    public function setLdapBindPass(?string $ldapBindPass): ConfigData
    {
        $this->set('ldapBindPass', $ldapBindPass);

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublinksImageEnabled(): bool
    {
        return $this->get('publinksImageEnabled', false);
    }

    /**
     * @param bool|null $publinksImageEnabled
     *
     * @return $this
     */
    public function setPublinksImageEnabled(?bool $publinksImageEnabled): ConfigData
    {
        $this->set('publinksImageEnabled', (bool)$publinksImageEnabled);

        return $this;
    }

    /**
     * @return bool
     */
    public function isHttpsEnabled(): bool
    {
        return $this->get('httpsEnabled', false);
    }

    /**
     * @param bool|null $httpsEnabled
     *
     * @return $this
     */
    public function setHttpsEnabled(?bool $httpsEnabled): ConfigData
    {
        $this->set('httpsEnabled', (bool)$httpsEnabled);

        return $this;
    }

    /**
     * @return bool
     */
    public function isCheckNotices(): bool
    {
        return $this->get('checkNotices', false);
    }

    /**
     * @param bool|null $checknotices
     *
     * @return $this
     */
    public function setCheckNotices(?bool $checknotices): ConfigData
    {
        $this->set('checkNotices', (bool)$checknotices);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountPassToImage(): bool
    {
        return $this->get('accountPassToImage', false);
    }

    /**
     * @param bool|null $accountPassToImage
     *
     * @return $this
     */
    public function setAccountPassToImage(?bool $accountPassToImage): ConfigData
    {
        $this->set('accountPassToImage', (bool)$accountPassToImage);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUpgradeKey(): ?string
    {
        return $this->get('upgradeKey');
    }

    /**
     * @param string|null $upgradeKey
     *
     * @return $this
     */
    public function setUpgradeKey(?string $upgradeKey): ConfigData
    {
        $this->set('upgradeKey', $upgradeKey);

        return $this;
    }

    /**
     * @return int
     */
    public function getDbPort(): int
    {
        return $this->get('dbPort', self::DB_PORT);
    }

    /**
     * @param int|null $dbPort
     *
     * @return $this
     */
    public function setDbPort(?int $dbPort): ConfigData
    {
        $this->set('dbPort', (int)$dbPort);

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublinksEnabled(): bool
    {
        return $this->get('publinksEnabled', false);
    }

    /**
     * @param bool|null $publinksEnabled
     *
     * @return $this
     */
    public function setPublinksEnabled(?bool $publinksEnabled): ConfigData
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
     * @param string|null $configSaver
     *
     * @return $this
     */
    public function setConfigSaver(?string $configSaver): ConfigData
    {
        $this->set('configSaver', $configSaver);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDbSocket(): ?string
    {
        return $this->get('dbSocket');
    }

    /**
     * @param string|null $dbSocket
     */
    public function setDbSocket(?string $dbSocket)
    {
        $this->set('dbSocket', $dbSocket);
    }

    /**
     * @return bool
     */
    public function isEncryptSession(): bool
    {
        return (bool)$this->get('encryptSession', false);
    }

    /**
     * @param bool|null $encryptSession
     *
     * @return $this
     */
    public function setEncryptSession(?bool $encryptSession): ConfigData
    {
        $this->set('encryptSession', (bool)$encryptSession);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountFullGroupAccess(): bool
    {
        return (bool)$this->get('accountFullGroupAccess', false);
    }

    /**
     * @param bool|null $accountFullGroupAccess
     *
     * @return $this
     */
    public function setAccountFullGroupAccess(?bool $accountFullGroupAccess): ConfigData
    {
        $this->set('accountFullGroupAccess', (bool)$accountFullGroupAccess);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthBasicEnabled(): bool
    {
        return (bool)$this->get('authBasicEnabled', true);
    }

    /**
     * @param bool|null $authBasicEnabled
     */
    public function setAuthBasicEnabled(?bool $authBasicEnabled)
    {
        $this->set('authBasicEnabled', (bool)$authBasicEnabled);
    }

    /**
     * @return string|null
     */
    public function getAuthBasicDomain(): ?string
    {
        return $this->get('authBasicDomain');
    }

    /**
     * @param string|null $authBasicDomain
     */
    public function setAuthBasicDomain(?string $authBasicDomain)
    {
        $this->set('authBasicDomain', $authBasicDomain);
    }

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled(): bool
    {
        return (bool)$this->get('authBasicAutoLoginEnabled', true);
    }

    /**
     * @param bool|null $authBasicAutoLoginEnabled
     */
    public function setAuthBasicAutoLoginEnabled(?bool $authBasicAutoLoginEnabled)
    {
        $this->set('authBasicAutoLoginEnabled', $authBasicAutoLoginEnabled);
    }

    /**
     * @return int|null
     */
    public function getSsoDefaultGroup(): ?int
    {
        return $this->get('ssoDefaultGroup');
    }

    /**
     * @param int|null $ssoDefaultGroup
     */
    public function setSsoDefaultGroup(?int $ssoDefaultGroup)
    {
        $this->set('ssoDefaultGroup', $ssoDefaultGroup);
    }

    /**
     * @return int|null
     */
    public function getSsoDefaultProfile(): ?int
    {
        return $this->get('ssoDefaultProfile');
    }

    /**
     * @param int|null $ssoDefaultProfile
     */
    public function setSsoDefaultProfile(?int $ssoDefaultProfile)
    {
        $this->set('ssoDefaultProfile', $ssoDefaultProfile);
    }

    /**
     * @return array
     */
    public function getMailRecipients(): array
    {
        return $this->get('mailRecipients', []);
    }

    /**
     * @param array|null $mailRecipients
     */
    public function setMailRecipients(?array $mailRecipients)
    {
        $this->set('mailRecipients', $mailRecipients ?: []);
    }

    /**
     * @return array
     */
    public function getMailEvents(): array
    {
        return $this->get('mailEvents', []);
    }

    /**
     * @param array|null $mailEvents
     */
    public function setMailEvents(?array $mailEvents)
    {
        $this->set('mailEvents', $mailEvents ?: []);
    }

    /**
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return (string)$this->get('databaseVersion');
    }

    /**
     * @param string|null $databaseVersion
     *
     * @return ConfigData
     */
    public function setDatabaseVersion(?string $databaseVersion): ConfigData
    {
        $this->set('databaseVersion', $databaseVersion);

        return $this;
    }

    /**
     * @return int
     */
    public function getConfigDate(): int
    {
        return (int)$this->get('configDate');
    }

    /**
     * @param int $configDate
     *
     * @return $this
     */
    public function setConfigDate(int $configDate): ConfigData
    {
        $this->set('configDate', (int)$configDate);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAccountExpireEnabled(): bool
    {
        return (bool)$this->get('accountExpireEnabled', false);
    }

    /**
     * @param bool|null $accountExpireEnabled
     *
     * @return ConfigData
     */
    public function setAccountExpireEnabled(?bool $accountExpireEnabled): ConfigData
    {
        $this->set('accountExpireEnabled', (bool)$accountExpireEnabled);

        return $this;
    }

    /**
     * @return int
     */
    public function getAccountExpireTime(): int
    {
        return $this->get('accountExpireTime', self::ACCOUNT_EXPIRE_TIME);
    }

    /**
     * @param int|null $accountExpireTime
     *
     * @return ConfigData
     */
    public function setAccountExpireTime(?int $accountExpireTime): ConfigData
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
     * @param bool|null $ldapTlsEnabled
     */
    public function setLdapTlsEnabled(?bool $ldapTlsEnabled)
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
     * @param array|null $filesAllowedMime
     */
    public function setFilesAllowedMime(?array $filesAllowedMime)
    {
        $this->set('filesAllowedMime', $filesAllowedMime ?: []);
    }

    /**
     * @return int
     */
    public function getLdapType(): int
    {
        return (int)$this->get('ldapType');
    }

    /**
     * @param int|null $ldapType
     */
    public function setLdapType(?int $ldapType)
    {
        $this->set('ldapType', (int)$ldapType);
    }

    /**
     * @return string
     */
    public function getAppVersion(): string
    {
        return $this->get('appVersion');
    }

    /**
     * @param string|null $appVersion
     */
    public function setAppVersion(?string $appVersion)
    {
        $this->set('appVersion', $appVersion);
    }

    /**
     * @return string|null
     */
    public function getApplicationUrl(): ?string
    {
        return $this->get('applicationUrl');
    }

    /**
     * @param string|null $applicationUrl
     */
    public function setApplicationUrl(?string $applicationUrl)
    {
        $this->set('applicationUrl', $applicationUrl ? rtrim($applicationUrl, '/') : null);
    }

    /**
     * @return string|null
     */
    public function getLdapFilterUserObject(): ?string
    {
        return $this->get('ldapFilterUserObject');
    }

    /**
     * @param string|null $filter
     */
    public function setLdapFilterUserObject(?string $filter)
    {
        $this->set('ldapFilterUserObject', $filter);
    }

    /**
     * @return string|null
     */
    public function getLdapFilterGroupObject(): ?string
    {
        return $this->get('ldapFilterGroupObject');
    }

    /**
     * @param string|null $filter
     */
    public function setLdapFilterGroupObject(?string $filter)
    {
        $this->set('ldapFilterGroupObject', $filter);
    }

    /**
     * @return array|null
     */
    public function getLdapFilterUserAttributes(): ?array
    {
        return $this->get('ldapFilterUserAttributes');
    }

    /**
     * @param array|null $attributes
     */
    public function setLdapFilterUserAttributes(?array $attributes)
    {
        $this->set('ldapFilterUserAttributes', $attributes ?: []);
    }

    /**
     * @return array|null
     */
    public function getLdapFilterGroupAttributes(): ?array
    {
        return $this->get('ldapFilterGroupAttributes');
    }

    /**
     * @param array|null $attributes
     */
    public function setLdapFilterGroupAttributes(?array $attributes)
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
     * @param bool|null $ldapDatabaseEnabled
     */
    public function setLdapDatabaseEnabled(?bool $ldapDatabaseEnabled)
    {
        $this->set('ldapDatabaseEnabled', (int)$ldapDatabaseEnabled);
    }

}
