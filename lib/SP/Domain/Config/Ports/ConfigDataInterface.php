<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Config\Ports;

/**
 * Interface ConfigDataInterface
 */
interface ConfigDataInterface
{
    public const LOG_EVENTS                    = 'logEvents';
    public const DOKUWIKI_ENABLED              = 'dokuwikiEnabled';
    public const DOKUWIKI_URL                  = 'dokuwikiUrl';
    public const DOKUWIKI_URL_BASE             = 'dokuwikiUrlBase';
    public const DOKUWIKI_USER                 = 'dokuwikiUser';
    public const DOKUWIKI_PASS                 = 'dokuwikiPass';
    public const DOKUWIKI_NAMESPACE            = 'dokuwikiNamespace';
    public const LDAP_DEFAULT_GROUP            = 'ldapDefaultGroup';
    public const LDAP_DEFAULT_PROFILE          = 'ldapDefaultProfile';
    public const PROXY_ENABLED                 = 'proxyEnabled';
    public const PROXY_SERVER                  = 'proxyServer';
    public const PROXY_PORT                    = 'proxyPort';
    public const PROXY_USER                    = 'proxyUser';
    public const PROXY_PASS                    = 'proxyPass';
    public const PUBLINKS_MAX_VIEWS            = 'publinksMaxViews';
    public const PUBLINKS_MAX_TIME             = 'publinksMaxTime';
    public const SYSLOG_ENABLED                = 'syslogEnabled';
    public const SYSLOG_REMOTE_ENABLED         = 'syslogRemoteEnabled';
    public const SYSLOG_SERVER                 = 'syslogServer';
    public const SYSLOG_PORT                   = 'syslogPort';
    public const BACKUP_HASH                   = 'backup_hash';
    public const EXPORT_HASH                   = 'export_hash';
    public const LDAP_BIND_USER                = 'ldapBindUser';
    public const ACCOUNT_COUNT                 = 'accountCount';
    public const ACCOUNT_LINK                  = 'accountLink';
    public const CHECK_UPDATES                 = 'checkUpdates';
    public const CONFIG_HASH                   = 'configHash';
    public const DB_HOST                       = 'dbHost';
    public const DB_NAME                       = 'dbName';
    public const DB_PASS                       = 'dbPass';
    public const DB_USER                       = 'dbUser';
    public const DEBUG                         = 'debug';
    public const DEMO_ENABLED                  = 'demoEnabled';
    public const FILES_ALLOWED_EXTS            = 'filesAllowedExts';
    public const FILES_ALLOWED_SIZE            = 'filesAllowedSize';
    public const FILES_ENABLED                 = 'filesEnabled';
    public const GLOBAL_SEARCH                 = 'globalSearch';
    public const INSTALLED                     = 'installed';
    public const LDAP_BASE                     = 'ldapBase';
    public const LDAP_ENABLED                  = 'ldapEnabled';
    public const LDAP_GROUP                    = 'ldapGroup';
    public const LDAP_SERVER                   = 'ldapServer';
    public const LOG_ENABLED                   = 'logEnabled';
    public const MAIL_AUTHENABLED              = 'mailAuthenabled';
    public const MAIL_ENABLED                  = 'mailEnabled';
    public const MAIL_FROM                     = 'mailFrom';
    public const MAIL_PASS                     = 'mailPass';
    public const MAIL_PORT                     = 'mailPort';
    public const MAIL_REQUESTS_ENABLED         = 'mailRequestsEnabled';
    public const MAIL_SECURITY                 = 'mailSecurity';
    public const MAIL_SERVER                   = 'mailServer';
    public const MAIL_USER                     = 'mailUser';
    public const MAINTENANCE                   = 'maintenance';
    public const PASSWORD_SALT                 = 'passwordSalt';
    public const RESULTS_AS_CARDS              = 'resultsAsCards';
    public const SESSION_TIMEOUT               = 'sessionTimeout';
    public const SITE_LANG                     = 'siteLang';
    public const SITE_THEME                    = 'siteTheme';
    public const CONFIG_VERSION                = 'configVersion';
    public const WIKI_ENABLED                  = 'wikiEnabled';
    public const WIKI_FILTER                   = 'wikiFilter';
    public const WIKI_PAGEURL                  = 'wikiPageurl';
    public const WIKI_SEARCHURL                = 'wikiSearchurl';
    public const LDAP_BIND_PASS                = 'ldapBindPass';
    public const PUBLINKS_IMAGE_ENABLED        = 'publinksImageEnabled';
    public const HTTPS_ENABLED                 = 'httpsEnabled';
    public const CHECK_NOTICES                 = 'checkNotices';
    public const ACCOUNT_PASS_TO_IMAGE         = 'accountPassToImage';
    public const UPGRADE_KEY                   = 'upgradeKey';
    public const DB_PORT                       = 'dbPort';
    public const PUBLINKS_ENABLED              = 'publinksEnabled';
    public const CONFIG_SAVER                  = 'configSaver';
    public const DB_SOCKET                     = 'dbSocket';
    public const ENCRYPT_SESSION               = 'encryptSession';
    public const ACCOUNT_FULL_GROUP_ACCESS     = 'accountFullGroupAccess';
    public const AUTH_BASIC_ENABLED            = 'authBasicEnabled';
    public const AUTH_BASIC_DOMAIN             = 'authBasicDomain';
    public const AUTH_BASIC_AUTO_LOGIN_ENABLED = 'authBasicAutoLoginEnabled';
    public const SSO_DEFAULT_GROUP             = 'ssoDefaultGroup';
    public const SSO_DEFAULT_PROFILE           = 'ssoDefaultProfile';
    public const MAIL_RECIPIENTS               = 'mailRecipients';
    public const MAIL_EVENTS                   = 'mailEvents';
    public const DATABASE_VERSION              = 'databaseVersion';
    public const CONFIG_DATE                   = 'configDate';
    public const ACCOUNT_EXPIRE_ENABLED        = 'accountExpireEnabled';
    public const ACCOUNT_EXPIRE_TIME           = 'accountExpireTime';
    public const LDAP_TLS_ENABLED              = 'ldapTlsEnabled';
    public const FILES_ALLOWED_MIME            = 'filesAllowedMime';
    public const LDAP_TYPE                     = 'ldapType';
    public const APP_VERSION                   = 'appVersion';
    public const APPLICATION_URL               = 'applicationUrl';
    public const LDAP_FILTER_USER_OBJECT       = 'ldapFilterUserObject';
    public const LDAP_FILTER_GROUP_OBJECT      = 'ldapFilterGroupObject';
    public const LDAP_FILTER_USER_ATTRIBUTES   = 'ldapFilterUserAttributes';
    public const LDAP_FILTER_GROUP_ATTRIBUTES  = 'ldapFilterGroupAttributes';
    public const LDAP_DATABASE_ENABLED         = 'ldapDatabaseEnabled';

    public function getAttributes(): array;

    public function getLogEvents(): array;

    public function setLogEvents(?array $logEvents): ConfigDataInterface;

    public function isDokuwikiEnabled(): bool;

    public function setDokuwikiEnabled(?bool $dokuwikiEnabled): ConfigDataInterface;

    public function getDokuwikiUrl(): ?string;

    public function setDokuwikiUrl(?string $dokuwikiUrl): ConfigDataInterface;

    public function getDokuwikiUrlBase(): ?string;

    public function setDokuwikiUrlBase(?string $dokuwikiUrlBase): ConfigDataInterface;

    public function getDokuwikiUser(): ?string;

    public function setDokuwikiUser(?string $dokuwikiUser): ConfigDataInterface;

    public function getDokuwikiPass(): ?string;

    public function setDokuwikiPass(?string $dokuwikiPass): ConfigDataInterface;

    public function getDokuwikiNamespace(): ?string;

    public function setDokuwikiNamespace(?string $dokuwikiNamespace): ConfigDataInterface;

    public function getLdapDefaultGroup(): int;

    public function setLdapDefaultGroup(?int $ldapDefaultGroup): ConfigDataInterface;

    public function getLdapDefaultProfile(): int;

    public function setLdapDefaultProfile(?int $ldapDefaultProfile): ConfigDataInterface;

    public function isProxyEnabled(): bool;

    public function setProxyEnabled(?bool $proxyEnabled): ConfigDataInterface;

    public function getProxyServer(): ?string;

    public function setProxyServer(?string $proxyServer): ConfigDataInterface;

    public function getProxyPort(): int;

    public function setProxyPort(?int $proxyPort): ConfigDataInterface;

    public function getProxyUser(): ?string;

    public function setProxyUser(?string $proxyUser): ConfigDataInterface;

    public function getProxyPass(): ?string;

    public function setProxyPass(?string $proxyPass): ConfigDataInterface;

    public function getPublinksMaxViews(): int;

    public function setPublinksMaxViews(?int $publinksMaxViews): ConfigDataInterface;

    public function getPublinksMaxTime(): int;

    public function setPublinksMaxTime(?int $publinksMaxTime): ConfigDataInterface;

    public function isSyslogEnabled(): bool;

    public function setSyslogEnabled(?bool $syslogEnabled): ConfigDataInterface;

    public function isSyslogRemoteEnabled(): bool;

    public function setSyslogRemoteEnabled(?bool $syslogRemoteEnabled): ConfigDataInterface;

    public function getSyslogServer(): ?string;

    public function setSyslogServer(?string $syslogServer): ConfigDataInterface;

    public function getSyslogPort(): int;

    public function setSyslogPort(?int $syslogPort): ConfigDataInterface;

    public function getBackupHash(): ?string;

    public function setBackupHash(?string $backup_hash): ConfigDataInterface;

    public function getExportHash(): ?string;

    public function setExportHash(?string $export_hash): ConfigDataInterface;

    public function getLdapBindUser(): ?string;

    public function setLdapBindUser(?string $ldapBindUser): ConfigDataInterface;

    public function getAccountCount(): int;

    public function setAccountCount(?int $accountCount): ConfigDataInterface;

    public function isAccountLink(): bool;

    public function setAccountLink(?bool $accountLink): ConfigDataInterface;

    public function isCheckUpdates(): bool;

    public function setCheckUpdates(?bool $checkUpdates): ConfigDataInterface;

    public function getConfigHash(): ?string;

    public function setConfigHash(): ConfigDataInterface;

    public function getDbHost(): ?string;

    public function setDbHost(?string $dbHost): ConfigDataInterface;

    public function getDbName(): ?string;

    public function setDbName(?string $dbName): ConfigDataInterface;

    public function getDbPass(): ?string;

    public function setDbPass(?string $dbPass): ConfigDataInterface;

    public function getDbUser(): ?string;

    public function setDbUser(?string $dbUser): ConfigDataInterface;

    public function isDebug(): bool;

    public function setDebug(?bool $debug): ConfigDataInterface;

    public function isDemoEnabled(): bool;

    public function setDemoEnabled(?bool $demoEnabled): ConfigDataInterface;

    public function getFilesAllowedExts(): array;

    public function getFilesAllowedSize(): int;

    public function setFilesAllowedSize(?int $filesAllowedSize): ConfigDataInterface;

    public function isFilesEnabled(): bool;

    public function setFilesEnabled(?bool $filesEnabled): ConfigDataInterface;

    public function isGlobalSearch(): bool;

    public function setGlobalSearch(?bool $globalSearch): ConfigDataInterface;

    public function isInstalled(): bool;

    public function setInstalled(?bool $installed): ConfigDataInterface;

    public function getLdapBase(): ?string;

    public function setLdapBase(?string $ldapBase): ConfigDataInterface;

    public function isLdapEnabled(): bool;

    public function setLdapEnabled(?bool $ldapEnabled): ConfigDataInterface;

    public function getLdapGroup(): ?string;

    public function setLdapGroup(?string $ldapGroup): ConfigDataInterface;

    public function getLdapServer(): ?string;

    public function setLdapServer(?string $ldapServer): ConfigDataInterface;

    public function isLogEnabled(): bool;

    public function setLogEnabled(?bool $logEnabled): ConfigDataInterface;

    public function isMailAuthenabled(): bool;

    public function setMailAuthenabled(?bool $mailAuthenabled): ConfigDataInterface;

    public function isMailEnabled(): bool;

    public function setMailEnabled(?bool $mailEnabled): ConfigDataInterface;

    public function getMailFrom(): ?string;

    public function setMailFrom(?string $mailFrom): ConfigDataInterface;

    public function getMailPass(): ?string;

    public function setMailPass(?string $mailPass): ConfigDataInterface;

    public function getMailPort(): int;

    public function setMailPort(?int $mailPort): ConfigDataInterface;

    public function isMailRequestsEnabled(): bool;

    public function setMailRequestsEnabled(?bool $mailRequestsEnabled): ConfigDataInterface;

    public function getMailSecurity(): ?string;

    public function setMailSecurity(?string $mailSecurity): ConfigDataInterface;

    public function getMailServer(): ?string;

    public function setMailServer(?string $mailServer): ConfigDataInterface;

    public function getMailUser(): ?string;

    public function setMailUser(?string $mailUser): ConfigDataInterface;

    public function isMaintenance(): bool;

    public function setMaintenance(?bool $maintenance): ConfigDataInterface;

    public function getPasswordSalt(): ?string;

    public function setPasswordSalt(?string $passwordSalt): ConfigDataInterface;

    public function isResultsAsCards(): bool;

    public function setResultsAsCards(?bool $resultsAsCards): ConfigDataInterface;

    public function getSessionTimeout(): int;

    public function setSessionTimeout(?int $sessionTimeout): ConfigDataInterface;

    public function getSiteLang(): ?string;

    public function setSiteLang(?string $siteLang): ConfigDataInterface;

    public function getSiteTheme(): string;

    public function setSiteTheme(?string $siteTheme): ConfigDataInterface;

    public function getConfigVersion(): ?string;

    public function setConfigVersion(?string $configVersion): ConfigDataInterface;

    public function isWikiEnabled(): bool;

    public function setWikiEnabled(?bool $wikiEnabled): ConfigDataInterface;

    public function getWikiFilter(): array;

    public function setWikiFilter(?array $wikiFilter): ConfigDataInterface;

    public function getWikiPageurl(): ?string;

    public function setWikiPageurl(?string $wikiPageurl): ConfigDataInterface;

    public function getWikiSearchurl(): ?string;

    public function setWikiSearchurl(?string $wikiSearchurl): ConfigDataInterface;

    public function getLdapBindPass(): ?string;

    public function setLdapBindPass(?string $ldapBindPass): ConfigDataInterface;

    public function isPublinksImageEnabled(): bool;

    public function setPublinksImageEnabled(?bool $publinksImageEnabled): ConfigDataInterface;

    public function isHttpsEnabled(): bool;

    public function setHttpsEnabled(?bool $httpsEnabled): ConfigDataInterface;

    public function isCheckNotices(): bool;

    public function setCheckNotices(?bool $checknotices): ConfigDataInterface;

    public function isAccountPassToImage(): bool;

    public function setAccountPassToImage(?bool $accountPassToImage): ConfigDataInterface;

    public function getUpgradeKey(): ?string;

    public function setUpgradeKey(?string $upgradeKey): ConfigDataInterface;

    public function getDbPort(): int;

    public function setDbPort(?int $dbPort): ConfigDataInterface;

    public function isPublinksEnabled(): bool;

    public function setPublinksEnabled(?bool $publinksEnabled): ConfigDataInterface;

    public function jsonSerialize();

    public function getConfigSaver(): ?string;

    public function setConfigSaver(?string $configSaver): ConfigDataInterface;

    public function getDbSocket(): ?string;

    public function setDbSocket(?string $dbSocket): ConfigDataInterface;

    public function isEncryptSession(): bool;

    public function setEncryptSession(?bool $encryptSession): ConfigDataInterface;

    public function isAccountFullGroupAccess(): bool;

    public function setAccountFullGroupAccess(?bool $accountFullGroupAccess): ConfigDataInterface;

    public function isAuthBasicEnabled(): bool;

    public function setAuthBasicEnabled(?bool $authBasicEnabled);

    public function getAuthBasicDomain(): ?string;

    public function setAuthBasicDomain(?string $authBasicDomain): ConfigDataInterface;

    public function isAuthBasicAutoLoginEnabled(): bool;

    public function setAuthBasicAutoLoginEnabled(?bool $authBasicAutoLoginEnabled): ConfigDataInterface;

    public function getSsoDefaultGroup(): ?int;

    public function setSsoDefaultGroup(?int $ssoDefaultGroup): ConfigDataInterface;

    public function getSsoDefaultProfile(): ?int;

    public function setSsoDefaultProfile(?int $ssoDefaultProfile): ConfigDataInterface;

    public function getMailRecipients(): ?array;

    public function setMailRecipients(?array $mailRecipients): ConfigDataInterface;

    public function getMailEvents(): ?array;

    public function setMailEvents(?array $mailEvents): ConfigDataInterface;

    public function getDatabaseVersion(): string;

    public function setDatabaseVersion(?string $databaseVersion): ConfigDataInterface;

    public function getConfigDate(): int;

    public function setConfigDate(int $configDate): ConfigDataInterface;

    public function isAccountExpireEnabled(): bool;

    public function setAccountExpireEnabled(?bool $accountExpireEnabled): ConfigDataInterface;

    public function getAccountExpireTime(): int;

    public function setAccountExpireTime(?int $accountExpireTime): ConfigDataInterface;

    public function isLdapTlsEnabled(): bool;

    public function setLdapTlsEnabled(?bool $ldapTlsEnabled): ConfigDataInterface;

    public function getFilesAllowedMime(): array;

    public function setFilesAllowedMime(?array $filesAllowedMime): ConfigDataInterface;

    public function getLdapType(): int;

    public function setLdapType(?int $ldapType): ConfigDataInterface;

    public function getAppVersion(): string;

    public function setAppVersion(?string $appVersion): ConfigDataInterface;

    public function getApplicationUrl(): ?string;

    public function setApplicationUrl(?string $applicationUrl): ConfigDataInterface;

    public function getLdapFilterUserObject(): ?string;

    public function setLdapFilterUserObject(?string $filter): ConfigDataInterface;

    public function getLdapFilterGroupObject(): ?string;

    public function setLdapFilterGroupObject(?string $filter): ConfigDataInterface;

    public function getLdapFilterUserAttributes(): array;

    public function setLdapFilterUserAttributes(?array $attributes): ConfigDataInterface;

    public function getLdapFilterGroupAttributes(): array;

    public function setLdapFilterGroupAttributes(?array $attributes): ConfigDataInterface;

    public function isLdapDatabaseEnabled(): bool;

    public function setLdapDatabaseEnabled(?bool $ldapDatabaseEnabled): ConfigDataInterface;
}
