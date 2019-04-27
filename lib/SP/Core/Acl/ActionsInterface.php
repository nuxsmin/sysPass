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

namespace SP\Core\Acl;

/**
 * Interface ActionsInterface para la definición de constantes de acciones disponibles.
 *
 * @package SP\Core\Acl
 */
interface ActionsInterface
{
    const ACCOUNT = 1;
    const ACCOUNT_SEARCH = 2;
    const ACCOUNT_VIEW = 3;
    const ACCOUNT_CREATE = 4;
    const ACCOUNT_EDIT = 5;
    const ACCOUNT_DELETE = 6;
    const ACCOUNT_VIEW_PASS = 7;
    const ACCOUNT_EDIT_PASS = 8;
    const ACCOUNT_EDIT_RESTORE = 9;
    const ACCOUNT_COPY = 10;
    const ACCOUNT_COPY_PASS = 11;
    const ACCOUNT_REQUEST = 12;
    const ACCOUNT_FILE = 20;
    const ACCOUNT_FILE_VIEW = 21;
    const ACCOUNT_FILE_UPLOAD = 22;
    const ACCOUNT_FILE_DOWNLOAD = 23;
    const ACCOUNT_FILE_DELETE = 24;
    const ACCOUNT_FILE_SEARCH = 25;
    const ACCOUNT_FILE_LIST = 26;
    const ACCOUNT_FAVORITE = 30;
    const ACCOUNT_FAVORITE_VIEW = 31;
    const ACCOUNT_FAVORITE_ADD = 32;
    const ACCOUNT_FAVORITE_DELETE = 33;
    const ACCOUNT_HISTORY_VIEW = 40;
    const ACCOUNT_HISTORY_VIEW_PASS = 41;
    const ACCOUNT_HISTORY_COPY_PASS = 42;
    const CATEGORY = 101;
    const CATEGORY_SEARCH = 102;
    const CATEGORY_VIEW = 103;
    const CATEGORY_CREATE = 104;
    const CATEGORY_EDIT = 105;
    const CATEGORY_DELETE = 106;
    const TAG = 201;
    const TAG_SEARCH = 202;
    const TAG_VIEW = 203;
    const TAG_CREATE = 204;
    const TAG_EDIT = 205;
    const TAG_DELETE = 206;
    const CLIENT = 301;
    const CLIENT_SEARCH = 302;
    const CLIENT_VIEW = 303;
    const CLIENT_CREATE = 304;
    const CLIENT_EDIT = 305;
    const CLIENT_DELETE = 306;
    const CUSTOMFIELD = 401;
    const CUSTOMFIELD_SEARCH = 402;
    const CUSTOMFIELD_VIEW = 403;
    const CUSTOMFIELD_CREATE = 404;
    const CUSTOMFIELD_EDIT = 405;
    const CUSTOMFIELD_DELETE = 406;
    const CUSTOMFIELD_VIEW_PASS = 407;
    const PUBLICLINK = 501;
    const PUBLICLINK_SEARCH = 502;
    const PUBLICLINK_VIEW = 503;
    const PUBLICLINK_CREATE = 504;
    const PUBLICLINK_EDIT = 505;
    const PUBLICLINK_DELETE = 506;
    const PUBLICLINK_REFRESH = 507;
    const FILE = 601;
    const FILE_SEARCH = 602;
    const FILE_VIEW = 603;
    const FILE_UPLOAD = 604;
    const FILE_DOWNLOAD = 605;
    const FILE_DELETE = 606;
    const USER = 701;
    const USER_SEARCH = 702;
    const USER_VIEW = 703;
    const USER_CREATE = 704;
    const USER_EDIT = 705;
    const USER_DELETE = 706;
    const USER_EDIT_PASS = 707;
    const GROUP = 801;
    const GROUP_SEARCH = 802;
    const GROUP_VIEW = 803;
    const GROUP_CREATE = 804;
    const GROUP_EDIT = 805;
    const GROUP_DELETE = 806;
    const PROFILE = 901;
    const PROFILE_SEARCH = 902;
    const PROFILE_VIEW = 903;
    const PROFILE_CREATE = 904;
    const PROFILE_EDIT = 905;
    const PROFILE_DELETE = 906;
    const AUTHTOKEN = 1001;
    const AUTHTOKEN_SEARCH = 1002;
    const AUTHTOKEN_VIEW = 1003;
    const AUTHTOKEN_CREATE = 1004;
    const AUTHTOKEN_EDIT = 1005;
    const AUTHTOKEN_DELETE = 1006;
    const PLUGIN = 1101;
    const PLUGIN_SEARCH = 1102;
    const PLUGIN_VIEW = 1103;
    const PLUGIN_CREATE = 1104;
    const PLUGIN_ENABLE = 1105;
    const PLUGIN_DISABLE = 1106;
    const PLUGIN_RESET = 1107;
    const PLUGIN_DELETE = 1108;
    const WIKI = 1201;
    const WIKI_SEARCH = 1202;
    const WIKI_VIEW = 1203;
    const WIKI_CREATE = 1204;
    const WIKI_EDIT = 1205;
    const WIKI_DELETE = 1206;
    const ACCOUNTMGR = 1301;
    const ACCOUNTMGR_SEARCH = 1302;
    const ACCOUNTMGR_VIEW = 1303;
    const ACCOUNTMGR_DELETE = 1304;
    const ACCOUNTMGR_BULK_EDIT = 1305;
    const ACCOUNTMGR_HISTORY = 1311;
    const ACCOUNTMGR_HISTORY_SEARCH = 1312;
    const ACCOUNTMGR_HISTORY_VIEW = 1313;
    const ACCOUNTMGR_HISTORY_DELETE = 1314;
    const ACCOUNTMGR_HISTORY_RESTORE = 1315;
    const NOTIFICATION = 1401;
    const NOTIFICATION_SEARCH = 1402;
    const NOTIFICATION_VIEW = 1403;
    const NOTIFICATION_CREATE = 1404;
    const NOTIFICATION_EDIT = 1405;
    const NOTIFICATION_DELETE = 1406;
    const NOTIFICATION_CHECK = 1407;
    const CONFIG = 1501;
    const CONFIG_GENERAL = 1502;
    const CONFIG_ACCOUNT = 1510;
    const CONFIG_WIKI = 1520;
    const CONFIG_CRYPT = 1530;
    const CONFIG_CRYPT_REFRESH = 1531;
    const CONFIG_CRYPT_TEMPPASS = 1532;
    const CONFIG_BACKUP = 1540;
    const CONFIG_BACKUP_RUN = 1541;
    const CONFIG_IMPORT = 1550;
    const CONFIG_IMPORT_CSV = 1551;
    const CONFIG_IMPORT_XML = 1552;
    const CONFIG_EXPORT = 1560;
    const CONFIG_EXPORT_RUN = 1561;
    const CONFIG_MAIL = 1570;
    const CONFIG_LDAP = 1580;
    const CONFIG_LDAP_SYNC = 1581;
    const EVENTLOG = 1701;
    const EVENTLOG_SEARCH = 1702;
    const EVENTLOG_CLEAR = 1703;
    const ITEMPRESET = 1801;
    const ITEMPRESET_SEARCH = 1802;
    const ITEMPRESET_VIEW = 1803;
    const ITEMPRESET_CREATE = 1804;
    const ITEMPRESET_EDIT = 1805;
    const ITEMPRESET_DELETE = 1806;
    const TRACK = 1901;
    const TRACK_SEARCH = 1902;
    const TRACK_UNLOCK = 1903;
    const TRACK_CLEAR = 1904;
    const ITEMS_MANAGE = 5001;
    const ACCESS_MANAGE = 5002;
    const SECURITY_MANAGE = 5003;
    const USERSETTINGS = 5010;
    const USERSETTINGS_GENERAL = 5011;
}