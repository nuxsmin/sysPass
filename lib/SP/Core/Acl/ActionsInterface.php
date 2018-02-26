<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
    const ACCOUNT_SEARCH = 1;
    const ACCOUNT = 10;
    const ACCOUNT_VIEW = 100;
    const ACCOUNT_CREATE = 101;
    const ACCOUNT_EDIT = 102;
    const ACCOUNT_DELETE = 103;
    const ACCOUNT_VIEW_PASS = 104;
    const ACCOUNT_VIEW_HISTORY = 105;
    const ACCOUNT_EDIT_PASS = 106;
    const ACCOUNT_EDIT_RESTORE = 107;
    const ACCOUNT_COPY = 108;
    const ACCOUNT_COPY_PASS = 109;
    const ACCOUNT_FILE = 11;
    const ACCOUNT_FILE_VIEW = 111;
    const ACCOUNT_FILE_UPLOAD = 112;
    const ACCOUNT_FILE_DOWNLOAD = 113;
    const ACCOUNT_FILE_DELETE = 114;
    const ACCOUNT_FILE_SEARCH = 115;
    const ACCOUNT_FILE_LIST = 116;
    const ACCOUNT_REQUEST = 12;
    const ACCOUNT_FAVORITE = 13;
    const ACCOUNT_FAVORITE_VIEW = 130;
    const ACCOUNT_FAVORITE_ADD = 131;
    const ACCOUNT_FAVORITE_DELETE = 133;
    const WIKI = 20;
    const WIKI_VIEW = 200;
    const WIKI_NEW = 201;
    const WIKI_EDIT = 202;
    const WIKI_DELETE = 203;
    const ITEMS_MANAGE = 60;
    const CATEGORY = 61;
    const CATEGORY_VIEW = 610;
    const CATEGORY_CREATE = 611;
    const CATEGORY_EDIT = 612;
    const CATEGORY_DELETE = 613;
    const CATEGORY_SEARCH = 615;
    const CLIENT = 62;
    const CLIENT_VIEW = 620;
    const CLIENT_CREATE = 621;
    const CLIENT_EDIT = 622;
    const CLIENT_DELETE = 623;
    const CLIENT_SEARCH = 625;
    const APITOKEN = 63;
    const APITOKEN_CREATE = 630;
    const APITOKEN_VIEW = 631;
    const APITOKEN_EDIT = 632;
    const APITOKEN_DELETE = 633;
    const APITOKEN_SEARCH = 635;
    const CUSTOMFIELD = 64;
    const CUSTOMFIELD_CREATE = 640;
    const CUSTOMFIELD_VIEW = 641;
    const CUSTOMFIELD_EDIT = 642;
    const CUSTOMFIELD_DELETE = 643;
    const CUSTOMFIELD_SEARCH = 645;
    const PUBLICLINK = 65;
    const PUBLICLINK_CREATE = 650;
    const PUBLICLINK_VIEW = 651;
    const PUBLICLINK_EDIT = 652;
    const PUBLICLINK_DELETE = 653;
    const PUBLICLINK_REFRESH = 654;
    const PUBLICLINK_SEARCH = 655;
    const FILE = 66;
    const FILE_VIEW = 661;
    const FILE_DOWNLOAD = 662;
    const FILE_DELETE = 663;
    const FILE_UPLOAD = 664;
    const FILE_SEARCH = 665;
    const ACCOUNTMGR = 67;
    const ACCOUNTMGR_HISTORY = 6701;
    const ACCOUNTMGR_VIEW = 671;
    const ACCOUNTMGR_DELETE = 673;
    const ACCOUNTMGR_DELETE_HISTORY = 6731;
    const ACCOUNTMGR_SEARCH = 675;
    const ACCOUNTMGR_SEARCH_HISTORY = 6751;
    const ACCOUNTMGR_RESTORE = 6771;
    const TAG = 68;
    const TAG_CREATE = 680;
    const TAG_VIEW = 681;
    const TAG_EDIT = 682;
    const TAG_DELETE = 683;
    const TAG_SEARCH = 685;
    const PLUGIN = 69;
    const PLUGIN_NEW = 690;
    const PLUGIN_VIEW = 691;
    const PLUGIN_SEARCH = 695;
    const PLUGIN_ENABLE = 696;
    const PLUGIN_DISABLE = 697;
    const PLUGIN_RESET = 698;
    const ACCESS_MANAGE = 70;
    const USER = 71;
    const USER_VIEW = 710;
    const USER_CREATE = 711;
    const USER_EDIT = 712;
    const USER_DELETE = 713;
    const USER_EDIT_PASS = 714;
    const USER_SEARCH = 715;
    const GROUP = 72;
    const GROUP_VIEW = 720;
    const GROUP_CREATE = 721;
    const GROUP_EDIT = 722;
    const GROUP_DELETE = 723;
    const GROUP_SEARCH = 725;
    const PROFILE = 73;
    const PROFILE_VIEW = 730;
    const PROFILE_CREATE = 731;
    const PROFILE_EDIT = 732;
    const PROFILE_DELETE = 733;
    const PROFILE_SEARCH = 735;
    const PREFERENCE = 740;
    const PREFERENCE_GENERAL = 741;
    const PREFERENCE_SECURITY = 742;
    const NOTIFICATION = 76;
    const NOTIFICATION_VIEW = 760;
    const NOTIFICATION_CREATE = 761;
    const NOTIFICATION_EDIT = 762;
    const NOTIFICATION_DELETE = 763;
    const NOTIFICATION_CHECK = 764;
    const NOTIFICATION_SEARCH = 765;
    const CONFIG = 1000;
    const CONFIG_GENERAL = 1001;
    const ACCOUNT_CONFIG = 1010;
    const WIKI_CONFIG = 1020;
    const ENCRYPTION_CONFIG = 1030;
    const ENCRYPTION_REFRESH = 1031;
    const ENCRYPTION_TEMPPASS = 1032;
    const BACKUP_CONFIG = 1040;
    const IMPORT_CONFIG = 1050;
    const EXPORT_CONFIG = 1060;
    const MAIL_CONFIG = 1070;
    const LDAP_CONFIG = 1080;
    const LDAP_SYNC = 1081;
    const EVENTLOG = 90;
    const EVENTLOG_SEARCH = 905;
    const EVENTLOG_CLEAR = 906;
}