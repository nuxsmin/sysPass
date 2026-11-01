<?php

declare(strict_types=1);
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

namespace SP\Tests\Stubs;

use SP\Domain\Common\Adapters\SerializedPayloadRewriter;

/**
 * Class DurablePayloadFixtures
 */
final class DurablePayloadFixtures
{
    public static function vaultWithCrypt(): string
    {
        return 'O:19:"SP\\Core\\Crypt\\Vault":4:{s:25:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'data";s:202:"def50200c4c712876bf463e89773dec424fe70dd9226080f83239ae5cf9a2610b3163f15cb936'
               . 'eba0f80f3c16bda737448e5f19ef3095d32bfde24f935c7688bdcd663d981cbd0e52c3a1a829f9a06454dcf58e'
               . '3a448c665bf509b0bdda162945f4eab06cc";s:24:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'key";s:512:"def10000def502007453b9389362f842e1c99d0031ecda6b092c2ffd758ce14d3aca2b377edd19'
               . '102b025a9eeec68952bd8d6dbf3dbb2fe907b0150a84bf239ec431f7c1bd0ce8acd96ad110269cf9b01571ffc4'
               . '7d49c74fa37c74fc8c202f9e827209b5e01710388b51f30698c6c366c2f303f3ac34c8faffcbc598a9c6dd9e03'
               . '521c112a92028ec71639f3307ec65b1a8cc7978598eab12d62b98de02e158daef10630976a81ab305245030fbf'
               . 'ee21a8220164613c29759df33f63c2a7ac88661eeeddb979bb3d22a76257294ed2fadfaa2b0d08a8d711eec841'
               . '7aaa7050484dc6f0df5e97872d1ddb9a360ad1c4d1c7e69bed69c5af0729bc7d34cdb65dbf";s:28:"' . "\0"
               . 'SP\\Core\\Crypt\\Vault' . "\0" . 'timeSet";i:1793467221;s:26:"' . "\0" . 'SP\\Core\\Crypt\\Vault'
               . "\0" . 'crypt";O:19:"SP\\Core\\Crypt\\Crypt":0:{}}';
    }

    public static function publicLinkVault(): string
    {
        return 'O:19:"SP\\Core\\Crypt\\Vault":4:{s:25:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'data";s:292:"def50200810afea2a2de5028546a25930f92dc63e5be256083c2f5fd28415ec9430f32ce5104e'
               . 'de5a39cce2e4408f91ce9282a241e3f624f302465b6296284c05494243a72cc929f3af75a3c80dbfb45bf124c6'
               . '190694b319d26c9468d79e60475cf81fe7cb8f5bd3ea49789d4f47fdc1f14df3f829b804777703d4d3e8237816'
               . '4d547a2c8ad62e81db41028152efb9438b3";s:24:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'key";s:512:"def10000def502000e10e42616131aaa4e0bc693dd9a85192739e91c08a7f3961cc614aa1e7013'
               . 'd27cc9db2680b1ffb1e4fa81484fc2f28c59d933a79af5c1d5572555a2a7bb5c92cac75eaceb94a98806c1fddf'
               . '321844187f06c7241840ab0667c4845805a7c5c68e80defc32a427828caa61346372b1296dd93c76ecc3b09c29'
               . 'a7aed5108069da503680f5dea14fcc4ea4b0c43e8c415db49c034626ccdc9ca5371a43f93f65f042ab9ed14e3d'
               . 'f044b9f83af3e0467a8f38b1c30a04824babfa7aa0959b3649d11a385311dd71c31edeb09b37d72b705c8c4622'
               . 'd1145abc0eeceae0e0066ccf80890ef8436706a414b4d82cded1e220f9f2350808d29e2de6";s:28:"' . "\0"
               . 'SP\\Core\\Crypt\\Vault' . "\0" . 'timeSet";i:1793467221;s:26:"' . "\0" . 'SP\\Core\\Crypt\\Vault'
               . "\0" . 'crypt";O:19:"SP\\Core\\Crypt\\Crypt":0:{}}';
    }

    public static function profile(): string
    {
        return 'O:24:"SP\\DataModel\\ProfileData":30:{s:10:"' . "\0" . '*' . "\0" . 'accView";b:1;s:14:"' . "\0" . '*'
               . "\0" . 'accViewPass";b:1;s:17:"' . "\0" . '*' . "\0" . 'accViewHistory";b:1;s:10:"' . "\0" . '*' . "\0"
               . 'accEdit";b:1;s:14:"' . "\0" . '*' . "\0" . 'accEditPass";b:1;s:9:"' . "\0" . '*' . "\0"
               . 'accAdd";b:1;s:12:"' . "\0" . '*' . "\0" . 'accDelete";b:1;s:11:"' . "\0" . '*' . "\0"
               . 'accFiles";b:1;s:13:"' . "\0" . '*' . "\0" . 'accPrivate";b:0;s:18:"' . "\0" . '*' . "\0"
               . 'accPrivateGroup";b:0;s:16:"' . "\0" . '*' . "\0" . 'accPermission";b:1;s:17:"' . "\0" . '*' . "\0"
               . 'accPublicLinks";b:1;s:18:"' . "\0" . '*' . "\0" . 'accGlobalSearch";b:1;s:16:"' . "\0" . '*' . "\0"
               . 'configGeneral";b:1;s:19:"' . "\0" . '*' . "\0" . 'configEncryption";b:1;s:15:"' . "\0" . '*' . "\0"
               . 'configBackup";b:1;s:15:"' . "\0" . '*' . "\0" . 'configImport";b:1;s:11:"' . "\0" . '*' . "\0"
               . 'mgmUsers";b:1;s:12:"' . "\0" . '*' . "\0" . 'mgmGroups";b:1;s:14:"' . "\0" . '*' . "\0"
               . 'mgmProfiles";b:1;s:16:"' . "\0" . '*' . "\0" . 'mgmCategories";b:1;s:15:"' . "\0" . '*' . "\0"
               . 'mgmCustomers";b:1;s:15:"' . "\0" . '*' . "\0" . 'mgmApiTokens";b:1;s:17:"' . "\0" . '*' . "\0"
               . 'mgmPublicLinks";b:1;s:14:"' . "\0" . '*' . "\0" . 'mgmAccounts";b:1;s:10:"' . "\0" . '*' . "\0"
               . 'mgmTags";b:1;s:11:"' . "\0" . '*' . "\0" . 'mgmFiles";b:1;s:17:"' . "\0" . '*' . "\0"
               . 'mgmItemsPreset";b:1;s:6:"' . "\0" . '*' . "\0" . 'evl";b:1;s:18:"' . "\0" . '*' . "\0"
               . 'mgmCustomFields";b:1;}';
    }

    public static function preferences(): string
    {
        return 'O:32:"SP\\DataModel\\UserPreferencesData":11:{s:7:"user_id";i:1;s:4:"lang";s:5:"es_ES";s:5:"'
               . 'theme";s:13:"material-blue";s:14:"resultsPerPage";i:6;s:11:"accountLink";b:1;s:9:"sortView'
               . 's";b:1;s:9:"topNavbar";b:0;s:15:"optionalActions";b:1;s:14:"resultsAsCards";b:1;s:18:"chec'
               . 'kNotifications";b:1;s:24:"showAccountSearchFilters";b:0;}';
    }

    public static function password(): string
    {
        return 'O:32:"SP\\DataModel\\ItemPreset\\Password":10:{s:40:"' . "\0" . 'SP\\DataModel\\ItemPreset\\Password'
               . "\0" . 'length";i:12;s:44:"' . "\0" . 'SP\\DataModel\\ItemPreset\\Password' . "\0"
               . 'useNumbers";b:1;s:44:"' . "\0" . 'SP\\DataModel\\ItemPreset\\Password' . "\0"
               . 'useLetters";b:1;s:44:"' . "\0" . 'SP\\DataModel\\ItemPreset\\Password' . "\0"
               . 'useSymbols";b:0;s:42:"' . "\0" . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'useUpper";b:1;s:42:"'
               . "\0" . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'useLower";b:1;s:42:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'useImage";b:0;s:44:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'expireTime";i:7464960000;s:39:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'score";i:3;s:39:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\Password' . "\0" . 'regex";s:7:"/[a-z]/";}';
    }

    public static function accountPrivate(): string
    {
        return 'O:38:"SP\\DataModel\\ItemPreset\\AccountPrivate":2:{s:51:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPrivate' . "\0" . 'privateUser";b:1;s:52:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPrivate' . "\0" . 'privateGroup";b:0;}';
    }

    public static function accountPermission(): string
    {
        return 'O:41:"SP\\DataModel\\ItemPreset\\AccountPermission":4:{s:52:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPermission' . "\0" . 'usersView";a:1:{i:0;i:1;}s:52:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPermission' . "\0" . 'usersEdit";a:1:{i:0;i:2;}s:57:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPermission' . "\0" . 'userGroupsView";a:1:{i:0;i:3;}s:57:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\AccountPermission' . "\0" . 'userGroupsEdit";a:1:{i:0;i:4;}}';
    }

    public static function sessionTimeout(): string
    {
        return 'O:38:"SP\\DataModel\\ItemPreset\\SessionTimeout":3:{s:47:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\SessionTimeout' . "\0" . 'address";s:8:"10.0.0.1";s:44:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\SessionTimeout' . "\0" . 'mask";s:13:"255.255.255.0";s:47:"' . "\0"
               . 'SP\\DataModel\\ItemPreset\\SessionTimeout' . "\0" . 'timeout";i:3600;}';
    }

    public static function configData(): string
    {
        return 'O:20:"SP\\Config\\ConfigData":93:{s:32:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'upgradeKey";s:11:"upgrade-key";s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dokuwikiEnabled";b:1;s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dokuwikiUrl";s:24:"https://wiki.example.com";s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dokuwikiUrlBase";s:33:"https://wiki.example.com/doku.php";s:34:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'dokuwikiUser";s:9:"wiki-user";s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dokuwikiPass";s:9:"wiki-pass";s:39:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dokuwikiNamespace";s:7:"syspass";s:38:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapDefaultGroup";i:1;s:40:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapDefaultProfile";i:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'proxyEnabled";b:1;s:33:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'proxyServer";s:17:"proxy.example.com";s:31:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'proxyPort";i:8080;s:31:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'proxyUser";s:10:"proxy-user";s:31:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'proxyPass";s:10:"proxy-pass";s:38:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'publinksMaxViews";i:3;s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'publinksMaxTime";i:600;s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'publinksEnabled";b:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'accountCount";i:6;s:33:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'accountLink";b:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'checkUpdates";b:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'checknotices";b:1;s:32:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'configHash";s:11:"config-hash";s:28:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'dbHost";s:14:"syspass-db-dev";s:30:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'dbSocket";s:27:"/var/run/mysqld/mysqld.sock";s:28:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'dbName";s:7:"syspass";s:28:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'dbPass";s:7:"db-pass";s:28:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'dbUser";s:7:"db-user";s:28:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'dbPort";i:3306;s:27:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'debug";b:0;s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'demoEnabled";b:0;s:38:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'filesAllowedExts";a:4:{i:0;s:3:"PDF";i:1;s:3:"JPG";i:2;s:3:"PNG";i:3;s:3:"TXT";}s:38:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0"
               . 'filesAllowedMime";a:2:{i:0;s:15:"application/pdf";i:1;s:9:"image/png";}s:38:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'filesAllowedSize";i:1024;s:34:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'filesEnabled";b:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'globalSearch";b:1;s:31:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'installed";b:1;s:30:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'ldapBase";s:17:"dc=example,dc=com";s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapBindUser";s:26:"cn=admin,dc=example,dc=com";s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapBindPass";s:9:"ldap-pass";s:35:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapProxyUser";s:0:"";s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'ldapEnabled";b:1;s:29:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'ldapAds";b:0;s:30:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapType";i:1;s:31:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'ldapGroup";s:9:"cn=groups";s:32:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'ldapServer";s:16:"ldap.example.com";s:32:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'logEnabled";b:1;s:31:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'logEvents";a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailAuthenabled";b:1;s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'mailEnabled";b:1;s:30:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'mailFrom";s:19:"syspass@example.com";s:30:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'mailPass";s:9:"mail-pass";s:30:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'mailPort";i:25;s:41:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailRequestsEnabled";b:1;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailSecurity";s:3:"tls";s:32:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailServer";s:16:"mail.example.com";s:30:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailUser";s:9:"mail-user";s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailRecipients";a:1:{i:0;s:17:"admin@example.com";}s:32:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'mailEvents";a:2:{i:0;i:4;i:1;i:5;}s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'maintenance";b:0;s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'passwordSalt";s:13:"password-salt";s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'resultsAsCards";b:1;s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'sessionTimeout";i:300;s:30:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'siteLang";s:5:"es_ES";s:31:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'siteTheme";s:13:"material-blue";s:35:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'configVersion";s:6:"3211.0";s:32:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'appVersion";s:6:"3.2.11";s:37:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'databaseVersion";s:6:"3211.0";s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'wikiEnabled";b:1;s:32:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'wikiFilter";a:1:{i:0;s:7:"account";}s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'wikiPageurl";s:29:"https://wiki.example.com/page";s:35:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'wikiSearchurl";s:31:"https://wiki.example.com/search";s:32:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'configDate";i:1531353681;s:42:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'publinksImageEnabled";b:1;s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'backup_hash";s:40:"c9142988b9d91a375ca83387f0593c7a12eaf5a2";s:33:"' . "\0" . 'SP\\Config\\ConfigData'
               . "\0" . 'export_hash";s:11:"export-hash";s:34:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'httpsEnabled";b:1;s:35:"' . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'syslogEnabled";b:1;s:41:"'
               . "\0" . 'SP\\Config\\ConfigData' . "\0" . 'syslogRemoteEnabled";b:1;s:34:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'syslogServer";s:18:"syslog.example.com";s:32:"' . "\0"
               . 'SP\\Config\\ConfigData' . "\0" . 'syslogPort";i:514;s:40:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'accountPassToImage";b:0;s:33:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'configSaver";s:12:"config-saver";s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'encryptSession";b:1;s:44:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'accountFullGroupAccess";b:0;s:38:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'authBasicEnabled";b:1;s:47:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'authBasicAutoLoginEnabled";b:1;s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'authBasicDomain";s:7:"EXAMPLE";s:37:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ssoDefaultGroup";i:1;s:39:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ssoDefaultProfile";i:1;s:42:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'accountExpireEnabled";b:1;s:39:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'accountExpireTime";i:10368000;s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'ldapTlsEnabled";b:1;s:36:"' . "\0" . 'SP\\Config\\ConfigData' . "\0"
               . 'applicationUrl";s:27:"https://syspass.example.com";}';
    }

    public static function vaultWithoutCrypt(): string
    {
        return 'O:19:"SP\\Core\\Crypt\\Vault":4:{s:25:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'data";s:7:"payload";s:24:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'key";s:3:"key";s:28:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0"
               . 'timeSet";i:1;s:32:"' . "\0" . 'SP\\Core\\Crypt\\Vault' . "\0" . 'timeUpdated";i:0;}';
    }

    public static function currentVault(): string
    {
        return SerializedPayloadRewriter::rewrite(self::vaultWithCrypt());
    }

    public static function jsonPreferences(): string
    {
        return '{"lang":"en_US","theme":"material-blue","resultsPerPage":12}';
    }

    public static function jsonProfile(): string
    {
        return '{"accView":true,"accViewPass":true,"accEdit":true}';
    }

    public static function malformed(): string
    {
        return 'not-a-serialized-payload';
    }
}
