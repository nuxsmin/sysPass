<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Config\Legacy;

use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;

/**
 * Maps the 3.2.11 SP\Config\ConfigData layout onto the current ConfigData collection.
 */
final class LegacyConfigDataMapper
{
    /**
     * @var array<string, array{string, string, string}>
     */
    public const SETTERS = [
        'upgradeKey' => [ConfigDataInterface::UPGRADE_KEY, 'setUpgradeKey', 'nullable-string'],
        'dokuwikiEnabled' => [ConfigDataInterface::DOKUWIKI_ENABLED, 'setDokuwikiEnabled', 'nullable-bool'],
        'dokuwikiUrl' => [ConfigDataInterface::DOKUWIKI_URL, 'setDokuwikiUrl', 'nullable-string'],
        'dokuwikiUrlBase' => [ConfigDataInterface::DOKUWIKI_URL_BASE, 'setDokuwikiUrlBase', 'nullable-string'],
        'dokuwikiUser' => [ConfigDataInterface::DOKUWIKI_USER, 'setDokuwikiUser', 'nullable-string'],
        'dokuwikiPass' => [ConfigDataInterface::DOKUWIKI_PASS, 'setDokuwikiPass', 'nullable-string'],
        'dokuwikiNamespace' => [ConfigDataInterface::DOKUWIKI_NAMESPACE, 'setDokuwikiNamespace', 'nullable-string'],
        'ldapDefaultGroup' => [ConfigDataInterface::LDAP_DEFAULT_GROUP, 'setLdapDefaultGroup', 'nullable-int'],
        'ldapDefaultProfile' => [ConfigDataInterface::LDAP_DEFAULT_PROFILE, 'setLdapDefaultProfile', 'nullable-int'],
        'proxyEnabled' => [ConfigDataInterface::PROXY_ENABLED, 'setProxyEnabled', 'nullable-bool'],
        'proxyServer' => [ConfigDataInterface::PROXY_SERVER, 'setProxyServer', 'nullable-string'],
        'proxyPort' => [ConfigDataInterface::PROXY_PORT, 'setProxyPort', 'int'],
        'proxyUser' => [ConfigDataInterface::PROXY_USER, 'setProxyUser', 'nullable-string'],
        'proxyPass' => [ConfigDataInterface::PROXY_PASS, 'setProxyPass', 'nullable-string'],
        'publinksMaxViews' => [ConfigDataInterface::PUBLINKS_MAX_VIEWS, 'setPublinksMaxViews', 'int'],
        'publinksMaxTime' => [ConfigDataInterface::PUBLINKS_MAX_TIME, 'setPublinksMaxTime', 'int'],
        'publinksEnabled' => [ConfigDataInterface::PUBLINKS_ENABLED, 'setPublinksEnabled', 'nullable-bool'],
        'accountCount' => [ConfigDataInterface::ACCOUNT_COUNT, 'setAccountCount', 'int'],
        'accountLink' => [ConfigDataInterface::ACCOUNT_LINK, 'setAccountLink', 'nullable-bool'],
        'checkUpdates' => [ConfigDataInterface::CHECK_UPDATES, 'setCheckUpdates', 'nullable-bool'],
        'checknotices' => [ConfigDataInterface::CHECK_NOTICES, 'setCheckNotices', 'nullable-bool'],
        'dbHost' => [ConfigDataInterface::DB_HOST, 'setDbHost', 'nullable-string'],
        'dbSocket' => [ConfigDataInterface::DB_SOCKET, 'setDbSocket', 'nullable-string'],
        'dbName' => [ConfigDataInterface::DB_NAME, 'setDbName', 'nullable-string'],
        'dbPass' => [ConfigDataInterface::DB_PASS, 'setDbPass', 'nullable-string'],
        'dbUser' => [ConfigDataInterface::DB_USER, 'setDbUser', 'nullable-string'],
        'dbPort' => [ConfigDataInterface::DB_PORT, 'setDbPort', 'int'],
        'debug' => [ConfigDataInterface::DEBUG, 'setDebug', 'nullable-bool'],
        'demoEnabled' => [ConfigDataInterface::DEMO_ENABLED, 'setDemoEnabled', 'nullable-bool'],
        'filesAllowedMime' => [ConfigDataInterface::FILES_ALLOWED_MIME, 'setFilesAllowedMime', 'array'],
        'filesAllowedSize' => [ConfigDataInterface::FILES_ALLOWED_SIZE, 'setFilesAllowedSize', 'int'],
        'filesEnabled' => [ConfigDataInterface::FILES_ENABLED, 'setFilesEnabled', 'nullable-bool'],
        'globalSearch' => [ConfigDataInterface::GLOBAL_SEARCH, 'setGlobalSearch', 'nullable-bool'],
        'installed' => [ConfigDataInterface::INSTALLED, 'setInstalled', 'nullable-bool'],
        'ldapBase' => [ConfigDataInterface::LDAP_BASE, 'setLdapBase', 'nullable-string'],
        'ldapBindUser' => [ConfigDataInterface::LDAP_BIND_USER, 'setLdapBindUser', 'nullable-string'],
        'ldapBindPass' => [ConfigDataInterface::LDAP_BIND_PASS, 'setLdapBindPass', 'nullable-string'],
        'ldapEnabled' => [ConfigDataInterface::LDAP_ENABLED, 'setLdapEnabled', 'nullable-bool'],
        'ldapType' => [ConfigDataInterface::LDAP_TYPE, 'setLdapType', 'nullable-int'],
        'ldapGroup' => [ConfigDataInterface::LDAP_GROUP, 'setLdapGroup', 'nullable-string'],
        'ldapServer' => [ConfigDataInterface::LDAP_SERVER, 'setLdapServer', 'nullable-string'],
        'logEnabled' => [ConfigDataInterface::LOG_ENABLED, 'setLogEnabled', 'nullable-bool'],
        'logEvents' => [ConfigDataInterface::LOG_EVENTS, 'setLogEvents', 'array'],
        'mailAuthenabled' => [ConfigDataInterface::MAIL_AUTHENABLED, 'setMailAuthenabled', 'nullable-bool'],
        'mailEnabled' => [ConfigDataInterface::MAIL_ENABLED, 'setMailEnabled', 'nullable-bool'],
        'mailFrom' => [ConfigDataInterface::MAIL_FROM, 'setMailFrom', 'nullable-string'],
        'mailPass' => [ConfigDataInterface::MAIL_PASS, 'setMailPass', 'nullable-string'],
        'mailPort' => [ConfigDataInterface::MAIL_PORT, 'setMailPort', 'int'],
        'mailRequestsEnabled' => [
            ConfigDataInterface::MAIL_REQUESTS_ENABLED,
            'setMailRequestsEnabled',
            'nullable-bool'
        ],
        'mailSecurity' => [ConfigDataInterface::MAIL_SECURITY, 'setMailSecurity', 'nullable-string'],
        'mailServer' => [ConfigDataInterface::MAIL_SERVER, 'setMailServer', 'nullable-string'],
        'mailUser' => [ConfigDataInterface::MAIL_USER, 'setMailUser', 'nullable-string'],
        'mailRecipients' => [ConfigDataInterface::MAIL_RECIPIENTS, 'setMailRecipients', 'array'],
        'mailEvents' => [ConfigDataInterface::MAIL_EVENTS, 'setMailEvents', 'array'],
        'maintenance' => [ConfigDataInterface::MAINTENANCE, 'setMaintenance', 'nullable-bool'],
        'passwordSalt' => [ConfigDataInterface::PASSWORD_SALT, 'setPasswordSalt', 'nullable-string'],
        'resultsAsCards' => [ConfigDataInterface::RESULTS_AS_CARDS, 'setResultsAsCards', 'nullable-bool'],
        'sessionTimeout' => [ConfigDataInterface::SESSION_TIMEOUT, 'setSessionTimeout', 'int'],
        'siteLang' => [ConfigDataInterface::SITE_LANG, 'setSiteLang', 'nullable-string'],
        'siteTheme' => [ConfigDataInterface::SITE_THEME, 'setSiteTheme', 'nullable-string'],
        'configVersion' => [ConfigDataInterface::CONFIG_VERSION, 'setConfigVersion', 'nullable-string'],
        'appVersion' => [ConfigDataInterface::APP_VERSION, 'setAppVersion', 'nullable-string'],
        'databaseVersion' => [ConfigDataInterface::DATABASE_VERSION, 'setDatabaseVersion', 'nullable-string'],
        'wikiEnabled' => [ConfigDataInterface::WIKI_ENABLED, 'setWikiEnabled', 'nullable-bool'],
        'wikiFilter' => [ConfigDataInterface::WIKI_FILTER, 'setWikiFilter', 'array'],
        'wikiPageurl' => [ConfigDataInterface::WIKI_PAGEURL, 'setWikiPageurl', 'nullable-string'],
        'wikiSearchurl' => [ConfigDataInterface::WIKI_SEARCHURL, 'setWikiSearchurl', 'nullable-string'],
        'configDate' => [ConfigDataInterface::CONFIG_DATE, 'setConfigDate', 'int'],
        'publinksImageEnabled' => [
            ConfigDataInterface::PUBLINKS_IMAGE_ENABLED,
            'setPublinksImageEnabled',
            'nullable-bool'
        ],
        'backup_hash' => [ConfigDataInterface::BACKUP_HASH, 'setBackupHash', 'nullable-string'],
        'export_hash' => [ConfigDataInterface::EXPORT_HASH, 'setExportHash', 'nullable-string'],
        'httpsEnabled' => [ConfigDataInterface::HTTPS_ENABLED, 'setHttpsEnabled', 'nullable-bool'],
        'syslogEnabled' => [ConfigDataInterface::SYSLOG_ENABLED, 'setSyslogEnabled', 'nullable-bool'],
        'syslogRemoteEnabled' => [
            ConfigDataInterface::SYSLOG_REMOTE_ENABLED,
            'setSyslogRemoteEnabled',
            'nullable-bool'
        ],
        'syslogServer' => [ConfigDataInterface::SYSLOG_SERVER, 'setSyslogServer', 'nullable-string'],
        'syslogPort' => [ConfigDataInterface::SYSLOG_PORT, 'setSyslogPort', 'int'],
        'accountPassToImage' => [
            ConfigDataInterface::ACCOUNT_PASS_TO_IMAGE,
            'setAccountPassToImage',
            'nullable-bool'
        ],
        'configSaver' => [ConfigDataInterface::CONFIG_SAVER, 'setConfigSaver', 'nullable-string'],
        'encryptSession' => [ConfigDataInterface::ENCRYPT_SESSION, 'setEncryptSession', 'nullable-bool'],
        'accountFullGroupAccess' => [
            ConfigDataInterface::ACCOUNT_FULL_GROUP_ACCESS,
            'setAccountFullGroupAccess',
            'nullable-bool'
        ],
        'authBasicEnabled' => [ConfigDataInterface::AUTH_BASIC_ENABLED, 'setAuthBasicEnabled', 'nullable-bool'],
        'authBasicAutoLoginEnabled' => [
            ConfigDataInterface::AUTH_BASIC_AUTO_LOGIN_ENABLED,
            'setAuthBasicAutoLoginEnabled',
            'nullable-bool'
        ],
        'authBasicDomain' => [ConfigDataInterface::AUTH_BASIC_DOMAIN, 'setAuthBasicDomain', 'nullable-string'],
        'ssoDefaultGroup' => [ConfigDataInterface::SSO_DEFAULT_GROUP, 'setSsoDefaultGroup', 'nullable-int'],
        'ssoDefaultProfile' => [ConfigDataInterface::SSO_DEFAULT_PROFILE, 'setSsoDefaultProfile', 'nullable-int'],
        'accountExpireEnabled' => [
            ConfigDataInterface::ACCOUNT_EXPIRE_ENABLED,
            'setAccountExpireEnabled',
            'nullable-bool'
        ],
        'accountExpireTime' => [ConfigDataInterface::ACCOUNT_EXPIRE_TIME, 'setAccountExpireTime', 'int'],
        'ldapTlsEnabled' => [ConfigDataInterface::LDAP_TLS_ENABLED, 'setLdapTlsEnabled', 'nullable-bool'],
        'applicationUrl' => [ConfigDataInterface::APPLICATION_URL, 'setApplicationUrl', 'nullable-string'],
    ];

    public const DROPPED = ['ldapAds', 'ldapProxyUser'];

    public const DIRECT = [
        'filesAllowedExts' => ConfigDataInterface::FILES_ALLOWED_EXTS,
    ];

    private const IGNORED = [
        'configHash',
    ];

    /**
     * @return array{dropped: string[], unknown: string[]}
     */
    public static function map(LegacyConfigData $legacy, ConfigData $config): array
    {
        $dropped = [];
        $unknown = [];

        foreach ($legacy->toArray() as $key => $value) {
            $property = preg_replace('/^\x00[^\x00]*\x00/', '', (string)$key);

            if ($property === null || $property === '') {
                continue;
            }

            if (in_array($property, self::DROPPED, true)) {
                $dropped[] = $property;
                continue;
            }

            if (in_array($property, self::IGNORED, true)) {
                continue;
            }

            if (isset(self::DIRECT[$property])) {
                $config->set(self::DIRECT[$property], (array)$value);
                continue;
            }

            if (!isset(self::SETTERS[$property])) {
                $unknown[] = $property;
                continue;
            }

            [$constant, $setter, $cast] = self::SETTERS[$property];

            $config->{$setter}(self::cast($value, $cast));
        }

        $dropped = array_values(array_unique($dropped));
        $unknown = array_values(array_unique($unknown));

        sort($dropped);
        sort($unknown);

        return [
            'dropped' => $dropped,
            'unknown' => $unknown,
        ];
    }

    private static function cast(mixed $value, string $cast): mixed
    {
        return match ($cast) {
            'int' => (int)$value,
            'bool' => (bool)$value,
            'string' => (string)$value,
            'array' => (array)$value,
            'nullable-int' => $value === null ? null : (int)$value,
            'nullable-bool' => $value === null ? null : (bool)$value,
            'nullable-string' => $value === null ? null : (string)$value,
            default => $value,
        };
    }
}
