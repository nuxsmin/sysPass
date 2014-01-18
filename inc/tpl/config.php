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

SP_ACL::checkUserAccess($action) || SP_Html::showCommonError('unavailable');
        
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
$chkMailRequests = ( SP_Config::getValue('mailrequestsenabled') ) ? 'checked="checked"' : '';
$allowedExts = SP_Config::getValue('allowed_exts');
?>        
        
<div id="title" class="midroundup titleNormal">
    <?php echo _('Sitio'); ?>
</div>

<form method="post" name="frmConfig" id="frmConfig">

<table id="tblSite" class="data tblConfig round">

    <tr>
        <td class="descField"><?php echo _('Idioma'); ?></td>
        <td class="valField">
            <select name="sitelang" id="sel-sitelang" size="1">
                <?php 
                foreach ( $arrLangAvailable as $langOption ){
                    $selected = ( SP_Config::getValue('sitelang') == $langOption ) ?  "SELECTED" : "";
                    echo "<option $selected>$langOption</option>";
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Timeout de sesión (s)'); ?>
        </td>
        <td class="valField">
            <input type="text" name="session_timeout" value="<?php echo SP_Config::getValue('session_timeout'); ?>" maxlength="4" <?php echo $txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar log de eventos'); ?>
            <?php SP_Common::printHelpButton("config", 20); ?>
        </td>
        <td class="valField">
            <label for="logenabled"><?php echo ($chkLog) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="logenabled" id="logenabled" class="checkbox" <?php echo $chkLog.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar depuración'); ?>
            <?php SP_Common::printHelpButton("config", 19); ?>
        </td>
        <td class="valField">
            <label for="debug"><?php echo ($chkDebug) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="debug" id="debug" class="checkbox" <?php echo $chkDebug.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Modo mantenimiento'); ?>
            <?php SP_Common::printHelpButton("config", 18); ?>
        </td>
        <td class="valField">
            <label for="maintenance"><?php echo ($chkMaintenance) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="maintenance" id="maintenance"  class="checkbox" <?php echo $chkMaintenance.' '.$txtDisabled; ?> />           
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Comprobar actualizaciones'); ?>
            <?php SP_Common::printHelpButton("config", 21); ?>
        </td>
        <td class="valField">
            <label for="updates"><?php echo ($chkUpdates) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="updates" id="updates" class="checkbox" <?php echo $chkUpdates.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Nombre de cuenta como enlace'); ?>
            <?php SP_Common::printHelpButton("config", 3); ?>
        </td>
        <td class="valField">
            <label for="account_link"><?php echo ($chkAccountLink) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="account_link" id="account_link" class="checkbox" <?php echo $chkAccountLink; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Gestión de archivos'); ?>
            <?php SP_Common::printHelpButton("config", 5); ?>
        </td>
        <td class="valField">
            <label for="filesenabled"><?php echo ($chkFiles) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="filesenabled" id="filesenabled" class="checkbox" <?php echo $chkFiles.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Extensiones de archivos permitidas'); ?>
            <?php SP_Common::printHelpButton("config", 22); ?>
        </td>
        <td class="valField">
            <input type="text" name="allowed_exts" id="allowed_exts" value="<?php echo $allowedExts; ?>"/>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Tamaño máximo de archivo'); ?>
            <?php SP_Common::printHelpButton("config", 6); ?>
        </td>
        <td class="valField">
            <input type="text" name="allowed_size" value="<?php echo SP_Config::getValue('allowed_size'); ?>" maxlength="5" <?php echo $txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Resultados por página'); ?>
            <?php SP_Common::printHelpButton("config", 4); ?>
        </td>
        <td class="valField">
            <select name="account_count" id="sel-account_count" size="1">
                <?php 
                foreach ($arrAccountCount as $num ){
                    $selected = ( SP_Config::getValue('account_count') == $num) ? 'SELECTED' : '';
                    echo "<option $selected>$num</option>";
                }
                ?>
            </select>
        </td>
    </tr>
</table>

<!--WIKI-->
<div id="title" class="midroundup titleNormal">
    <?php echo _('Wiki'); ?>
</div>

<table id="tblWiki" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar enlaces Wiki'); ?>
            <?php SP_Common::printHelpButton("config", 7); ?>
        </td>
        <td class="valField">
            <label for="wikienabled"><?php echo ($chkWiki) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="wikienabled" id="wikienabled" class="checkbox" <?php echo $chkWiki.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('URL de búsqueda Wiki'); ?>
            <?php SP_Common::printHelpButton("config", 8); ?>
        </td>
        <td class="valField">
            <input type="text" name="wikisearchurl" class="txtLong" value="<?php echo SP_Config::getValue('wikisearchurl'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('URL de página en Wiki'); ?>
            <?php SP_Common::printHelpButton("config", 9); ?>
        </td>
        <td class="valField">
            <input type="text" name="wikipageurl" class="txtLong" value="<?php echo SP_Config::getValue('wikipageurl'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Prefijo para nombre de cuenta'); ?>
            <?php SP_Common::printHelpButton("config", 10); ?>
        </td>
        <td class="valField">
            <input type="text" name="wikifilter" id="wikifilter" value="<?php echo SP_Config::getValue('wikifilter'); ?>" />
        </td>
    </tr>
</table>

<!--LDAP-->

<div id="title" class="midroundup titleNormal">
    <?php echo _('LDAP'); ?>
</div>

<table id="tblLdap" class="data tblConfig round">
<?php if ( SP_Util::ldapIsAvailable() && ! $isDemoMode ): ?>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar LDAP'); ?>
            <?php SP_Common::printHelpButton("config", 11); ?>
        </td>
        <td class="valField">
            <label for="ldapenabled"><?php echo ($chkLdap) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="ldapenabled" id="ldapenabled" class="checkbox" <?php echo $chkLdap.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Servidor'); ?>
            <?php SP_Common::printHelpButton("config", 15); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapserver" value="<?php echo SP_Config::getValue('ldapserver'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Usuario de conexión'); ?>
            <?php SP_Common::printHelpButton("config", 12); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapbinduser" value="<?php echo SP_Config::getValue('ldapbinduser'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Clave de conexión'); ?>
            <?php SP_Common::printHelpButton("config", 17); ?>
        </td>
        <td class="valField">
            <input type="password" name="ldapbindpass" value="<?php echo SP_Config::getValue('ldapbindpass'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
    <td class="descField">
        <?php echo _('Base de búsqueda'); ?>
        <?php SP_Common::printHelpButton("config", 13); ?>
    </td>
        <td class="valField">
            <input type="text" name="ldapbase" class="txtLong" value="<?php echo SP_Config::getValue('ldapbase'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Grupo'); ?>
            <?php SP_Common::printHelpButton("config", 14); ?>
        </td>
        <td class="valField">
            <input type="text" name="ldapgroup" class="txtLong" value="<?php echo SP_Config::getValue('ldapgroup'); ?>" maxlength="128" />
        </td>
    </tr>
<?php else: ?>
    <tr>
        <td class="option-disabled">
            <?php echo _('Módulo no disponible'); ?>
        </td>
    </tr>   
<?php endif; ?>
</table>

<!--MAIL-->
<div id="title" class="midroundup titleNormal">
    <?php echo _('Correo'); ?>
</div>

<table id="tblMail" class="data tblConfig round">
    <tr>
        <td class="descField">
            <?php echo _('Habilitar notificaciones de correo'); ?>
        </td>
        <td class="valField">
            <label for="mailenabled"><?php echo ($chkMail) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="mailenabled" id="mailenabled" class="checkbox" <?php echo $chkMail.' '.$txtDisabled; ?> />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Servidor'); ?>
        </td>
        <td class="valField">
            <input type="text" name="mailserver" size="20" value="<?php echo SP_Config::getValue('mailserver'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Dirección de correo de envío'); ?>
        </td>
        <td class="valField">
            <input type="text" name="mailfrom" size="20" value="<?php echo SP_Config::getValue('mailfrom'); ?>" maxlength="128" />
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo _('Habilitar peticiones por correo'); ?>
        </td>
        <td class="valField">
            <label for="mailrequestsenabled"><?php echo ($chkMailRequests) ? 'ON' : 'OFF'; ?></label>
            <input type="checkbox" name="mailrequestsenabled" id="mailrequestsenabled" class="checkbox" <?php echo $chkMailRequests.' '.$txtDisabled; ?> />
        </td>
    </tr>
</table> 

<?php if ( $isDemoMode ): ?>
    <input type="hidden" name="logenabled" value="1" />
    <input type="hidden" name="filesenabled" value="1" />
    <input type="hidden" name="wikienabled" value="1" />
<?php endif; ?>
    <input type="hidden" name="active" value="<?php echo $activeTab ?>" />
    <input type="hidden" name="action" value="config" />
    <input type="hidden" name="is_ajax" value="1" />
    <input type="hidden" name="sk" value="<?php echo SP_Common::getSessionKey(TRUE); ?>">
</form>

<div class="action">
    <ul>
        <li
            ><img src="imgs/check.png" title="<?php echo _('Guardar'); ?>" class="inputImg" OnClick="configMgmt('saveconfig');" />
        </li>
    </ul>
</div>

<script>
    $("#sel-sitelang").chosen({disable_search : true});
    $("#sel-account_link").chosen({disable_search : true});
    $("#sel-account_count").chosen({disable_search : true});
    $('#frmConfig .checkbox').button();
    $('#frmConfig .ui-button').click(function(){
        // El cambio de clase se produce durante el evento de click
        // Si tiene la clase significa que el estado anterior era ON y ahora es OFF
        if ( $(this).hasClass('ui-state-active') ){
            $(this).children().html('OFF');
        } else{
            $(this).children().html('ON');
        }
    });
    $('#allowed_exts').tagsInput({
        'width':'350px',
        'defaultText':'<?php echo _('Añadir extensión'); ?>',
        'defaultRemoveText':'<?php echo _('Eliminar extensión'); ?>',
        'removeWithBackspace' : false,
        'tagsToUpper' : true,
        'maxChars' : 4,
        'onAddTag' : function(){
            // Fix scrolling to bottom
            var $tagsbox = $(this).next();
            $tagsbox.animate({scrollTop: $tagsbox.height()});
            
            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[13] + '" />');
            }
        },
        'onRemoveTag' : function(){           
            var $tagsbox = $(this).next();
            
            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[13] + '"/>');
            }
        },
        onChange : function(){
            // Fix tooltip on refresh the tags list
            $(this + '[title]').powerTip(powertipOptions);
        }
    });
    $('#wikifilter').tagsInput({
        'width':'350px',
        'height':'50px',
        'defaultText':'<?php echo _('Añadir filtro'); ?>',
        'defaultRemoveText':'<?php echo _('Eliminar filtro'); ?>',
        'removeWithBackspace' : false,
        onAddTag : function(){
            // Fix scrolling to bottom
            var $tagsbox = $(this).next();
            $tagsbox.animate({scrollTop: $tagsbox.height()});
            
            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[13] + '"/>');
            }
        },
        onRemoveTag : function(){
            var $tagsbox = $(this).next();
            
            if ( $tagsbox.find('img:last').attr('alt') != 'warning' ){
                $tagsbox.find('div:last').prev().append('<img src="imgs/warning.png" alt="warning" class="iconMini" title="' + LANG[13] + '"/>');
            }
        },
        onChange : function(){
            var $tagsbox = $(this).next();
            last_width = $tagsbox.find("span:last").width() + 10;
            $tagsbox.find(".tag:last").css('width', last_width);
            
            // Fix tooltip on refresh the tags list
            $(this + '[title]').powerTip(powertipOptions);
        }
    });
</script>