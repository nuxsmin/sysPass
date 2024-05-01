<?php
declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Core\Acl;

/**
 * Interface ActionsInterface para la definición de constantes de acciones disponibles.
 *
 * @package SP\Core\Acl
 */
interface AclActionsInterface
{
    public const ACCOUNT                    = 1;
    public const ACCOUNT_SEARCH             = 2;
    public const ACCOUNT_VIEW               = 3;
    public const ACCOUNT_CREATE             = 4;
    public const ACCOUNT_EDIT               = 5;
    public const ACCOUNT_DELETE             = 6;
    public const ACCOUNT_VIEW_PASS          = 7;
    public const ACCOUNT_EDIT_PASS          = 8;
    public const ACCOUNT_EDIT_RESTORE       = 9;
    public const ACCOUNT_COPY               = 10;
    public const ACCOUNT_COPY_PASS          = 11;
    public const ACCOUNT_REQUEST            = 12;
    public const ACCOUNT_FILE               = 20;
    public const ACCOUNT_FILE_VIEW          = 21;
    public const ACCOUNT_FILE_UPLOAD        = 22;
    public const ACCOUNT_FILE_DOWNLOAD      = 23;
    public const ACCOUNT_FILE_DELETE        = 24;
    public const ACCOUNT_FILE_SEARCH        = 25;
    public const ACCOUNT_FILE_LIST          = 26;
    public const ACCOUNT_FAVORITE           = 30;
    public const ACCOUNT_FAVORITE_VIEW      = 31;
    public const ACCOUNT_FAVORITE_ADD       = 32;
    public const ACCOUNT_FAVORITE_DELETE    = 33;
    public const ACCOUNT_HISTORY_VIEW       = 40;
    public const ACCOUNT_HISTORY_VIEW_PASS  = 41;
    public const ACCOUNT_HISTORY_COPY_PASS  = 42;
    public const CATEGORY                   = 101;
    public const CATEGORY_SEARCH            = 102;
    public const CATEGORY_VIEW              = 103;
    public const CATEGORY_CREATE            = 104;
    public const CATEGORY_EDIT              = 105;
    public const CATEGORY_DELETE            = 106;
    public const TAG                        = 201;
    public const TAG_SEARCH                 = 202;
    public const TAG_VIEW                   = 203;
    public const TAG_CREATE                 = 204;
    public const TAG_EDIT                   = 205;
    public const TAG_DELETE                 = 206;
    public const CLIENT                     = 301;
    public const CLIENT_SEARCH              = 302;
    public const CLIENT_VIEW                = 303;
    public const CLIENT_CREATE              = 304;
    public const CLIENT_EDIT                = 305;
    public const CLIENT_DELETE              = 306;
    public const CUSTOMFIELD                = 401;
    public const CUSTOMFIELD_SEARCH         = 402;
    public const CUSTOMFIELD_VIEW           = 403;
    public const CUSTOMFIELD_CREATE         = 404;
    public const CUSTOMFIELD_EDIT           = 405;
    public const CUSTOMFIELD_DELETE         = 406;
    public const CUSTOMFIELD_VIEW_PASS      = 407;
    public const PUBLICLINK                 = 501;
    public const PUBLICLINK_SEARCH          = 502;
    public const PUBLICLINK_VIEW            = 503;
    public const PUBLICLINK_CREATE          = 504;
    public const PUBLICLINK_EDIT            = 505;
    public const PUBLICLINK_DELETE          = 506;
    public const PUBLICLINK_REFRESH         = 507;
    public const FILE                       = 601;
    public const FILE_SEARCH                = 602;
    public const FILE_VIEW                  = 603;
    public const FILE_UPLOAD                = 604;
    public const FILE_DOWNLOAD              = 605;
    public const FILE_DELETE                = 606;
    public const USER                       = 701;
    public const USER_SEARCH                = 702;
    public const USER_VIEW                  = 703;
    public const USER_CREATE                = 704;
    public const USER_EDIT                  = 705;
    public const USER_DELETE                = 706;
    public const USER_EDIT_PASS             = 707;
    public const GROUP                      = 801;
    public const GROUP_SEARCH               = 802;
    public const GROUP_VIEW                 = 803;
    public const GROUP_CREATE               = 804;
    public const GROUP_EDIT                 = 805;
    public const GROUP_DELETE               = 806;
    public const PROFILE                    = 901;
    public const PROFILE_SEARCH             = 902;
    public const PROFILE_VIEW               = 903;
    public const PROFILE_CREATE             = 904;
    public const PROFILE_EDIT               = 905;
    public const PROFILE_DELETE             = 906;
    public const AUTHTOKEN                  = 1001;
    public const AUTHTOKEN_SEARCH           = 1002;
    public const AUTHTOKEN_VIEW             = 1003;
    public const AUTHTOKEN_CREATE           = 1004;
    public const AUTHTOKEN_EDIT             = 1005;
    public const AUTHTOKEN_DELETE           = 1006;
    public const PLUGIN                     = 1101;
    public const PLUGIN_SEARCH              = 1102;
    public const PLUGIN_VIEW                = 1103;
    public const PLUGIN_CREATE              = 1104;
    public const PLUGIN_ENABLE              = 1105;
    public const PLUGIN_DISABLE             = 1106;
    public const PLUGIN_RESET               = 1107;
    public const PLUGIN_DELETE              = 1108;
    public const WIKI                       = 1201;
    public const WIKI_SEARCH                = 1202;
    public const WIKI_VIEW                  = 1203;
    public const WIKI_CREATE                = 1204;
    public const WIKI_EDIT                  = 1205;
    public const WIKI_DELETE                = 1206;
    public const ACCOUNTMGR                 = 1301;
    public const ACCOUNTMGR_SEARCH          = 1302;
    public const ACCOUNTMGR_VIEW            = 1303;
    public const ACCOUNTMGR_DELETE          = 1304;
    public const ACCOUNTMGR_BULK_EDIT       = 1305;
    public const ACCOUNTMGR_HISTORY         = 1311;
    public const ACCOUNTMGR_HISTORY_SEARCH  = 1312;
    public const ACCOUNTMGR_HISTORY_VIEW    = 1313;
    public const ACCOUNTMGR_HISTORY_DELETE  = 1314;
    public const ACCOUNTMGR_HISTORY_RESTORE = 1315;
    public const NOTIFICATION               = 1401;
    public const NOTIFICATION_SEARCH        = 1402;
    public const NOTIFICATION_VIEW          = 1403;
    public const NOTIFICATION_CREATE        = 1404;
    public const NOTIFICATION_EDIT          = 1405;
    public const NOTIFICATION_DELETE        = 1406;
    public const NOTIFICATION_CHECK         = 1407;
    public const CONFIG                     = 1501;
    public const CONFIG_GENERAL             = 1502;
    public const CONFIG_ACCOUNT             = 1510;
    public const CONFIG_WIKI                = 1520;
    public const CONFIG_CRYPT               = 1530;
    public const CONFIG_CRYPT_REFRESH       = 1531;
    public const CONFIG_CRYPT_TEMPPASS      = 1532;
    public const CONFIG_BACKUP              = 1540;
    public const CONFIG_BACKUP_RUN          = 1541;
    public const CONFIG_IMPORT              = 1550;
    public const CONFIG_IMPORT_CSV          = 1551;
    public const CONFIG_IMPORT_XML          = 1552;
    public const CONFIG_EXPORT              = 1560;
    public const CONFIG_EXPORT_RUN          = 1561;
    public const CONFIG_MAIL                = 1570;
    public const CONFIG_LDAP                = 1580;
    public const CONFIG_LDAP_SYNC           = 1581;
    public const EVENTLOG                   = 1701;
    public const EVENTLOG_SEARCH            = 1702;
    public const EVENTLOG_CLEAR             = 1703;
    public const ITEMPRESET                 = 1801;
    public const ITEMPRESET_SEARCH          = 1802;
    public const ITEMPRESET_VIEW            = 1803;
    public const ITEMPRESET_CREATE          = 1804;
    public const ITEMPRESET_EDIT            = 1805;
    public const ITEMPRESET_DELETE          = 1806;
    public const TRACK                      = 1901;
    public const TRACK_SEARCH               = 1902;
    public const TRACK_UNLOCK               = 1903;
    public const TRACK_CLEAR                = 1904;
    public const ITEMS_MANAGE               = 5001;
    public const ACCESS_MANAGE              = 5002;
    public const SECURITY_MANAGE            = 5003;
    public const USERSETTINGS               = 5010;
    public const USERSETTINGS_GENERAL       = 5011;
}
