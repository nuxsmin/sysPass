<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Config;


/**
 * Class configData
 *
 * @package SP\Config
 */
interface ConfigDataInterface
{
    /**
     * @return array
     */
    public function getAttributes(): array;

    /**
     * @return array
     */
    public function getLogEvents(): array;

    /**
     * @param array|null $logEvents
     *
     * @return ConfigDataInterface
     */
    public function setLogEvents(?array $logEvents): ConfigDataInterface;

    /**
     * @return boolean
     */
    public function isDokuwikiEnabled(): bool;

    /**
     * @param bool|null $dokuwikiEnabled
     *
     * @return $this
     */
    public function setDokuwikiEnabled(?bool $dokuwikiEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDokuwikiUrl(): ?string;

    /**
     * @param string|null $dokuwikiUrl
     *
     * @return $this
     */
    public function setDokuwikiUrl(?string $dokuwikiUrl): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDokuwikiUrlBase(): ?string;

    /**
     * @param string|null $dokuwikiUrlBase
     *
     * @return $this
     */
    public function setDokuwikiUrlBase(?string $dokuwikiUrlBase): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDokuwikiUser(): ?string;

    /**
     * @param string|null $dokuwikiUser
     *
     * @return $this
     */
    public function setDokuwikiUser(?string $dokuwikiUser): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDokuwikiPass(): ?string;

    /**
     * @param string|null $dokuwikiPass
     *
     * @return $this
     */
    public function setDokuwikiPass(?string $dokuwikiPass): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDokuwikiNamespace(): ?string;

    /**
     * @param string|null $dokuwikiNamespace
     *
     * @return $this
     */
    public function setDokuwikiNamespace(?string $dokuwikiNamespace): ConfigDataInterface;

    /**
     * @return int
     */
    public function getLdapDefaultGroup(): int;

    /**
     * @param int|null $ldapDefaultGroup
     *
     * @return $this
     */
    public function setLdapDefaultGroup(?int $ldapDefaultGroup): ConfigDataInterface;

    /**
     * @return int
     */
    public function getLdapDefaultProfile(): int;

    /**
     * @param int|null $ldapDefaultProfile
     *
     * @return $this
     */
    public function setLdapDefaultProfile(?int $ldapDefaultProfile): ConfigDataInterface;

    /**
     * @return boolean
     */
    public function isProxyEnabled(): bool;

    /**
     * @param boolean|null $proxyEnabled
     *
     * @return $this
     */
    public function setProxyEnabled(?bool $proxyEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getProxyServer(): ?string;

    /**
     * @param string|null $proxyServer
     *
     * @return $this
     */
    public function setProxyServer(?string $proxyServer): ConfigDataInterface;

    /**
     * @return int
     */
    public function getProxyPort(): int;

    /**
     * @param int|null $proxyPort
     *
     * @return $this
     */
    public function setProxyPort(?int $proxyPort): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getProxyUser(): ?string;

    /**
     * @param string|null $proxyUser
     *
     * @return $this
     */
    public function setProxyUser(?string $proxyUser): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getProxyPass(): ?string;

    /**
     * @param string|null $proxyPass
     *
     * @return $this
     */
    public function setProxyPass(?string $proxyPass): ConfigDataInterface;

    /**
     * @return int
     */
    public function getPublinksMaxViews(): int;

    /**
     * @param int|null $publinksMaxViews
     *
     * @return $this
     */
    public function setPublinksMaxViews(?int $publinksMaxViews): ConfigDataInterface;

    /**
     * @return int
     */
    public function getPublinksMaxTime(): int;

    /**
     * @param int|null $publinksMaxTime
     *
     * @return $this
     */
    public function setPublinksMaxTime(?int $publinksMaxTime): ConfigDataInterface;

    /**
     * @return boolean
     */
    public function isSyslogEnabled(): bool;

    /**
     * @param boolean|null $syslogEnabled
     *
     * @return $this
     */
    public function setSyslogEnabled(?bool $syslogEnabled): ConfigDataInterface;

    /**
     * @return boolean
     */
    public function isSyslogRemoteEnabled(): bool;

    /**
     * @param boolean|null $syslogRemoteEnabled
     *
     * @return $this
     */
    public function setSyslogRemoteEnabled(?bool $syslogRemoteEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getSyslogServer(): ?string;

    /**
     * @param string|null $syslogServer
     *
     * @return $this
     */
    public function setSyslogServer(?string $syslogServer): ConfigDataInterface;

    /**
     * @return int
     */
    public function getSyslogPort(): int;

    /**
     * @param int|null $syslogPort
     *
     * @return $this
     */
    public function setSyslogPort(?int $syslogPort): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getBackupHash(): ?string;

    /**
     * @param string|null $backup_hash
     *
     * @return $this
     */
    public function setBackupHash(?string $backup_hash): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getExportHash(): ?string;

    /**
     * @param string|null $export_hash
     *
     * @return $this
     */
    public function setExportHash(?string $export_hash): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapBindUser(): ?string;

    /**
     * @param string|null $ldapBindUser
     *
     * @return $this
     */
    public function setLdapBindUser(?string $ldapBindUser): ConfigDataInterface;

    /**
     * @return int
     */
    public function getAccountCount(): int;

    /**
     * @param int|null $accountCount
     *
     * @return $this
     */
    public function setAccountCount(?int $accountCount): ConfigDataInterface;

    /**
     * @return boolean
     */
    public function isAccountLink(): bool;

    /**
     * @param bool|null $accountLink
     *
     * @return $this
     */
    public function setAccountLink(?bool $accountLink): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isCheckUpdates(): bool;

    /**
     * @param bool|null $checkUpdates
     *
     * @return $this
     */
    public function setCheckUpdates(?bool $checkUpdates): ConfigDataInterface;

    /**
     * @return string
     */
    public function getConfigHash();

    /**
     * Generates a hash from current config options
     */
    public function setConfigHash(): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDbHost(): ?string;

    /**
     * @param string|null $dbHost
     *
     * @return $this
     */
    public function setDbHost(?string $dbHost): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDbName(): ?string;

    /**
     * @param string|null $dbName
     *
     * @return $this
     */
    public function setDbName(?string $dbName): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDbPass(): ?string;

    /**
     * @param string|null $dbPass
     *
     * @return $this
     */
    public function setDbPass(?string $dbPass): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDbUser(): ?string;

    /**
     * @param string|null $dbUser
     *
     * @return $this
     */
    public function setDbUser(?string $dbUser): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isDebug(): bool;

    /**
     * @param bool|null $debug
     *
     * @return $this
     */
    public function setDebug(?bool $debug): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isDemoEnabled(): bool;

    /**
     * @param bool|null $demoEnabled
     *
     * @return $this
     */
    public function setDemoEnabled(?bool $demoEnabled): ConfigDataInterface;

    /**
     * @return array
     */
    public function getFilesAllowedExts(): array;

    /**
     * @return int
     */
    public function getFilesAllowedSize(): int;

    /**
     * @param int|null $filesAllowedSize
     *
     * @return $this
     */
    public function setFilesAllowedSize(?int $filesAllowedSize): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isFilesEnabled(): bool;

    /**
     * @param bool|null $filesEnabled
     *
     * @return $this
     */
    public function setFilesEnabled(?bool $filesEnabled): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isGlobalSearch(): bool;

    /**
     * @param bool|null $globalSearch
     *
     * @return $this
     */
    public function setGlobalSearch(?bool $globalSearch): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isInstalled(): bool;

    /**
     * @param bool|null $installed
     *
     * @return $this
     */
    public function setInstalled(?bool $installed): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapBase(): ?string;

    /**
     * @param string|null $ldapBase
     *
     * @return $this
     */
    public function setLdapBase(?string $ldapBase): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isLdapEnabled(): bool;

    /**
     * @param bool|null $ldapEnabled
     *
     * @return $this
     */
    public function setLdapEnabled(?bool $ldapEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapGroup(): ?string;

    /**
     * @param string|null $ldapGroup
     *
     * @return $this
     */
    public function setLdapGroup(?string $ldapGroup): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapServer(): ?string;

    /**
     * @param string|null $ldapServer
     *
     * @return $this
     */
    public function setLdapServer(?string $ldapServer): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isLogEnabled(): bool;

    /**
     * @param bool|null $logEnabled
     *
     * @return $this
     */
    public function setLogEnabled(?bool $logEnabled): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isMailAuthenabled(): bool;

    /**
     * @param bool|null $mailAuthenabled
     *
     * @return $this
     */
    public function setMailAuthenabled(?bool $mailAuthenabled): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isMailEnabled(): bool;

    /**
     * @param bool|null $mailEnabled
     *
     * @return $this
     */
    public function setMailEnabled(?bool $mailEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getMailFrom(): ?string;

    /**
     * @param string|null $mailFrom
     *
     * @return $this
     */
    public function setMailFrom(?string $mailFrom): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getMailPass(): ?string;

    /**
     * @param string|null $mailPass
     *
     * @return $this
     */
    public function setMailPass(?string $mailPass): ConfigDataInterface;

    /**
     * @return int
     */
    public function getMailPort(): int;

    /**
     * @param int|null $mailPort
     *
     * @return $this
     */
    public function setMailPort(?int $mailPort): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isMailRequestsEnabled(): bool;

    /**
     * @param bool|null $mailRequestsEnabled
     *
     * @return $this
     */
    public function setMailRequestsEnabled(?bool $mailRequestsEnabled): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getMailSecurity(): ?string;

    /**
     * @param string|null $mailSecurity
     *
     * @return $this
     */
    public function setMailSecurity(?string $mailSecurity): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getMailServer(): ?string;

    /**
     * @param string|null $mailServer
     *
     * @return $this
     */
    public function setMailServer(?string $mailServer): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getMailUser(): ?string;

    /**
     * @param string|null $mailUser
     *
     * @return $this
     */
    public function setMailUser(?string $mailUser): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isMaintenance(): bool;

    /**
     * @param bool|null $maintenance
     *
     * @return $this
     */
    public function setMaintenance(?bool $maintenance): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getPasswordSalt(): ?string;

    /**
     * @param string|null $passwordSalt
     *
     * @return $this
     */
    public function setPasswordSalt(?string $passwordSalt): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isResultsAsCards(): bool;

    /**
     * @param bool|null $resultsAsCards
     *
     * @return $this
     */
    public function setResultsAsCards(?bool $resultsAsCards): ConfigDataInterface;

    /**
     * @return int
     */
    public function getSessionTimeout(): int;

    /**
     * @param int|null $sessionTimeout
     *
     * @return $this
     */
    public function setSessionTimeout(?int $sessionTimeout): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getSiteLang(): ?string;

    /**
     * @param string|null $siteLang
     *
     * @return $this
     */
    public function setSiteLang(?string $siteLang): ConfigDataInterface;

    /**
     * @return string
     */
    public function getSiteTheme(): string;

    /**
     * @param string|null $siteTheme
     *
     * @return $this
     */
    public function setSiteTheme(?string $siteTheme): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getConfigVersion(): ?string;

    /**
     * @param string|null $configVersion
     *
     * @return $this
     */
    public function setConfigVersion(?string $configVersion): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isWikiEnabled(): bool;

    /**
     * @param bool|null $wikiEnabled
     *
     * @return $this
     */
    public function setWikiEnabled(?bool $wikiEnabled): ConfigDataInterface;

    /**
     * @return array
     */
    public function getWikiFilter(): array;

    /**
     * @param array|null $wikiFilter
     *
     * @return $this
     */
    public function setWikiFilter(?array $wikiFilter): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getWikiPageurl(): ?string;

    /**
     * @param string|null $wikiPageurl
     *
     * @return $this
     */
    public function setWikiPageurl(?string $wikiPageurl): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getWikiSearchurl(): ?string;

    /**
     * @param string|null $wikiSearchurl
     *
     * @return $this
     */
    public function setWikiSearchurl(?string $wikiSearchurl): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapBindPass(): ?string;

    /**
     * @param string|null $ldapBindPass
     *
     * @return $this
     */
    public function setLdapBindPass(?string $ldapBindPass): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isPublinksImageEnabled(): bool;

    /**
     * @param bool|null $publinksImageEnabled
     *
     * @return $this
     */
    public function setPublinksImageEnabled(?bool $publinksImageEnabled): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isHttpsEnabled(): bool;

    /**
     * @param bool|null $httpsEnabled
     *
     * @return $this
     */
    public function setHttpsEnabled(?bool $httpsEnabled): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isCheckNotices(): bool;

    /**
     * @param bool|null $checknotices
     *
     * @return $this
     */
    public function setCheckNotices(?bool $checknotices): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isAccountPassToImage(): bool;

    /**
     * @param bool|null $accountPassToImage
     *
     * @return $this
     */
    public function setAccountPassToImage(?bool $accountPassToImage): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getUpgradeKey(): ?string;

    /**
     * @param string|null $upgradeKey
     *
     * @return $this
     */
    public function setUpgradeKey(?string $upgradeKey): ConfigDataInterface;

    /**
     * @return int
     */
    public function getDbPort(): int;

    /**
     * @param int|null $dbPort
     *
     * @return $this
     */
    public function setDbPort(?int $dbPort): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isPublinksEnabled(): bool;

    /**
     * @param bool|null $publinksEnabled
     *
     * @return $this
     */
    public function setPublinksEnabled(?bool $publinksEnabled): ConfigDataInterface;

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *        which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize();

    /**
     * @return string
     */
    public function getConfigSaver();

    /**
     * @param string|null $configSaver
     *
     * @return $this
     */
    public function setConfigSaver(?string $configSaver): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getDbSocket(): ?string;

    /**
     * @param string|null $dbSocket
     *
     * @return ConfigDataInterface
     */
    public function setDbSocket(?string $dbSocket): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isEncryptSession(): bool;

    /**
     * @param bool|null $encryptSession
     *
     * @return $this
     */
    public function setEncryptSession(?bool $encryptSession): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isAccountFullGroupAccess(): bool;

    /**
     * @param bool|null $accountFullGroupAccess
     *
     * @return $this
     */
    public function setAccountFullGroupAccess(?bool $accountFullGroupAccess): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isAuthBasicEnabled(): bool;

    /**
     * @param bool|null $authBasicEnabled
     */
    public function setAuthBasicEnabled(?bool $authBasicEnabled);

    /**
     * @return string|null
     */
    public function getAuthBasicDomain(): ?string;

    /**
     * @param string|null $authBasicDomain
     *
     * @return ConfigDataInterface
     */
    public function setAuthBasicDomain(?string $authBasicDomain): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled(): bool;

    /**
     * @param bool|null $authBasicAutoLoginEnabled
     *
     * @return ConfigDataInterface
     */
    public function setAuthBasicAutoLoginEnabled(?bool $authBasicAutoLoginEnabled): ConfigDataInterface;

    /**
     * @return int|null
     */
    public function getSsoDefaultGroup(): ?int;

    /**
     * @param int|null $ssoDefaultGroup
     *
     * @return ConfigDataInterface
     */
    public function setSsoDefaultGroup(?int $ssoDefaultGroup): ConfigDataInterface;

    /**
     * @return int|null
     */
    public function getSsoDefaultProfile(): ?int;

    /**
     * @param int|null $ssoDefaultProfile
     *
     * @return ConfigDataInterface
     */
    public function setSsoDefaultProfile(?int $ssoDefaultProfile): ConfigDataInterface;

    /**
     * @return array|null
     */
    public function getMailRecipients(): ?array;

    /**
     * @param array|null $mailRecipients
     *
     * @return ConfigDataInterface
     */
    public function setMailRecipients(?array $mailRecipients): ConfigDataInterface;

    /**
     * @return array|null
     */
    public function getMailEvents(): ?array;

    /**
     * @param array|null $mailEvents
     *
     * @return ConfigDataInterface
     */
    public function setMailEvents(?array $mailEvents): ConfigDataInterface;

    /**
     * @return string
     */
    public function getDatabaseVersion(): string;

    /**
     * @param string|null $databaseVersion
     *
     * @return ConfigDataInterface
     */
    public function setDatabaseVersion(?string $databaseVersion): ConfigDataInterface;

    /**
     * @return int
     */
    public function getConfigDate(): int;

    /**
     * @param int $configDate
     *
     * @return $this
     */
    public function setConfigDate(int $configDate): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isAccountExpireEnabled(): bool;

    /**
     * @param bool|null $accountExpireEnabled
     *
     * @return ConfigDataInterface
     */
    public function setAccountExpireEnabled(?bool $accountExpireEnabled): ConfigDataInterface;

    /**
     * @return int
     */
    public function getAccountExpireTime(): int;

    /**
     * @param int|null $accountExpireTime
     *
     * @return ConfigDataInterface
     */
    public function setAccountExpireTime(?int $accountExpireTime): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isLdapTlsEnabled(): bool;

    /**
     * @param bool|null $ldapTlsEnabled
     *
     * @return ConfigDataInterface
     */
    public function setLdapTlsEnabled(?bool $ldapTlsEnabled): ConfigDataInterface;

    /**
     * @return array
     */
    public function getFilesAllowedMime(): array;

    /**
     * @param array|null $filesAllowedMime
     *
     * @return ConfigDataInterface
     */
    public function setFilesAllowedMime(?array $filesAllowedMime): ConfigDataInterface;

    /**
     * @return int
     */
    public function getLdapType(): int;

    /**
     * @param int|null $ldapType
     *
     * @return ConfigDataInterface
     */
    public function setLdapType(?int $ldapType): ConfigDataInterface;

    /**
     * @return string
     */
    public function getAppVersion(): string;

    /**
     * @param string|null $appVersion
     *
     * @return ConfigDataInterface
     */
    public function setAppVersion(?string $appVersion): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getApplicationUrl(): ?string;

    /**
     * @param string|null $applicationUrl
     *
     * @return ConfigDataInterface
     */
    public function setApplicationUrl(?string $applicationUrl): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapFilterUserObject(): ?string;

    /**
     * @param string|null $filter
     *
     * @return ConfigDataInterface
     */
    public function setLdapFilterUserObject(?string $filter): ConfigDataInterface;

    /**
     * @return string|null
     */
    public function getLdapFilterGroupObject(): ?string;

    /**
     * @param string|null $filter
     *
     * @return ConfigDataInterface
     */
    public function setLdapFilterGroupObject(?string $filter): ConfigDataInterface;

    /**
     * @return array
     */
    public function getLdapFilterUserAttributes(): array;

    /**
     * @param array|null $attributes
     *
     * @return ConfigDataInterface
     */
    public function setLdapFilterUserAttributes(?array $attributes): ConfigDataInterface;

    /**
     * @return array
     */
    public function getLdapFilterGroupAttributes(): array;

    /**
     * @param array|null $attributes
     *
     * @return ConfigDataInterface
     */
    public function setLdapFilterGroupAttributes(?array $attributes): ConfigDataInterface;

    /**
     * @return bool
     */
    public function isLdapDatabaseEnabled(): bool;

    /**
     * @param bool|null $ldapDatabaseEnabled
     *
     * @return ConfigDataInterface
     */
    public function setLdapDatabaseEnabled(?bool $ldapDatabaseEnabled): ConfigDataInterface;
}