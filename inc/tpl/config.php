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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

$action = $data['action'];
$activeTab = $data['active'];

SP_Users::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        
$arrLangAvailable = array('es_ES','en_US');
$isDemoMode = SP_Config::getValue('demoenabled',0);

$arrAccountCount = array(1,2,3,5,10,15,20,25,30,50,100);

$txtDisabled = ( $isDemoMode ) ? "DISABLED" : "";
$chkLog = ( SP_Config::getValue('logenabled') ) ? 'checked="checked"' : '';
$chkDebug = ( SP_Config::getValue('debug') ) ? 'checked="checked"' : '';
$chkMaintenance = ( SP_Config::getValue('maintenance') ) ? 'checked="checked"' : '';
$chkUpdates = ( SP_Config::getValue('checkupdates') ) ? 'checked="checked"' : '';
$chkAccountLink = ( SP_Config::getValue('account_link') ) ? 'checked="checked"' : '';
$chkFiles = ( SP_Config::getValue('filesenabled') ) ? 'checked="checked"' : '';
$chkWiki = ( SP_Config::getValue('wikienabled') ) ? 'checked="checked"' : '';
$chkLdap = ( SP_Config::getValue('ldapenabled') ) ? 'checked="checked"' : '';
$chkMail = ( SP_Config::getValue('mailenabled') ) ? 'checked="checked"' : '';

?>        
        
<div id="title" class="midroundup titleNormal">
    <? echo _('Sitio'); ?>
</div>

<form method="post" name="frmConfig" id="frmConfig">

<table id="tblSite" class="data tblConfig round">

    <tr>
        <td class="descField"><? echo _('Idioma'); ?></td>
        <td class="valField">
            <select name="sitelang" id="sel-sitelang" size="1">
            <? foreach ( $arrLangAvailable as $langOption ):
                $selected = ( SP_Config::getValue('sitelang') == $langOption ) ?  "SELECTED" : "";
            ?>
                <OPTION <? echo $selected; ?>><? echo $langOption; ?></OPTION>
            <? endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Timeout de sesión (s)'); ?>
        </td>
        <td class="valField">
            <input type="text" name="session_timeout" value="<? echo SP_Config::getValue('session_timeout'); ?>" maxlength="4" <? echo $txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Habilitar log de eventos'); ?>
            <? SP_Common::printHelpButton("config", 20); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="logenabled" class="checkbox" <? echo $chkLog.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Habilitar depuración'); ?>
            <? SP_Common::printHelpButton("config", 19); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="debug" class="checkbox" <? echo $chkDebug.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Modo mantenimiento'); ?>
            <? SP_Common::printHelpButton("config", 18); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="maintenance" class="checkbox" <? echo $chkMaintenance.' '.$txtDisabled; ?> />           
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Comprobar actualizaciones'); ?>
            <? SP_Common::printHelpButton("config", 21); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="updates" class="checkbox" <? echo $chkUpdates.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Nombre de cuenta como enlace'); ?>
            <? SP_Common::printHelpButton("config", 3); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="account_link" class="checkbox" <? echo $chkAccountLink; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Gestión de archivos'); ?>
            <? SP_Common::printHelpButton("config", 5); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="filesenabled" class="checkbox" <? echo $chkFiles.' '.$txtDisabled; ?> />
        </td>

    </tr>
    <tr>
        <td class="descField">
            <? echo _('Extensiones de archivos permitidas'); ?>
        </td>
        <td class="valField">
            <input type="text" name="add_ext" id="add_ext" maxlength="4" />
            <img src="imgs/add.png" title="<? echo _('Añadir extensión'); ?>" class="inputImg" id="btnAddExt" OnClick="addSelOption('allowed_exts','add_ext')" />
            <img src="imgs/delete.png" title="<? echo _('Eliminar extensión'); ?>" class="inputImg" id="btnDelExt" OnClick="delSelOption('allowed_exts')" />
            <br>
            <select id="allowed_exts" name="allowed_exts[]" multiple="multiple" size="4">
            <?
                if ( SP_Config::getValue('allowed_exts') ){
                    $allowed_exts = explode(",", SP_Config::getValue('allowed_exts'));
                    sort($allowed_exts, SORT_STRING);

                    foreach ( $allowed_exts as $extAllow ){
                        echo '<option value="'.$extAllow.'" selected>'.$extAllow.'</option>';
                    }
                }
            ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Tamaño máximo de archivo'); ?>
            <? SP_Common::printHelpButton("config", 6); ?>
        </td>
        <td class="valField">
            <input type="text" name="allowed_size" value="<? echo SP_Config::getValue('allowed_size'); ?>" maxlength="5" <? echo $txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Resultados por página'); ?>
            <? SP_Common::printHelpButton("config", 4); ?>
        </td>
        <td class="valField">
            <select name="account_count" id="sel-account_count" size="1">
        <? foreach ($arrAccountCount as $num ){
                if ( SP_Config::getValue('account_count') == $num){
                    echo "<OPTION SELECTED>$num</OPTION>";
                } else {
                    echo "<OPTION>$num</OPTION>";
                }
            }
        ?>
            </select>
        </td>
    </tr>
</table>

<!--WIKI-->
<div id="title" class="midroundup titleNormal">
    <? echo _('Wiki'); ?>
</div>

<table id="tblWiki" class="data tblConfig round">
    <tr>
        <td class="descField">
            <? echo _('Habilitar enlaces Wiki'); ?>
            <? SP_Common::printHelpButton("config", 7); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="wikienabled" class="checkbox" <? echo $chkWiki.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('URL de búsqueda Wiki'); ?>
            <? SP_Common::printHelpButton("config", 8); ?>
        </td>
        <td class="valField">
            <input type="text" name="wikisearchurl" class="txtLong" value="<? echo SP_Config::getValue('wikisearchurl'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('URL de página en Wiki'); ?>
            <? SP_Common::printHelpButton("config", 9); ?>
        </td>
        <td class="valField">
            <input type="text" name="wikipageurl" class="txtLong" value="<? echo SP_Config::getValue('wikipageurl'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Prefijo para nombre de cuenta'); ?>
            <? SP_Common::printHelpButton("config", 10); ?>
        </td>
        <td class="valField">
            <input type="text" name="add_wikifilter" id="add_wikifilter" maxlength="128" />
            <img src="imgs/add.png" title="<? echo _('Añadir filtro'); ?>" class="inputImg" id="btnAddWikifilter" OnClick="addSelOption('wikifilter','add_wikifilter')" />
            <img src="imgs/delete.png" title="<? echo _('Eliminar filtro'); ?>" class="inputImg" id="btnDelWikifilter" OnClick="delSelOption('wikifilter')" />
            <br>
            <select id="wikifilter" name="wikifilter[]" MULTIPLE="multiple" size="3">
            <? 
            if ( SP_Config::getValue('wikifilter') ){
                $wikifilter = explode("||", SP_Config::getValue('wikifilter'));
                sort($wikifilter, SORT_STRING);

                foreach ( $wikifilter as $filter ){
                    echo '<OPTION value="'.$filter.'">'.$filter.'</OPTION>';
                }
            }
            ?>
            </select>
        </td>
    </tr>
</table>

<!--LDAP-->

<div id="title" class="midroundup titleNormal">
    <? echo _('LDAP'); ?>
</div>

<table id="tblLdap" class="data tblConfig round">
<? if ( SP_Util::ldapIsAvailable() && ! $isDemoMode ): ?>
    <tr>
        <td class="descField">
            <? echo _('Habilitar LDAP'); ?>
            <? SP_Common::printHelpButton("config", 11); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="ldapenabled" class="checkbox" <? echo $chkLdap.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Servidor'); ?>
            <? SP_Common::printHelpButton("config", 15); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapserver" value="<? echo SP_Config::getValue('ldapserver'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Usuario de conexión'); ?>
            <? SP_Common::printHelpButton("config", 12); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapbinduser" value="<? echo SP_Config::getValue('ldapbinduser'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Clave de conexión'); ?>
            <? SP_Common::printHelpButton("config", 17); ?>
        </td>
        <td class="valField">
            <input type="password" name="ldapbindpass" value="<? echo SP_Config::getValue('ldapbindpass'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
    <td class="descField">
        <? echo _('Base de búsqueda'); ?>
        <? SP_Common::printHelpButton("config", 13); ?>
    </td>
        <td class="valField">
            <input type="text" name="ldapbase" class="txtLong" value="<? echo SP_Config::getValue('ldapbase'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Grupo'); ?>
            <? SP_Common::printHelpButton("config", 14); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapgroup" class="txtLong" value="<? echo SP_Config::getValue('ldapgroup'); ?>" maxlength="128" />
        </td>
    </tr>
<? else: ?>
    <tr>
        <td class="option-disabled">
            <? echo _('Módulo no disponible'); ?>
        </td>
    </tr>   
<? endif; ?>
</table>

<!--MAIL-->
<div id="title" class="midroundup titleNormal">
    <? echo _('Correo'); ?>
</div>

<table id="tblMail" class="data tblConfig round">
    <tr>
        <td class="descField">
            <? echo _('Habilitar notificaciones de correo'); ?>
        </td>
        <td class="valField">
            <input type="checkbox" name="mailenabled" class="checkbox" <? echo $chkMail.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Servidor'); ?>
        </td>
        <td class="valField">
            <input type="text" name="mailserver" size="20" value="<? echo SP_Config::getValue('mailserver'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Dirección de correo de envío'); ?>
        </td>
        <td class="valField">
            <input type="text" name="mailfrom" size="20" value="<? echo SP_Config::getValue('mailfrom'); ?>" maxlength="128" />
        </td>
    </tr>
</table> 

<? if ( $isDemoMode ): ?>
    <input type="hidden" name="logenabled" value="1" />
    <input type="hidden" name="filesenabled" value="1" />
    <input type="hidden" name="wikienabled" value="1" />
<? endif; ?>

<input type="hidden" name="active" value="<? echo $activeTab ?>" />
<input type="hidden" name="action" value="config" />
<input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
</form>

<div class="action">
    <ul>
        <li
            ><img src="imgs/check.png" title="<? echo _('Guardar'); ?>" class="inputImg" OnClick="configMgmt('saveconfig');" />
        </li>
    </ul>
</div>

<script>
    $("#sel-sitelang").chosen({disable_search : true});
    $("#sel-account_link").chosen({disable_search : true});
    $("#sel-account_count").chosen({disable_search : true});
</script>
