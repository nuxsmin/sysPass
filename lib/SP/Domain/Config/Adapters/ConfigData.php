<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\DataCollection;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Util\Serde;
use SP\Util\Version;

/**
 * Class ConfigData
 */
final class ConfigData extends DataCollection implements ConfigDataInterface
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
        return $this->getArrayCopy();
    }

    public function countAttributes(): int
    {
        return $this->count();
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
        return $this->get(ConfigDataInterface::PUBLINKS_MAX_TIME, self::DEFAULT_PUBLIC_LINK_MAX_TIME);
    }

    public function setPublinksMaxTime(?int $publinksMaxTime): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PUBLINKS_MAX_TIME, (int)$publinksMaxTime);

        return $this;
    }

    public function isSyslogEnabled(): bool
    {
        return $this->get(ConfigDataInterface::SYSLOG_ENABLED, false);
    }

    public function setSyslogEnabled(?bool $syslogEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SYSLOG_ENABLED, (bool)$syslogEnabled);

        return $this;
    }

    public function isSyslogRemoteEnabled(): bool
    {
        return $this->get(ConfigDataInterface::SYSLOG_REMOTE_ENABLED, false);
    }

    public function setSyslogRemoteEnabled(?bool $syslogRemoteEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SYSLOG_REMOTE_ENABLED, (bool)$syslogRemoteEnabled);

        return $this;
    }

    public function getSyslogServer(): ?string
    {
        return $this->get(ConfigDataInterface::SYSLOG_SERVER);
    }

    public function setSyslogServer(?string $syslogServer): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SYSLOG_SERVER, $syslogServer);

        return $this;
    }

    public function getSyslogPort(): int
    {
        return $this->get(ConfigDataInterface::SYSLOG_PORT, self::DEFAULT_SYSLOG_PORT);
    }

    public function setSyslogPort(?int $syslogPort): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SYSLOG_PORT, (int)$syslogPort);

        return $this;
    }

    public function getBackupHash(): ?string
    {
        return $this->get(ConfigDataInterface::BACKUP_HASH);
    }

    public function setBackupHash(?string $backup_hash): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::BACKUP_HASH, $backup_hash);

        return $this;
    }

    public function getExportHash(): ?string
    {
        return $this->get(ConfigDataInterface::EXPORT_HASH);
    }

    public function setExportHash(?string $export_hash): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::EXPORT_HASH, $export_hash);

        return $this;
    }

    public function getLdapBindUser(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_BIND_USER);
    }

    public function setLdapBindUser(?string $ldapBindUser): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_BIND_USER, $ldapBindUser);

        return $this;
    }

    public function getAccountCount(): int
    {
        return $this->get(ConfigDataInterface::ACCOUNT_COUNT, self::DEFAULT_ACCOUNT_COUNT);
    }

    public function setAccountCount(?int $accountCount): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_COUNT, (int)$accountCount);

        return $this;
    }

    public function isAccountLink(): bool
    {
        return $this->get(ConfigDataInterface::ACCOUNT_LINK, true);
    }

    public function setAccountLink(?bool $accountLink): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_LINK, (bool)$accountLink);

        return $this;
    }

    public function isCheckUpdates(): bool
    {
        return $this->get(ConfigDataInterface::CHECK_UPDATES, false);
    }

    public function setCheckUpdates(?bool $checkUpdates): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CHECK_UPDATES, (bool)$checkUpdates);

        return $this;
    }

    public function getConfigHash(): ?string
    {
        return $this->get(ConfigDataInterface::CONFIG_HASH);
    }

    /**
     * Generates a hash from current config options
     */
    public function setConfigHash(): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CONFIG_HASH, sha1(Serde::serialize($this->getArrayCopy())));

        return $this;
    }

    public function getDbHost(): ?string
    {
        return $this->get(ConfigDataInterface::DB_HOST);
    }

    public function setDbHost(?string $dbHost): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_HOST, $dbHost);

        return $this;
    }

    public function getDbName(): ?string
    {
        return $this->get(ConfigDataInterface::DB_NAME);
    }

    public function setDbName(?string $dbName): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_NAME, $dbName);

        return $this;
    }

    public function getDbPass(): ?string
    {
        return $this->get(ConfigDataInterface::DB_PASS);
    }

    public function setDbPass(?string $dbPass): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_PASS, $dbPass);

        return $this;
    }

    public function getDbUser(): ?string
    {
        return $this->get(ConfigDataInterface::DB_USER);
    }

    public function setDbUser(?string $dbUser): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_USER, $dbUser);

        return $this;
    }

    public function isDebug(): bool
    {
        return $this->get(ConfigDataInterface::DEBUG, false);
    }

    public function setDebug(?bool $debug): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DEBUG, (bool)$debug);

        return $this;
    }

    public function isDemoEnabled(): bool
    {
        return $this->get(ConfigDataInterface::DEMO_ENABLED, false);
    }

    public function setDemoEnabled(?bool $demoEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DEMO_ENABLED, (bool)$demoEnabled);

        return $this;
    }

    public function getFilesAllowedExts(): array
    {
        return $this->get(ConfigDataInterface::FILES_ALLOWED_EXTS, []);
    }

    public function getFilesAllowedSize(): int
    {
        return $this->get(ConfigDataInterface::FILES_ALLOWED_SIZE, self::DEFAULT_FILES_ALLOWED_SIZE);
    }

    public function setFilesAllowedSize(?int $filesAllowedSize): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::FILES_ALLOWED_SIZE, (int)$filesAllowedSize);

        return $this;
    }

    public function isFilesEnabled(): bool
    {
        return $this->get(ConfigDataInterface::FILES_ENABLED, true);
    }

    public function setFilesEnabled(?bool $filesEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::FILES_ENABLED, (bool)$filesEnabled);

        return $this;
    }

    public function isGlobalSearch(): bool
    {
        return $this->get(ConfigDataInterface::GLOBAL_SEARCH, true);
    }

    public function setGlobalSearch(?bool $globalSearch): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::GLOBAL_SEARCH, (bool)$globalSearch);

        return $this;
    }

    public function isInstalled(): bool
    {
        return $this->get(ConfigDataInterface::INSTALLED, false);
    }

    public function setInstalled(?bool $installed): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::INSTALLED, (bool)$installed);

        return $this;
    }

    public function getLdapBase(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_BASE);
    }

    public function setLdapBase(?string $ldapBase): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_BASE, $ldapBase);

        return $this;
    }

    public function isLdapEnabled(): bool
    {
        return $this->get(ConfigDataInterface::LDAP_ENABLED, false);
    }

    public function setLdapEnabled(?bool $ldapEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_ENABLED, (bool)$ldapEnabled);

        return $this;
    }

    public function getLdapGroup(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_GROUP);
    }

    public function setLdapGroup(?string $ldapGroup): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_GROUP, $ldapGroup);

        return $this;
    }

    public function getLdapServer(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_SERVER);
    }

    public function setLdapServer(?string $ldapServer): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_SERVER, $ldapServer);

        return $this;
    }

    public function isLogEnabled(): bool
    {
        return $this->get(ConfigDataInterface::LOG_ENABLED, true);
    }

    public function setLogEnabled(?bool $logEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LOG_ENABLED, (bool)$logEnabled);

        return $this;
    }

    public function isMailAuthenabled(): bool
    {
        return $this->get(ConfigDataInterface::MAIL_AUTHENABLED, false);
    }

    public function setMailAuthenabled(?bool $mailAuthenabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_AUTHENABLED, (bool)$mailAuthenabled);

        return $this;
    }

    public function isMailEnabled(): bool
    {
        return $this->get(ConfigDataInterface::MAIL_ENABLED, false);
    }

    public function setMailEnabled(?bool $mailEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_ENABLED, (bool)$mailEnabled);

        return $this;
    }

    public function getMailFrom(): ?string
    {
        return $this->get(ConfigDataInterface::MAIL_FROM);
    }

    public function setMailFrom(?string $mailFrom): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_FROM, $mailFrom);

        return $this;
    }

    public function getMailPass(): ?string
    {
        return $this->get(ConfigDataInterface::MAIL_PASS);
    }

    public function setMailPass(?string $mailPass): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_PASS, $mailPass);

        return $this;
    }

    public function getMailPort(): int
    {
        return $this->get(ConfigDataInterface::MAIL_PORT, self::DEFAULT_MAIL_PORT);
    }

    public function setMailPort(?int $mailPort): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_PORT, (int)$mailPort);

        return $this;
    }

    public function isMailRequestsEnabled(): bool
    {
        return $this->get(ConfigDataInterface::MAIL_REQUESTS_ENABLED, false);
    }

    public function setMailRequestsEnabled(?bool $mailRequestsEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_REQUESTS_ENABLED, (bool)$mailRequestsEnabled);

        return $this;
    }

    public function getMailSecurity(): ?string
    {
        return $this->get(ConfigDataInterface::MAIL_SECURITY);
    }

    public function setMailSecurity(?string $mailSecurity): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_SECURITY, $mailSecurity);

        return $this;
    }

    public function getMailServer(): ?string
    {
        return $this->get(ConfigDataInterface::MAIL_SERVER);
    }

    public function setMailServer(?string $mailServer): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_SERVER, $mailServer);

        return $this;
    }

    public function getMailUser(): ?string
    {
        return $this->get(ConfigDataInterface::MAIL_USER);
    }

    public function setMailUser(?string $mailUser): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_USER, $mailUser);

        return $this;
    }

    public function isMaintenance(): bool
    {
        return $this->get(ConfigDataInterface::MAINTENANCE, false);
    }

    public function setMaintenance(?bool $maintenance): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAINTENANCE, (bool)$maintenance);

        return $this;
    }

    public function getPasswordSalt(): ?string
    {
        return $this->get(ConfigDataInterface::PASSWORD_SALT);
    }

    public function setPasswordSalt(?string $passwordSalt): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PASSWORD_SALT, $passwordSalt);

        return $this;
    }

    public function isResultsAsCards(): bool
    {
        return $this->get(ConfigDataInterface::RESULTS_AS_CARDS, false);
    }

    public function setResultsAsCards(?bool $resultsAsCards): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::RESULTS_AS_CARDS, (bool)$resultsAsCards);

        return $this;
    }

    public function getSessionTimeout(): int
    {
        return $this->get(ConfigDataInterface::SESSION_TIMEOUT, self::DEFAULT_SESSION_TIMEOUT);
    }

    public function setSessionTimeout(?int $sessionTimeout): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SESSION_TIMEOUT, (int)$sessionTimeout);

        return $this;
    }

    public function getSiteLang(): ?string
    {
        return $this->get(ConfigDataInterface::SITE_LANG);
    }

    public function setSiteLang(?string $siteLang): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SITE_LANG, $siteLang);

        return $this;
    }

    public function getSiteTheme(): string
    {
        return $this->get(ConfigDataInterface::SITE_THEME, self::DEFAULT_SITE_THEME);
    }

    public function setSiteTheme(?string $siteTheme): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SITE_THEME, $siteTheme);

        return $this;
    }

    public function getConfigVersion(): ?string
    {
        return (string)$this->get(ConfigDataInterface::CONFIG_VERSION);
    }

    public function setConfigVersion(?string $configVersion): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CONFIG_VERSION, $configVersion);

        return $this;
    }

    public function isWikiEnabled(): bool
    {
        return $this->get(ConfigDataInterface::WIKI_ENABLED, false);
    }

    public function setWikiEnabled(?bool $wikiEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::WIKI_ENABLED, (bool)$wikiEnabled);

        return $this;
    }

    public function getWikiFilter(): array
    {
        return $this->get(ConfigDataInterface::WIKI_FILTER, []);
    }

    public function setWikiFilter(?array $wikiFilter): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::WIKI_FILTER, $wikiFilter);

        return $this;
    }

    public function getWikiPageurl(): ?string
    {
        return $this->get(ConfigDataInterface::WIKI_PAGEURL);
    }

    public function setWikiPageurl(?string $wikiPageurl): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::WIKI_PAGEURL, $wikiPageurl);

        return $this;
    }

    public function getWikiSearchurl(): ?string
    {
        return $this->get(ConfigDataInterface::WIKI_SEARCHURL);
    }

    public function setWikiSearchurl(?string $wikiSearchurl): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::WIKI_SEARCHURL, $wikiSearchurl);

        return $this;
    }

    public function getLdapBindPass(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_BIND_PASS);
    }

    public function setLdapBindPass(?string $ldapBindPass): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_BIND_PASS, $ldapBindPass);

        return $this;
    }

    public function isPublinksImageEnabled(): bool
    {
        return $this->get(ConfigDataInterface::PUBLINKS_IMAGE_ENABLED, false);
    }

    public function setPublinksImageEnabled(?bool $publinksImageEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PUBLINKS_IMAGE_ENABLED, (bool)$publinksImageEnabled);

        return $this;
    }

    public function isHttpsEnabled(): bool
    {
        return $this->get(ConfigDataInterface::HTTPS_ENABLED, false);
    }

    public function setHttpsEnabled(?bool $httpsEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::HTTPS_ENABLED, (bool)$httpsEnabled);

        return $this;
    }

    public function isCheckNotices(): bool
    {
        return $this->get(ConfigDataInterface::CHECK_NOTICES, false);
    }

    public function setCheckNotices(?bool $checknotices): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CHECK_NOTICES, (bool)$checknotices);

        return $this;
    }

    public function isAccountPassToImage(): bool
    {
        return $this->get(ConfigDataInterface::ACCOUNT_PASS_TO_IMAGE, false);
    }

    public function setAccountPassToImage(?bool $accountPassToImage): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_PASS_TO_IMAGE, (bool)$accountPassToImage);

        return $this;
    }

    public function getUpgradeKey(): ?string
    {
        return $this->get(ConfigDataInterface::UPGRADE_KEY);
    }

    public function setUpgradeKey(?string $upgradeKey): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::UPGRADE_KEY, $upgradeKey);

        return $this;
    }

    public function getDbPort(): int
    {
        return $this->get(ConfigDataInterface::DB_PORT, self::DEFAULT_DB_PORT);
    }

    public function setDbPort(?int $dbPort): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_PORT, (int)$dbPort);

        return $this;
    }

    public function isPublinksEnabled(): bool
    {
        return $this->get(ConfigDataInterface::PUBLINKS_ENABLED, false);
    }

    public function setPublinksEnabled(?bool $publinksEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::PUBLINKS_ENABLED, (bool)$publinksEnabled);

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
        return $this->getArrayCopy();
    }

    public function getConfigSaver(): ?string
    {
        return $this->get(ConfigDataInterface::CONFIG_SAVER);
    }

    public function setConfigSaver(?string $configSaver): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CONFIG_SAVER, $configSaver);

        return $this;
    }

    public function getDbSocket(): ?string
    {
        return $this->get(ConfigDataInterface::DB_SOCKET);
    }

    public function setDbSocket(?string $dbSocket): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DB_SOCKET, $dbSocket);

        return $this;
    }

    public function isEncryptSession(): bool
    {
        return (bool)$this->get(ConfigDataInterface::ENCRYPT_SESSION, false);
    }

    public function setEncryptSession(?bool $encryptSession): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ENCRYPT_SESSION, (bool)$encryptSession);

        return $this;
    }

    public function isAccountFullGroupAccess(): bool
    {
        return (bool)$this->get(ConfigDataInterface::ACCOUNT_FULL_GROUP_ACCESS, false);
    }

    public function setAccountFullGroupAccess(?bool $accountFullGroupAccess): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_FULL_GROUP_ACCESS, (bool)$accountFullGroupAccess);

        return $this;
    }

    public function isAuthBasicEnabled(): bool
    {
        return (bool)$this->get(ConfigDataInterface::AUTH_BASIC_ENABLED, true);
    }

    public function setAuthBasicEnabled(?bool $authBasicEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::AUTH_BASIC_ENABLED, (bool)$authBasicEnabled);

        return $this;
    }

    public function getAuthBasicDomain(): ?string
    {
        return $this->get(ConfigDataInterface::AUTH_BASIC_DOMAIN);
    }

    public function setAuthBasicDomain(?string $authBasicDomain): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::AUTH_BASIC_DOMAIN, $authBasicDomain);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthBasicAutoLoginEnabled(): bool
    {
        return (bool)$this->get(ConfigDataInterface::AUTH_BASIC_AUTO_LOGIN_ENABLED, true);
    }

    public function setAuthBasicAutoLoginEnabled(?bool $authBasicAutoLoginEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::AUTH_BASIC_AUTO_LOGIN_ENABLED, $authBasicAutoLoginEnabled);

        return $this;
    }

    public function getSsoDefaultGroup(): ?int
    {
        return $this->get(ConfigDataInterface::SSO_DEFAULT_GROUP);
    }

    public function setSsoDefaultGroup(?int $ssoDefaultGroup): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SSO_DEFAULT_GROUP, $ssoDefaultGroup);

        return $this;
    }

    public function getSsoDefaultProfile(): ?int
    {
        return $this->get(ConfigDataInterface::SSO_DEFAULT_PROFILE);
    }

    public function setSsoDefaultProfile(?int $ssoDefaultProfile): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::SSO_DEFAULT_PROFILE, $ssoDefaultProfile);

        return $this;
    }

    public function getMailRecipients(): array
    {
        return $this->get(ConfigDataInterface::MAIL_RECIPIENTS, []);
    }

    public function setMailRecipients(?array $mailRecipients): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_RECIPIENTS, $mailRecipients);

        return $this;
    }

    public function getMailEvents(): array
    {
        return $this->get(ConfigDataInterface::MAIL_EVENTS, []);
    }

    public function setMailEvents(?array $mailEvents): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::MAIL_EVENTS, $mailEvents);

        return $this;
    }

    /**
     * @return string
     */
    public function getDatabaseVersion(): string
    {
        return (string)$this->get(ConfigDataInterface::DATABASE_VERSION);
    }

    public function setDatabaseVersion(?string $databaseVersion): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::DATABASE_VERSION, $databaseVersion);

        return $this;
    }

    public function getConfigDate(): int
    {
        return (int)$this->get(ConfigDataInterface::CONFIG_DATE);
    }

    public function setConfigDate(int $configDate): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::CONFIG_DATE, $configDate);

        return $this;
    }

    public function isAccountExpireEnabled(): bool
    {
        return (bool)$this->get(ConfigDataInterface::ACCOUNT_EXPIRE_ENABLED, false);
    }

    public function setAccountExpireEnabled(?bool $accountExpireEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_EXPIRE_ENABLED, (bool)$accountExpireEnabled);

        return $this;
    }

    public function getAccountExpireTime(): int
    {
        return $this->get(ConfigDataInterface::ACCOUNT_EXPIRE_TIME, self::DEFAULT_ACCOUNT_EXPIRE_TIME);
    }

    public function setAccountExpireTime(?int $accountExpireTime): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::ACCOUNT_EXPIRE_TIME, (int)$accountExpireTime);

        return $this;
    }

    public function isLdapTlsEnabled(): bool
    {
        return $this->get(ConfigDataInterface::LDAP_TLS_ENABLED, false);
    }

    public function setLdapTlsEnabled(?bool $ldapTlsEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_TLS_ENABLED, (int)$ldapTlsEnabled);

        return $this;
    }

    public function getFilesAllowedMime(): array
    {
        return $this->get(ConfigDataInterface::FILES_ALLOWED_MIME, []);
    }

    public function setFilesAllowedMime(?array $filesAllowedMime): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::FILES_ALLOWED_MIME, $filesAllowedMime);

        return $this;
    }

    public function getLdapType(): int
    {
        return (int)$this->get(ConfigDataInterface::LDAP_TYPE);
    }

    public function setLdapType(?int $ldapType): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_TYPE, (int)$ldapType);

        return $this;
    }

    public function getAppVersion(): string
    {
        return $this->get(ConfigDataInterface::APP_VERSION, Version::getVersionStringNormalized());
    }

    public function setAppVersion(?string $appVersion): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::APP_VERSION, $appVersion);

        return $this;
    }

    public function getApplicationUrl(): ?string
    {
        return $this->get(ConfigDataInterface::APPLICATION_URL);
    }

    public function setApplicationUrl(?string $applicationUrl): ConfigDataInterface
    {
        $this->set(
            ConfigDataInterface::APPLICATION_URL,
            $applicationUrl
                ? rtrim($applicationUrl, '/')
                : null
        );

        return $this;
    }

    public function getLdapFilterUserObject(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_FILTER_USER_OBJECT);
    }

    public function setLdapFilterUserObject(?string $filter): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_FILTER_USER_OBJECT, $filter);

        return $this;
    }

    public function getLdapFilterGroupObject(): ?string
    {
        return $this->get(ConfigDataInterface::LDAP_FILTER_GROUP_OBJECT);
    }

    public function setLdapFilterGroupObject(?string $filter): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_FILTER_GROUP_OBJECT, $filter);

        return $this;
    }

    public function getLdapFilterUserAttributes(): array
    {
        return $this->get(ConfigDataInterface::LDAP_FILTER_USER_ATTRIBUTES, []);
    }

    public function setLdapFilterUserAttributes(?array $attributes): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_FILTER_USER_ATTRIBUTES, $attributes);

        return $this;
    }

    public function getLdapFilterGroupAttributes(): array
    {
        return $this->get(ConfigDataInterface::LDAP_FILTER_GROUP_ATTRIBUTES, []);
    }

    public function setLdapFilterGroupAttributes(?array $attributes): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_FILTER_GROUP_ATTRIBUTES, $attributes);

        return $this;
    }

    public function isLdapDatabaseEnabled(): bool
    {
        return $this->get(ConfigDataInterface::LDAP_DATABASE_ENABLED, true);
    }

    public function setLdapDatabaseEnabled(?bool $ldapDatabaseEnabled): ConfigDataInterface
    {
        $this->set(ConfigDataInterface::LDAP_DATABASE_ENABLED, (int)$ldapDatabaseEnabled);

        return $this;
    }
}
