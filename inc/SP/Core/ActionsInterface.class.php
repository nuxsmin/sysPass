<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core;

/**
 * Interface ActionsInterface para la definición de constantes de acciones disponibles.
 *
 * @package Controller
 */
interface ActionsInterface
{
    const ACTION_ACC_SEARCH = 1;
    const ACTION_ACC = 10;
    const ACTION_ACC_VIEW = 100;
    const ACTION_ACC_NEW = 101;
    const ACTION_ACC_EDIT = 102;
    const ACTION_ACC_DELETE = 103;
    const ACTION_ACC_VIEW_PASS = 104;
    const ACTION_ACC_VIEW_HISTORY = 105;
    const ACTION_ACC_EDIT_PASS = 106;
    const ACTION_ACC_EDIT_RESTORE = 107;
    const ACTION_ACC_COPY = 108;
    const ACTION_ACC_FILES = 11;
    const ACTION_ACC_FILES_VIEW = 111;
    const ACTION_ACC_FILES_UPLOAD = 112;
    const ACTION_ACC_FILES_DOWNLOAD = 113;
    const ACTION_ACC_FILES_DELETE = 114;
    const ACTION_ACC_REQUEST = 12;
    const ACTION_ACC_FAVORITES = 13;
    const ACTION_ACC_FAVORITES_VIEW = 130;
    const ACTION_ACC_FAVORITES_ADD = 131;
    const ACTION_ACC_FAVORITES_DELETE = 133;
    const ACTION_WIKI = 20;
    const ACTION_WIKI_VIEW = 200;
    const ACTION_WIKI_NEW = 201;
    const ACTION_WIKI_EDIT = 202;
    const ACTION_WIKI_DELETE = 203;
    const ACTION_MGM = 60;
    const ACTION_MGM_CATEGORIES = 61;
    const ACTION_MGM_CATEGORIES_VIEW = 610;
    const ACTION_MGM_CATEGORIES_NEW = 611;
    const ACTION_MGM_CATEGORIES_EDIT = 612;
    const ACTION_MGM_CATEGORIES_DELETE = 613;
    const ACTION_MGM_CATEGORIES_SEARCH = 615;
    const ACTION_MGM_CUSTOMERS = 62;
    const ACTION_MGM_CUSTOMERS_VIEW = 620;
    const ACTION_MGM_CUSTOMERS_NEW = 621;
    const ACTION_MGM_CUSTOMERS_EDIT = 622;
    const ACTION_MGM_CUSTOMERS_DELETE = 623;
    const ACTION_MGM_CUSTOMERS_SEARCH = 625;
    const ACTION_MGM_APITOKENS = 63;
    const ACTION_MGM_APITOKENS_NEW = 630;
    const ACTION_MGM_APITOKENS_VIEW = 631;
    const ACTION_MGM_APITOKENS_EDIT = 632;
    const ACTION_MGM_APITOKENS_DELETE = 633;
    const ACTION_MGM_APITOKENS_SEARCH = 635;
    const ACTION_MGM_CUSTOMFIELDS = 64;
    const ACTION_MGM_CUSTOMFIELDS_NEW = 640;
    const ACTION_MGM_CUSTOMFIELDS_VIEW = 641;
    const ACTION_MGM_CUSTOMFIELDS_EDIT = 642;
    const ACTION_MGM_CUSTOMFIELDS_DELETE = 643;
    const ACTION_MGM_CUSTOMFIELDS_SEARCH = 645;
    const ACTION_MGM_PUBLICLINKS = 65;
    const ACTION_MGM_PUBLICLINKS_NEW = 650;
    const ACTION_MGM_PUBLICLINKS_VIEW = 651;
    const ACTION_MGM_PUBLICLINKS_DELETE = 653;
    const ACTION_MGM_PUBLICLINKS_REFRESH = 654;
    const ACTION_MGM_PUBLICLINKS_SEARCH = 655;
    const ACTION_MGM_FILES = 66;
    const ACTION_MGM_FILES_VIEW = 661;
    const ACTION_MGM_FILES_DELETE = 663;
    const ACTION_MGM_FILES_SEARCH = 665;
    const ACTION_MGM_ACCOUNTS = 67;
    const ACTION_MGM_ACCOUNTS_HISTORY = 6701;
    const ACTION_MGM_ACCOUNTS_VIEW = 671;
    const ACTION_MGM_ACCOUNTS_DELETE = 673;
    const ACTION_MGM_ACCOUNTS_DELETE_HISTORY = 6731;
    const ACTION_MGM_ACCOUNTS_SEARCH = 675;
    const ACTION_MGM_ACCOUNTS_SEARCH_HISTORY = 6751;
    const ACTION_MGM_ACCOUNTS_EDIT_RESTORE = 6771;
    const ACTION_MGM_TAGS = 68;
    const ACTION_MGM_TAGS_NEW = 680;
    const ACTION_MGM_TAGS_VIEW = 681;
    const ACTION_MGM_TAGS_EDIT = 682;
    const ACTION_MGM_TAGS_DELETE = 683;
    const ACTION_MGM_TAGS_SEARCH = 685;
    const ACTION_MGM_PLUGINS = 69;
    const ACTION_MGM_PLUGINS_NEW = 690;
    const ACTION_MGM_PLUGINS_VIEW = 691;
    const ACTION_MGM_PLUGINS_SEARCH = 695;
    const ACTION_MGM_PLUGINS_ENABLE= 696;
    const ACTION_MGM_PLUGINS_DISABLE= 697;
    const ACTION_MGM_PLUGINS_RESET= 698;
    const ACTION_USR = 70;
    const ACTION_USR_USERS = 71;
    const ACTION_USR_USERS_VIEW = 710;
    const ACTION_USR_USERS_NEW = 711;
    const ACTION_USR_USERS_EDIT = 712;
    const ACTION_USR_USERS_DELETE = 713;
    const ACTION_USR_USERS_EDITPASS = 714;
    const ACTION_USR_USERS_SEARCH = 715;
    const ACTION_USR_GROUPS = 72;
    const ACTION_USR_GROUPS_VIEW = 720;
    const ACTION_USR_GROUPS_NEW = 721;
    const ACTION_USR_GROUPS_EDIT = 722;
    const ACTION_USR_GROUPS_DELETE = 723;
    const ACTION_USR_GROUPS_SEARCH = 725;
    const ACTION_USR_PROFILES = 73;
    const ACTION_USR_PROFILES_VIEW = 730;
    const ACTION_USR_PROFILES_NEW = 731;
    const ACTION_USR_PROFILES_EDIT = 732;
    const ACTION_USR_PROFILES_DELETE = 733;
    const ACTION_USR_PROFILES_SEARCH = 735;
    const ACTION_USR_PREFERENCES = 740;
    const ACTION_USR_PREFERENCES_GENERAL = 741;
    const ACTION_USR_PREFERENCES_SECURITY = 742;
    const ACTION_USR_SYNC_LDAP = 751;
    const ACTION_NOT = 760;
    const ACTION_NOT_USER = 761;
    const ACTION_NOT_USER_VIEW = 7610;
    const ACTION_NOT_USER_NEW = 7611;
    const ACTION_NOT_USER_EDIT = 7612;
    const ACTION_NOT_USER_DELETE = 7613;
    const ACTION_NOT_USER_CHECK = 7614;
    const ACTION_NOT_USER_SEARCH = 7615;
    const ACTION_CFG = 80;
    const ACTION_CFG_GENERAL = 801;
    const ACTION_CFG_ENCRYPTION = 802;
    const ACTION_CFG_ENCRYPTION_REFRESH = 8021;
    const ACTION_CFG_ENCRYPTION_TEMPPASS = 803;
    const ACTION_CFG_BACKUP = 804;
    const ACTION_CFG_IMPORT = 805;
    const ACTION_CFG_EXPORT = 806;
    const ACTION_CFG_WIKI = 807;
    const ACTION_CFG_LDAP = 808;
    const ACTION_CFG_MAIL = 809;
    const ACTION_CFG_ACCOUNTS = 810;
    const ACTION_EVL = 90;
}