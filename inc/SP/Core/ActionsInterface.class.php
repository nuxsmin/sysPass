<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Core;

/**
 * Interface ActionsInterface para la definición de constantes de acciones disponibles.
 *
 * @package Controller
 */
interface ActionsInterface {
    const ACTION_ACC_SEARCH = 1;
    const ACTION_ACC_VIEW = 2;
    const ACTION_ACC_VIEW_PASS = 3;
    const ACTION_ACC_VIEW_HISTORY = 4;
    const ACTION_ACC_EDIT = 10;
    const ACTION_ACC_EDIT_PASS = 11;
    const ACTION_ACC_EDIT_RESTORE = 12;
    const ACTION_ACC_NEW = 20;
    const ACTION_ACC_COPY = 30;
    const ACTION_ACC_DELETE = 40;
    const ACTION_ACC_FILES = 50;
    const ACTION_ACC_REQUEST = 51;
    const ACTION_MGM = 60;
    const ACTION_MGM_CATEGORIES = 61;
    const ACTION_MGM_CATEGORIES_VIEW = 610;
    const ACTION_MGM_CATEGORIES_NEW = 611;
    const ACTION_MGM_CATEGORIES_EDIT = 612;
    const ACTION_MGM_CATEGORIES_DELETE = 613;
    const ACTION_MGM_CUSTOMERS = 62;
    const ACTION_MGM_CUSTOMERS_VIEW = 620;
    const ACTION_MGM_CUSTOMERS_NEW = 621;
    const ACTION_MGM_CUSTOMERS_EDIT = 622;
    const ACTION_MGM_CUSTOMERS_DELETE = 623;
    const ACTION_MGM_APITOKENS = 63;
    const ACTION_MGM_APITOKENS_NEW = 630;
    const ACTION_MGM_APITOKENS_VIEW = 631;
    const ACTION_MGM_APITOKENS_EDIT = 632;
    const ACTION_MGM_APITOKENS_DELETE = 633;
    const ACTION_MGM_CUSTOMFIELDS = 64;
    const ACTION_MGM_CUSTOMFIELDS_NEW = 640;
    const ACTION_MGM_CUSTOMFIELDS_VIEW = 641;
    const ACTION_MGM_CUSTOMFIELDS_EDIT = 642;
    const ACTION_MGM_CUSTOMFIELDS_DELETE = 643;
    const ACTION_MGM_PUBLICLINKS = 65;
    const ACTION_MGM_PUBLICLINKS_NEW = 650;
    const ACTION_MGM_PUBLICLINKS_VIEW = 651;
    const ACTION_MGM_PUBLICLINKS_DELETE = 653;
    const ACTION_MGM_PUBLICLINKS_REFRESH = 654;
    const ACTION_USR = 70;
    const ACTION_USR_USERS = 71;
    const ACTION_USR_USERS_VIEW= 710;
    const ACTION_USR_USERS_NEW = 711;
    const ACTION_USR_USERS_EDIT = 712;
    const ACTION_USR_USERS_DELETE = 713;
    const ACTION_USR_USERS_EDITPASS = 714;
    const ACTION_USR_GROUPS = 72;
    const ACTION_USR_GROUPS_VIEW = 720;
    const ACTION_USR_GROUPS_NEW = 721;
    const ACTION_USR_GROUPS_EDIT = 722;
    const ACTION_USR_GROUPS_DELETE = 723;
    const ACTION_USR_PROFILES = 73;
    const ACTION_USR_PROFILES_VIEW = 730;
    const ACTION_USR_PROFILES_NEW = 731;
    const ACTION_USR_PROFILES_EDIT = 732;
    const ACTION_USR_PROFILES_DELETE = 733;
    const ACTION_USR_PREFERENCES = 740;
    const ACTION_USR_PREFERENCES_GENERAL = 741;
    const ACTION_USR_PREFERENCES_SECURITY = 742;
    const ACTION_CFG = 80;
    const ACTION_CFG_GENERAL = 81;
    const ACTION_CFG_ENCRYPTION = 82;
    const ACTION_CFG_ENCRYPTION_TEMPPASS = 83;
    const ACTION_CFG_BACKUP = 84;
    const ACTION_CFG_IMPORT = 85;
    const ACTION_CFG_EXPORT = 86;
    const ACTION_CFG_WIKI = 87;
    const ACTION_CFG_LDAP = 88;
    const ACTION_CFG_MAIL = 89;
    const ACTION_EVL = 90;
}