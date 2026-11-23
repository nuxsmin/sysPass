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

use AllowDynamicProperties;

/**
 * Snapshot of the 3.2.11 SP\Config\ConfigData layout.
 *
 * This class only exists to decode legacy config backups. It must never be persisted.
 */
#[AllowDynamicProperties]
final class LegacyConfigData
{
    /** @var string|null */
    private $upgradeKey = null;
    /** @var bool */
    private $dokuwikiEnabled = false;
    /** @var string|null */
    private $dokuwikiUrl = null;
    /** @var string|null */
    private $dokuwikiUrlBase = null;
    /** @var string|null */
    private $dokuwikiUser = null;
    /** @var string|null */
    private $dokuwikiPass = null;
    /** @var string|null */
    private $dokuwikiNamespace = null;
    /** @var int|null */
    private $ldapDefaultGroup = null;
    /** @var int|null */
    private $ldapDefaultProfile = null;
    /** @var bool */
    private $proxyEnabled = false;
    /** @var string|null */
    private $proxyServer = null;
    /** @var int */
    private $proxyPort = 8080;
    /** @var string|null */
    private $proxyUser = null;
    /** @var string|null */
    private $proxyPass = null;
    /** @var int */
    private $publinksMaxViews = 3;
    /** @var int */
    private $publinksMaxTime = 600;
    /** @var bool */
    private $publinksEnabled = false;
    /** @var int */
    private $accountCount = 12;
    /** @var bool */
    private $accountLink = true;
    /** @var bool */
    private $checkUpdates = false;
    /** @var bool */
    private $checknotices = false;
    /** @var string|null */
    private $configHash = null;
    /** @var string|null */
    private $dbHost = null;
    /** @var string|null */
    private $dbSocket = null;
    /** @var string|null */
    private $dbName = null;
    /** @var string|null */
    private $dbPass = null;
    /** @var string|null */
    private $dbUser = null;
    /** @var int */
    private $dbPort = 3306;
    /** @var bool */
    private $debug = false;
    /** @var bool */
    private $demoEnabled = false;
    /** @var array<mixed> */
    private $filesAllowedExts = [];
    /** @var array<mixed> */
    private $filesAllowedMime = [];
    /** @var int */
    private $filesAllowedSize = 1024;
    /** @var bool */
    private $filesEnabled = true;
    /** @var bool */
    private $globalSearch = true;
    /** @var bool */
    private $installed = false;
    /** @var string|null */
    private $ldapBase = null;
    /** @var string|null */
    private $ldapBindUser = null;
    /** @var string|null */
    private $ldapBindPass = null;
    /** @var string|null */
    private $ldapProxyUser = null;
    /** @var bool */
    private $ldapEnabled = false;
    /** @var bool */
    private $ldapAds = false;
    /** @var int|null */
    private $ldapType = null;
    /** @var string|null */
    private $ldapGroup = null;
    /** @var string|null */
    private $ldapServer = null;
    /** @var bool */
    private $logEnabled = true;
    /** @var array<mixed> */
    private $logEvents = [];
    /** @var bool */
    private $mailAuthenabled = false;
    /** @var bool */
    private $mailEnabled = false;
    /** @var string|null */
    private $mailFrom = null;
    /** @var string|null */
    private $mailPass = null;
    /** @var int */
    private $mailPort = 25;
    /** @var bool */
    private $mailRequestsEnabled = false;
    /** @var string|null */
    private $mailSecurity = null;
    /** @var string|null */
    private $mailServer = null;
    /** @var string|null */
    private $mailUser = null;
    /** @var array<mixed> */
    private $mailRecipients = [];
    /** @var array<mixed> */
    private $mailEvents = [];
    /** @var bool */
    private $maintenance = false;
    /** @var string|null */
    private $passwordSalt = null;
    /** @var bool */
    private $resultsAsCards = false;
    /** @var int */
    private $sessionTimeout = 300;
    /** @var string|null */
    private $siteLang = null;
    /** @var string */
    private $siteTheme = 'material-blue';
    /** @var string|null */
    private $configVersion = null;
    /** @var string|null */
    private $appVersion = null;
    /** @var string|null */
    private $databaseVersion = null;
    /** @var bool */
    private $wikiEnabled = false;
    /** @var array<mixed> */
    private $wikiFilter = [];
    /** @var string|null */
    private $wikiPageurl = null;
    /** @var string|null */
    private $wikiSearchurl = null;
    /** @var int */
    private $configDate = 0;
    /** @var bool */
    private $publinksImageEnabled = false;
    /** @var string|null */
    private $backup_hash = null;
    /** @var string|null */
    private $export_hash = null;
    /** @var bool */
    private $httpsEnabled = false;
    /** @var bool */
    private $syslogEnabled = false;
    /** @var bool */
    private $syslogRemoteEnabled = false;
    /** @var string|null */
    private $syslogServer = null;
    /** @var int */
    private $syslogPort = 514;
    /** @var bool */
    private $accountPassToImage = false;
    /** @var string|null */
    private $configSaver = null;
    /** @var bool */
    private $encryptSession = false;
    /** @var bool */
    private $accountFullGroupAccess = false;
    /** @var bool */
    private $authBasicEnabled = true;
    /** @var bool */
    private $authBasicAutoLoginEnabled = true;
    /** @var string|null */
    private $authBasicDomain = null;
    /** @var int|null */
    private $ssoDefaultGroup = null;
    /** @var int|null */
    private $ssoDefaultProfile = null;
    /** @var bool */
    private $accountExpireEnabled = false;
    /** @var int */
    private $accountExpireTime = 10368000;
    /** @var bool */
    private $ldapTlsEnabled = false;
    /** @var string|null */
    private $applicationUrl = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
