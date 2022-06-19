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

namespace SP\Domain\Config\Adapters;

use JsonSerializable;
use SP\Core\DataCollection;
use SP\Domain\Config\In\ConfigDataInterface;

/**
 * Class configData
 */
final class ConfigData extends DataCollection implements JsonSerializable, ConfigDataInterface
{
    private const DEFAULT_PUBLIC_LINK_MAX_VIEWS = 3;
    private const DEFAULT_PUBLIC_LINK_MAX_TIME  = 600;
    private const DEFAULT_ACCOUNT_COUNT         = 12;
    private const DEFAULT_DB_PORT               = 3306;
    private const DEFAULT_FILES_ALLOWED_SIZE    = 1024;
    private const DEFAULT_MAIL_PORT             = 587;
    private const DEFAULT_SESSION_TIMEOUT       = 300;
    private const DEFAULT_SITE_THEME            = 'material-blue';
    private const DEFAULT_SYSLOG_PORT           = 514;
    private const DEFAULT_ACCOUNT_EXPIRE_TIME   = 10368000;
    private const DEFAULT_PROXY_PORT            = 8080;

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getLogEvents(): array
    {
        return $this->get(ConfigDataInterface::LOG_EVENTS, []);
    }

    public function setLogEvents(?array $logEvents): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LOG_EVENTS, $logEvents);

        return $this;
    }

    public function isDokuwikiEnabled(): bool
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_ENABLED, false);
    }

    public function setDokuwikiEnabled(?bool $dokuwikiEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_ENABLED, (bool)$dokuwikiEnabled);

        return $this;
    }

    public function getDokuwikiUrl(): ?string
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_URL);
    }

    public function setDokuwikiUrl(?string $dokuwikiUrl): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_URL, $dokuwikiUrl);

        return $this;
    }

    public function getDokuwikiUrlBase(): ?string
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_URL_BASE);
    }

    public function setDokuwikiUrlBase(?string $dokuwikiUrlBase): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_URL_BASE, $dokuwikiUrlBase);

        return $this;
    }

    public function getDokuwikiUser(): ?string
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_USER);
    }

    public function setDokuwikiUser(?string $dokuwikiUser): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_USER, $dokuwikiUser);

        return $this;
    }

    public function getDokuwikiPass(): ?string
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_PASS);
    }

    public function setDokuwikiPass(?string $dokuwikiPass): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_PASS, $dokuwikiPass);

        return $this;
    }

    public function getDokuwikiNamespace(): ?string
    {
        return $this->get(ConfigDataInterface::DOKUWIKI_NAMESPACE);
    }

    public function setDokuwikiNamespace(?string $dokuwikiNamespace): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DOKUWIKI_NAMESPACE, $dokuwikiNamespace);

        return $this;
    }

    public function getLdapDefaultGroup(): int
    {
        return (int)$this->get(ConfigDataInterface::LDAP_DEFAULT_GROUP);
    }

    public function setLdapDefaultGroup(?int $ldapDefaultGroup): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_DEFAULT_GROUP, (int)$ldapDefaultGroup);

        return $this;
    }

    public function getLdapDefaultProfile(): int
    {
        return (int)$this->get(ConfigDataInterface::LDAP_DEFAULT_PROFILE);
    }

    public function setLdapDefaultProfile(?int $ldapDefaultProfile): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_DEFAULT_PROFILE, (int)$ldapDefaultProfile);

        return $this;
    }

    public function isProxyEnabled(): bool
    {
        return $this->get(ConfigDataInterface::PROXY_ENABLED, false);
    }

    public function setProxyEnabled(?bool $proxyEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PROXY_ENABLED, (bool)$proxyEnabled);

        return $this;
    }

    public function getProxyServer(): ?string
    {
        return $this->get(ConfigDataInterface::PROXY_SERVER);
    }

    public function setProxyServer(?string $proxyServer): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PROXY_SERVER, $proxyServer);

        return $this;
    }

    public function getProxyPort(): int
    {
        return $this->get(ConfigDataInterface::PROXY_PORT, self::DEFAULT_PROXY_PORT);
    }

    public function setProxyPort(?int $proxyPort): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PROXY_PORT, (int)$proxyPort);

        return $this;
    }

    public function getProxyUser(): ?string
    {
        return $this->get(ConfigDataInterface::PROXY_USER);
    }

    public function setProxyUser(?string $proxyUser): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PROXY_USER, $proxyUser);

        return $this;
    }

    public function getProxyPass(): ?string
    {
        return $this->get(ConfigDataInterface::PROXY_PASS);
    }

    public function setProxyPass(?string $proxyPass): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PROXY_PASS, $proxyPass);

        return $this;
    }

    public function getPublinksMaxViews(): int
    {
        return $this->get(ConfigDataInterface::PUBLINKS_MAX_VIEWS, self::DEFAULT_PUBLIC_LINK_MAX_VIEWS);
    }


    public function setPublinksMaxViews(?int $publinksMaxViews): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PUBLINKS_MAX_VIEWS, (int)$publinksMaxViews);

        return $this;
    }

    public function getPublinksMaxTime(): int
    {
        return $this->get('publinksMaxTime', self::DEFAULT_PUBLIC_LINK_MAX_TIME);
    }

    public function setPublinksMaxTime(?int $publinksMaxTime): ConfigDataInterface
    {
        $this->set('publinksMaxTime', (int)$publinksMaxTime);

        return $this;
    }

    public function isSyslogEnabled(): bool
    {
        return $this->get('syslogEnabled', false);
    }

    public function setSyslogEnabled(?bool $syslogEnabled): ConfigDataInterface
    {
        $this->set('syslogEnabled', (bool)$syslogEnabled);

        return $this;
    }

    public function isSyslogRemoteEnabled(): bool
    {
        return $this->get('syslogRemoteEnabled', false);
    }

    public function setSyslogRemoteEnabled(?bool $syslogRemoteEnabled): ConfigDataInterface
    {
        $this->set('syslogRemoteEnabled', (bool)$syslogRemoteEnabled);

        return $this;
    }

    public function getSyslogServer(): ?string
    {
        return $this->get('syslogServer');
    }

    public function setSyslogServer(?string $syslogServer): ConfigDataInterface
    {
        $this->set('syslogServer', $syslogServer);

        return $this;
    }

    public function getSyslogPort(): int
    {
        return $this->get('syslogPort', self::DEFAULT_SYSLOG_PORT);
    }

    public function setSyslogPort(?int $syslogPort): ConfigDataInterface
    {
        $this->set('syslogPort', (int)$syslogPort);

        return $this;
    }

    public function getBackupHash(): ?string
    {
        return $this->get('backup_hash');
    }

    public function setBackupHash(?string $backup_hash): ConfigDataInterface
    {
        $this->set('backup_hash', $backup_hash);

        return $this;
    }

    public function getExportHash(): ?string
    {
        return $this->get('export_hash');
    }

    public function setExportHash(?string $export_hash): ConfigDataInterface
    {
        $this->set('export_hash', $export_hash);

        return $this;
    }

    public function getLdapBindUser(): ?string
    {
        return $this->get('ldapBindUser');
    }

    public function setLdapBindUser(?string $ldapBindUser): ConfigDataInterface
    {
        $this->set('ldapBindUser', $ldapBindUser);

        return $this;
    }

    public function getAccountCount(): int
    {
        return $this->get('accountCount', self::DEFAULT_ACCOUNT_COUNT);
    }

    public function setAccountCount(?int $accountCount): ConfigDataInterface
    {
        $this->set('accountCount', (int)$accountCount);

        return $this;
    }

    public function isAccountLink(): bool
    {
        return $this->get('accountLink', true);
    }

    public function setAccountLink(?bool $accountLink): ConfigDataInterface
    {
        $this->set('accountLink', (bool)$accountLink);

        return $this;
    }

    public function isCheckUpdates(): bool
    {
        return $this->get('checkUpdates', false);
    }

    public function setCheckUpdates(?bool $checkUpdates): ConfigDataInterface
    {
        $this->set('checkUpdates', (bool)$checkUpdates);

        return $this;
    }

    public function getConfigHash(): ?string
    {
        return $this->get('configHash');
    }

    /**
     * Generates a hash from current config options
     */
    public function setConfigHash(): ConfigDataInterface
    {
        $this->set('configHash', sha1(serialize($this->attributes)));

        return $this;
    }

    public function getDbHost(): ?string
    {
        return $this->get('dbHost');
    }

    public function setDbHost(?string $dbHost): ConfigDataInterface
    {
        $this->set('dbHost', $dbHost);

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->get('dbName');
    }

    public function setDbName(?string $dbName): ConfigDataInterface
    {
        $this->set('dbName', $dbName);

        return $this;
    }

    public function getDbPass(): ?string
    {
        return $this->get('dbPass');
    }

    public function setDbPass(?string $dbPass): ConfigDataInterface
    {
        $this->set('dbPass', $dbPass);

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->get('dbUser');
    }

    public function setDbUser(?string $dbUser): ConfigDataInterface
    {
        $this->set('dbUser', $dbUser);

        return $this;
    }

    public function isDebug(): bool
    {
        return $this->get('debug', false);
    }

    public function setDebug(?bool $debug): ConfigDataInterface
    {
        $this->set('debug', (bool)$debug);

        return $this;
    }

    public function isDemoEnabled(): bool
    {
        return $this->get('demoEnabled', false);
    }

    public function setDemoEnabled(?bool $demoEnabled): ConfigDataInterface
    {
        $this->set('demoEnabled', (bool)$demoEnabled);

        return $this;
    }

    public function getFilesAllowedExts(): array
    {
        return $this->get('filesAllowedExts', []);
    }

    public function getFilesAllowedSize(): int
    {
        return $this->get('filesAllowedSize', self::DEFAULT_FILES_ALLOWED_SIZE);
    }

    public function setFilesAllowedSize(?int $filesAllowedSize): ConfigDataInterface
    {
        $this->set('filesAllowedSize', (int)$filesAllowedSize);

        return $this;
    }

    public function isFilesEnabled(): bool
    {
        return $this->get('filesEnabled', true);
    }

    public function setFilesEnabled(?bool $filesEnabled): ConfigDataInterface
    {
        $this->set('filesEnabled', (bool)$filesEnabled);

        return $this;
    }

    public function isGlobalSearch(): bool
    {
        return $this->get('globalSearch', true);
    }

    public function setGlobalSearch(?bool $globalSearch): ConfigDataInterface
    {
        $this->set('globalSearch', (bool)$globalSearch);

        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->get('installed', false);
    }

    public function setInstalled(?bool $installed): ConfigDataInterface
    {
        $this->set('installed', (bool)$installed);

        return $this;
    }

    public function getLdapBase(): ?string
    {
        return $this->get('ldapBase');
    }

    public function setLdapBase(?string $ldapBase): ConfigDataInterface
    {
        $this->set('ldapBase', $ldapBase);

        return $this;
    }

    public function isLdapEnabled(): bool
    {
        return $this->get('ldapEnabled', false);
    }

    public function setLdapEnabled(?bool $ldapEnabled): ConfigDataInterface
    {
        $this->set('ldapEnabled', (bool)$ldapEnabled);

        return $this;
    }

    public function getLdapGroup(): ?string
    {
        return $this->get('ldapGroup');
    }

    public function setLdapGroup(?string $ldapGroup): ConfigDataInterface
    {
        $this->set('ldapGroup', $ldapGroup);

        return $this;
    }

    public function getLdapServer(): ?string
    {
        return $this->get('ldapServer');
    }

    public function setLdapServer(?string $ldapServer): ConfigDataInterface
    {
        $this->set('ldapServer', $ldapServer);

        return $this;
    }

    public function isLogEnabled(): bool
    {
        return $this->get('logEnabled', true);
    }

    public function setLogEnabled(?bool $logEnabled): ConfigDataInterface
    {
        $this->set('logEnabled', (bool)$logEnabled);

        return $this;
    }

    public function isMailAuthenabled(): bool
    {
        return $this->get('mailAuthenabled', false);
    }

    public function setMailAuthenabled(?bool $mailAuthenabled): ConfigDataInterface
    {
        $this->set('mailAuthenabled', (bool)$mailAuthenabled);

        return $this;
    }

    public function isMailEnabled(): bool
    {
        return $this->get('mailEnabled', false);
    }

    public function setMailEnabled(?bool $mailEnabled): ConfigDataInterface
    {
        $this->set('mailEnabled', (bool)$mailEnabled);

        return $this;
    }

    public function getMailFrom(): ?string
    {
        return $this->get('mailFrom');
    }

    public function setMailFrom(?string $mailFrom): ConfigDataInterface
    {
        $this->set('mailFrom', $mailFrom);

        return $this;
    }

    public function getMailPass(): ?string
    {
        return $this->get('mailPass');
    }

    public function setMailPass(?string $mailPass): ConfigDataInterface
    {
        $this->set('mailPass', $mailPass);

        return $this;
    }

    public function getMailPort(): int
    {
        return $this->get('mailPort', self::DEFAULT_MAIL_PORT);
    }

    public function setMailPort(?int $mailPort): ConfigDataInterface
    {
        $this->set('mailPort', (int)$mailPort);

        return $this;
    }

    public function isMailRequestsEnabled(): bool
    {
        return $this->get('mailRequestsEnabled', false);
    }

    public function setMailRequestsEnabled(?bool $mailRequestsEnabled): ConfigDataInterface
    {
        $this->set('mailRequestsEnabled', (bool)$mailRequestsEnabled);

        return $this;
    }

    public function getMailSecurity(): ?string
    {
        return $this->get('mailSecurity');
    }

    public function setMailSecurity(?string $mailSecurity): ConfigDataInterface
    {
        $this->set('mailSecurity', $mailSecurity);

        return $this;
    }

    public function getMailServer(): ?string
    {
        return $this->get('mailServer');
    }

    public function setMailServer(?string $mailServer): ConfigDataInterface
    {
        $this->set('mailServer', $mailServer);

        return $this;
    }

    public function getMailUser(): ?string
    {
        return $this->get('mailUser');
    }

    public function setMailUser(?string $mailUser): ConfigDataInterface
    {
        $this->set('mailUser', $mailUser);

        return $this;
    }

    public function isMaintenance(): bool
    {
        return $this->get('maintenance', false);
    }

    public function setMaintenance(?bool $maintenance): ConfigDataInterface
    {
        $this->set('maintenance', (bool)$maintenance);

        return $this;
    }

    public function getPasswordSalt(): ?string
    {
        return $this->get('passwordSalt');
    }

    public function setPasswordSalt(?string $passwordSalt): ConfigDataInterface
    {
        $this->set('passwordSalt', $passwordSalt);

        return $this;
    }

    public function isResultsAsCards(): bool
    {
        return $this->get('resultsAsCards', false);
    }

    public function setResultsAsCards(?bool $resultsAsCards): ConfigDataInterface
    {
        $this->set('resultsAsCards', (bool)$resultsAsCards);

        return $this;
    }

    public function getSessionTimeout(): int
    {
        return $this->get('sessionTimeout', self::DEFAULT_SESSION_TIMEOUT);
    }

    public function setSessionTimeout(?int $sessionTimeout): ConfigDataInterface
    {
        $this->set('sessionTimeout', (int)$sessionTimeout);

        return $this;
    }

    public function getSiteLang(): ?string
    {
        return $this->get('siteLang');
    }

    public function setSiteLang(?string $siteLang): ConfigDataInterface
    {
        $this->set('siteLang', $siteLang);

        return $this;
    }

    public function getSiteTheme(): string
    {
        return $this->get('siteTheme', self::DEFAULT_SITE_THEME);
    }

    public function setSiteTheme(?string $siteTheme): ConfigDataInterface
    {
        $this->set('siteTheme', $siteTheme);

        return $this;
    }

    public function getConfigVersion(): ?string
    {
        return (string)$this->get('configVersion');
    }

    public function setConfigVersion(?string $configVersion): ConfigDataInterface
    {
        $this->set('configVersion', $configVersion);

        return $this;
    }

    public function isWikiEnabled(): bool
    {
        return $this->get('wikiEnabled', false);
    }

    public function setWikiEnabled(?bool $wikiEnabled): ConfigDataInterface
    {
        $this->set('wikiEnabled', (bool)$wikiEnabled);

        return $this;
    }

    public function getWikiFilter(): array
    {
        return $this->get('wikiFilter', []);
    }

    public function setWikiFilter(?array $wikiFilter): ConfigDataInterface
    {
        $this->set('wikiFilter', $wikiFilter);

        return $this;
    }

    public function getWikiPageurl(): ?string
    {
        return $this->get('wikiPageurl');
    }

    public function setWikiPageurl(?string $wikiPageurl): ConfigDataInterface
    {
        $this->set('wikiPageurl', $wikiPageurl);

        return $this;
    }

    public function getWikiSearchurl(): ?string
    {
        return $this->get('wikiSearchurl');
    }

    public function setWikiSearchurl(?string $wikiSearchurl): ConfigDataInterface
    {
        $this->set('wikiSearchurl', $wikiSearchurl);

        return $this;
    }

    public function getLdapBindPass(): ?string
    {
        return $this->get('ldapBindPass');
    }

    public function setLdapBindPass(?string $ldapBindPass): ConfigDataInterface
    {
        $this->set('ldapBindPass', $ldapBindPass);

        return $this;
    }

    public function isPublinksImageEnabled(): bool
    {
        return $this->get('publinksImageEnabled', false);
    }

    public function setPublinksImageEnabled(?bool $publinksImageEnabled): ConfigDataInterface
    {
        $this->set('publinksImageEnabled', (bool)$publinksImageEnabled);

        return $this;
    }

    public function isHttpsEnabled(): bool
    {
        return $this->get('httpsEnabled', false);
    }

    public function setHttpsEnabled(?bool $httpsEnabled): ConfigDataInterface
    {
        $this->set('httpsEnabled', (bool)$httpsEnabled);

        return $this;
    }

    public function isCheckNotices(): bool
    {
        return $this->get('checkNotices', false);
    }

    public function setCheckNotices(?bool $checknotices): ConfigDataInterface
    {
        $this->set('checkNotices', (bool)$checknotices);

        return $this;
    }

    public function isAccountPassToImage(): bool
    {
        return $this->get('accountPassToImage', false);
    }

    public function setAccountPassToImage(?bool $accountPassToImage): ConfigDataInterface
    {
        $this->set('accountPassToImage', (bool)$accountPassToImage);

        return $this;
    }

    public function getUpgradeKey(): ?string
    {
        return $this->get('upgradeKey');
    }

    public function setUpgradeKey(?string $upgradeKey): ConfigDataInterface
    {
        $this->set('upgradeKey', $upgradeKey);

        return $this;
    }

    public function getDbPort(): int
    {
        return $this->get('dbPort', self::DEFAULT_DB_PORT);
    }

    public function setDbPort(?int $dbPort): ConfigDataInterface
    {
        $this->set('dbPort', (int)$dbPort);

        return $this;
    }

    public function isPublinksEnabled(): bool
    {
        return $this->get('publinksEnabled', false);
    }

    public function setPublinksEnabled(?bool $publinksEnabled): ConfigDataInterface
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
    public function jsonSerialize(): array
    {
        return $this->attributes;
    }

    public function getConfigSaver(): ?string
    {
        return $this->get('configSaver');
    }

    public function setConfigSaver(?string $configSaver): ConfigDataInterface
    {
        $this->set('configSaver', $configSaver);

        return $this;
    }

    public function getDbSocket(): ?string
    {
        return $this->get('dbSocket');
    }

    public function setDbSocket(?string $dbSocket): ConfigDataInterface
    {
        $this->set('dbSocket', $dbSocket);

        return $this;
    }

    public function isEncryptSession(): bool
    {
        return (bool)$this->get('encryptSession', false);
    }

    public function setEncryptSession(?bool $encryptSession): ConfigDataInterface
    {
        $this->set('encryptSession', (bool)$encryptSession);

        return $this;
    }

    public function isAccountFullGroupAccess(): bool
    {
        return (bool)$this->get('accountFullGroupAccess', false);
    }

    public function setAccountFullGroupAccess(?bool $accountFullGroupAccess): ConfigDataInterface
    {
        $this->set('accountFullGroupAccess', (bool)$accountFullGroupAccess);

        return $this;
    }

    public function isAuthBasicEnabled(): bool
    {
        return (bool)$this->get('authBasicEnabled', true);
    }

    public function setAuthBasicEnabled(?bool $authBasicEnabled): ConfigDataInterface
    {
        $this->set('authBasicEnabled', (bool)$authBasicEnabled);

        return $this;
    }

    public function getAuthBasicDomain(): ?string
    {
        return $this->get('authBasicDomain');
    }

    public function setAuthBasicDomain(?string $authBasicDomain): ConfigDataInterface
    {
        $this->set('authBasicDomain', $authBasicDomain);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled(): bool
    {
        return (bool)$this->get('authBasicAutoLoginEnabled', true);
    }

    public function setAuthBasicAutoLoginEnabled(?bool $authBasicAutoLoginEnabled): ConfigDataInterface
    {
        $this->set('authBasicAutoLoginEnabled', $authBasicAutoLoginEnabled);

        return $this;
    }

    public function getSsoDefaultGroup(): ?int
    {
        return $this->get('ssoDefaultGroup');
    }

    public function setSsoDefaultGroup(?int $ssoDefaultGroup): ConfigDataInterface
    {
        $this->set('ssoDefaultGroup', $ssoDefaultGroup);

        return $this;
    }

    public function getSsoDefaultProfile(): ?int
    {
        return $this->get('ssoDefaultProfile');
    }

    public function setSsoDefaultProfile(?int $ssoDefaultProfile): ConfigDataInterface
    {
        $this->set('ssoDefaultProfile', $ssoDefaultProfile);

        return $this;
    }

    public function getMailRecipients(): array
    {
        return $this->get('mailRecipients', []);
    }

    public function setMailRecipients(?array $mailRecipients): ConfigDataInterface
    {
        $this->set('mailRecipients', $mailRecipients);

        return $this;
    }

    public function getMailEvents(): array
    {
        return $this->get('mailEvents', []);
    }

    public function setMailEvents(?array $mailEvents): ConfigDataInterface
    {
        $this->set('mailEvents', $mailEvents);

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return (string)$this->get('databaseVersion');
    }

    public function setDatabaseVersion(?string $databaseVersion): ConfigDataInterface
    {
        $this->set('databaseVersion', $databaseVersion);

        return $this;
    }

    public function getConfigDate(): int
    {
        return (int)$this->get('configDate');
    }

    public function setConfigDate(int $configDate): ConfigDataInterface
    {
        $this->set('configDate', $configDate);

        return $this;
    }

    public function isAccountExpireEnabled(): bool
    {
        return (bool)$this->get('accountExpireEnabled', false);
    }

    public function setAccountExpireEnabled(?bool $accountExpireEnabled): ConfigDataInterface
    {
        $this->set('accountExpireEnabled', (bool)$accountExpireEnabled);

        return $this;
    }

    public function getAccountExpireTime(): int
    {
        return $this->get('accountExpireTime', self::DEFAULT_ACCOUNT_EXPIRE_TIME);
    }

    public function setAccountExpireTime(?int $accountExpireTime): ConfigDataInterface
    {
        $this->set('accountExpireTime', (int)$accountExpireTime);

        return $this;
    }

    public function isLdapTlsEnabled(): bool
    {
        return $this->get('ldapTlsEnabled', false);
    }

    public function setLdapTlsEnabled(?bool $ldapTlsEnabled): ConfigDataInterface
    {
        $this->set('ldapTlsEnabled', (int)$ldapTlsEnabled);

        return $this;
    }

    public function getFilesAllowedMime(): array
    {
        return $this->get('filesAllowedMime', []);
    }

    public function setFilesAllowedMime(?array $filesAllowedMime): ConfigDataInterface
    {
        $this->set('filesAllowedMime', $filesAllowedMime);

        return $this;
    }

    public function getLdapType(): int
    {
        return (int)$this->get('ldapType');
    }

    public function setLdapType(?int $ldapType): ConfigDataInterface
    {
        $this->set('ldapType', (int)$ldapType);

        return $this;
    }

    public function getAppVersion(): string
    {
        return $this->get('appVersion');
    }

    public function setAppVersion(?string $appVersion): ConfigDataInterface
    {
        $this->set('appVersion', $appVersion);

        return $this;
    }

    public function getApplicationUrl(): ?string
    {
        return $this->get('applicationUrl');
    }

    public function setApplicationUrl(?string $applicationUrl): ConfigDataInterface
    {
        $this->set(
            'applicationUrl',
            $applicationUrl
                ? rtrim($applicationUrl, '/')
                : null
        );

        return $this;
    }

    public function getLdapFilterUserObject(): ?string
    {
        return $this->get('ldapFilterUserObject');
    }

    public function setLdapFilterUserObject(?string $filter): ConfigDataInterface
    {
        $this->set('ldapFilterUserObject', $filter);

        return $this;
    }

    public function getLdapFilterGroupObject(): ?string
    {
        return $this->get('ldapFilterGroupObject');
    }

    public function setLdapFilterGroupObject(?string $filter): ConfigDataInterface
    {
        $this->set('ldapFilterGroupObject', $filter);

        return $this;
    }

    public function getLdapFilterUserAttributes(): array
    {
        return $this->get('ldapFilterUserAttributes', []);
    }

    public function setLdapFilterUserAttributes(?array $attributes): ConfigDataInterface
    {
        $this->set('ldapFilterUserAttributes', $attributes);

        return $this;
    }

    public function getLdapFilterGroupAttributes(): array
    {
        return $this->get('ldapFilterGroupAttributes', []);
    }

    public function setLdapFilterGroupAttributes(?array $attributes): ConfigDataInterface
    {
        $this->set('ldapFilterGroupAttributes', $attributes);

        return $this;
    }

    public function isLdapDatabaseEnabled(): bool
    {
        return $this->get('ldapDatabaseEnabled', true);
    }

    public function setLdapDatabaseEnabled(?bool $ldapDatabaseEnabled): ConfigDataInterface
    {
        $this->set('ldapDatabaseEnabled', (int)$ldapDatabaseEnabled);

        return $this;
    }
}
