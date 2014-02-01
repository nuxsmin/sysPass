<?php

/**
 * sysPass
 * 
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012 Rubén Domínguez nuxsmin@syspass.org
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
define('APP_ROOT', '..');
include_once (APP_ROOT . "/inc/init.php");

SP_Util::checkReferer('POST');

if (!SP_Init::isLoggedIn()) {
    SP_Util::logout();
}

if (SP_Common::parseParams('p', 'action', '', TRUE)) {
    $action = $tplvars['action'] = SP_Common::parseParams('p', 'action');
    $itemId = $tplvars['id'] = SP_Common::parseParams('p', 'id', 0);
    $tplvars['lastaction'] = SP_Common::parseParams('p', 'lastAction', 'accsearch');
} else {
    die('<div class="error">' . _('Parámetros incorrectos') . '</DIV>');
}

switch ($action) {
    case "accsearch":
        SP_Account::$accountSearchTxt = SP_Common::parseParams('s', 'accountSearchTxt');
        SP_Account::$accountSearchCustomer = SP_Common::parseParams('s', 'accountSearchCustomer');
        SP_Account::$accountSearchCategory = SP_Common::parseParams('s', 'accountSearchCategory', 0);
        SP_Account::$accountSearchOrder = SP_Common::parseParams('s', 'accountSearchOrder', 0);
        SP_Account::$accountSearchKey = SP_Common::parseParams('s', 'accountSearchKey', 0);

        SP_Html::getTemplate('search', $tplvars);
        break;
    case "accnew":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        SP_Users::checkUserUpdateMPass() || SP_Html::showCommonError('updatempass');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "acccopy":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        SP_Users::checkUserUpdateMPass() || SP_Html::showCommonError('updatempass');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "accedit":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        SP_Users::checkUserUpdateMPass() || SP_Html::showCommonError('updatempass');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "acceditpass":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        SP_Users::checkUserUpdateMPass() || SP_Html::showCommonError('updatempass');

        SP_Html::getTemplate('editpass', $tplvars);
        break;
    case "accview":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "accviewhistory":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "accdelete":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

        SP_Html::getTemplate('accounts', $tplvars);
        break;
    case "accrequest":
        SP_Html::getTemplate('request', $tplvars);
        break;
    case "usersmenu":
        echo '<DIV ID="tabs">';
        echo '<UL>';
        echo ( SP_ACL::checkUserAccess("users") ) ? '<LI><A HREF="#tabs-1" TITLE="' . _('Gestión de Usuarios') . '">' . _('Gestión de Usuarios') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("groups") ) ? '<LI><A HREF="#tabs-2" TITLE="' . _('Gestión de Grupos') . '">' . _('Gestión de Grupos') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("profiles") ) ? '<LI><A HREF="#tabs-3" TITLE="' . _('Gestión de Perfiles') . '">' . _('Gestión de Perfiles') . '</A></LI>' : '';
        echo '</UL>';

        $tplvars['active'] = 0;

        if (SP_ACL::checkUserAccess("users")) {
            $arrUsersTableProp = array(
                'itemName' => _('Usuario'),
                'tblId' => 'tblUsers',
                'header' => '',
                'tblHeaders' => array(
                    _('Nombre'),
                    _('Login'),
                    _('Perfil'),
                    _('Grupo'),
                    _('Propiedades')),
                'tblRowSrc' => array(
                    'user_name',
                    'user_login',
                    'userprofile_name',
                    'usergroup_name',
                    array(
                        'user_isAdminApp' => array('img_file' => 'check_blue.png', 'img_title' => _('Admin Aplicación')),
                        'user_isAdminAcc' => array('img_file' => 'check_orange.png', 'img_title' => _('Admin Cuentas')),
                        'user_isLdap' => array('img_file' => 'ldap.png', 'img_title' => _('Usuario de LDAP')),
                        'user_isDisabled' => array('img_file' => 'disabled.png', 'img_title' => _('Deshabilitado'))
                    )
                ),
                'tblRowSrcId' => 'user_id',
                'frmId' => 'frm_tblusers',
                'actionId' => 1,
                'newActionId' => 2,
                'active' => $tplvars['active'] ++,
                'actions' => array('view', 'edit', 'del', 'pass'));

            echo '<DIV ID="tabs-1">';
            $startTime = microtime();
            $users = SP_Users::getUsers();

            if ($users) {
                SP_Users::getUsrGrpTable($arrUsersTableProp, $users);
                SP_Html::printQueryInfoBar(count($users), $startTime);
            }
            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("groups")) {
            $arrGroupsTableProp = array(
                'itemName' => _('Grupo'),
                'tblId' => 'tblGroups',
                'header' => '',
                'tblHeaders' => array(_('Nombre'), _('Descripción')),
                'tblRowSrc' => array('usergroup_name', 'usergroup_description'),
                'tblRowSrcId' => 'usergroup_id',
                'frmId' => 'frm_tblgroups',
                'actionId' => 3,
                'newActionId' => 4,
                'active' => $tplvars['active'] ++,
                'actions' => array('edit', 'del'));

            echo '<DIV ID="tabs-2">';

            $startTime = microtime();
            $groups = SP_Groups::getGroups();

            if ($groups) {
                SP_Users::getUsrGrpTable($arrGroupsTableProp, $groups);
                SP_Html::printQueryInfoBar(count($groups), $startTime);
            }

            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("profiles")) {
            $arrProfilesTableProp = array(
                'itemName' => _('Perfil'),
                'tblId' => 'tblProfiles',
                'header' => '',
                'tblHeaders' => array(_('Nombre')),
                'tblRowSrc' => array('userprofile_name'),
                'tblRowSrcId' => 'userprofile_id',
                'frmId' => 'frm_tblprofiles',
                'actionId' => 5,
                'newActionId' => 6,
                'active' => $tplvars['active'] ++,
                'actions' => array('edit', 'del'));

            echo '<DIV ID="tabs-3">';

            $startTime = microtime();
            $profiles = SP_Profiles::getProfiles();

            if ($profiles) {
                SP_Users::getUsrGrpTable($arrProfilesTableProp, $profiles);
                SP_Html::printQueryInfoBar(count($profiles), $startTime);
            }

            echo '</DIV>';
        }

        echo '</DIV>';

        echo '<script>
            $("#tabs").tabs({
                active: ' . $itemId . ',
                create: function( event, ui ) {$("input:visible:first").focus();},
                activate: function( event, ui ) {
                    setContentSize();
                }
            });
            </script>';
        break;
    case "configmenu":
        echo '<DIV ID="tabs">';
        echo '<UL>';
        echo ( SP_ACL::checkUserAccess("config") ) ? '<LI><A HREF="#tabs-1" TITLE="' . _('Configuración') . '">' . _('Configuración') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("categories") ) ? '<LI><A HREF="#tabs-2" TITLE="' . _('Categorías') . '">' . _('Categorías') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("masterpass") ) ? '<LI><A HREF="#tabs-3" TITLE="' . _('Clave Maestra') . '">' . _('Clave Maestra') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("backup") ) ? '<LI><A HREF="#tabs-4" TITLE="' . _('Copia de Seguridad') . '">' . _('Copia de Seguridad') . '</A></LI>' : '';
        echo ( SP_ACL::checkUserAccess("config") ) ? '<LI><A HREF="#tabs-5" TITLE="' . _('Importar cuentas desde fuentes externas') . '">' . _('Importar Cuentas') . '</A></LI>' : '';
        echo '</UL>';

        $tplvars['active'] = 0;

        if (SP_ACL::checkUserAccess("config")) {
            $tplvars['active'] ++;

            echo '<DIV ID="tabs-1">';
            SP_Html::getTemplate('config', $tplvars);
            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("categories")) {
            $tplvars['active'] ++;

            echo '<DIV ID="tabs-2">';
            SP_Html::getTemplate('categories', $tplvars);
            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("masterpass")) {
            $tplvars['active'] ++;

            echo '<DIV ID="tabs-3">';
            SP_Html::getTemplate('masterpass', $tplvars);
            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("backup")) {
            $tplvars['active'] ++;

            echo '<DIV ID="tabs-4">';
            SP_Html::getTemplate('backup', $tplvars);
            echo '</DIV>';
        }

        if (SP_ACL::checkUserAccess("config")) {
            $tplvars['active'] ++;

            echo '<DIV ID="tabs-5">';
            SP_Html::getTemplate('migrate', $tplvars);
            echo '</DIV>';
        }

        echo '</DIV>';

        echo '<script>
            $("#tabs").tabs({
                active: ' . $itemId . ',
                create: function( event, ui ) {$("input:visible:first").focus();},
                activate: function( event, ui ) {
                    setContentSize();
                    $("input:visible:first").focus();
                }
            });
            </script>';
        break;
    case "eventlog":
        SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');

        SP_Html::getTemplate('eventlog', $tplvars);
        break;
}

if (isset($_SESSION["uisadminapp"]) && SP_Config::getValue('debug')) {
    $time_stop = SP_Init::microtime_float();
    $time = ($time_stop - $time_start);
    $memEnd = memory_get_usage();
    //$crypt = new SP_Crypt;

    $debugTxt[] = "<div ID= 'debuginfo' class='round'>";
    $debugTxt[] = "<h3>DEBUG INFO</h3>";
    $debugTxt[] = "<ul>";
    $debugTxt[] = "<li>RENDER -> " . $time . " sec</li>";
    $debugTxt[] = "<li>MEM -> Init: " . ($memInit / 1000) . " KB - End: " . ($memEnd / 1000) . " KB - Total: " . (($memEnd - $memInit) / 1000) . " KB</li>";
    $debugTxt[] = "<li>SESSION:";
    $debugTxt[] = "<pre>" . print_r($_SESSION, TRUE) . "</pre";
    $debugTxt[] = "</li>";
    $debugTxt[] = "<li>CONFIG:<pre>";
    $debugTxt[] = "<pre>" . print_r(SP_Config::getKeys(TRUE), TRUE) . "</pre>";
    $debugTxt[] = "</li>";
    //$debugTxt[] = '<li>'.$crypt->getSessionMasterPass().'</li>';
    $debugTxt[] = "</div>";

    foreach ($debugTxt as $out) {
        echo $out;
    }
}

// Se comprueba si hay actualizaciones.
// Es necesario que se haga al final de obtener el contenido ya que la 
// consulta ajax detiene al resto si se ejecuta antes
if ($_SESSION['uisadminapp'] && SP_Config::getValue('checkupdates') === 1 && !SP_Common::parseParams('s', 'UPDATED', FALSE, TRUE)) {
    echo '<script>checkUpds();</script>';
}