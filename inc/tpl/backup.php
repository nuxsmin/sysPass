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
 * MERCHANTABILITY or FITNESS FOR a PARTICULAR PURPOSE.  See the
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

$siteName = SP_Html::getAppInfo('appname');
$backupDir = SP_Init::$SERVERROOT.'/backup';
$backupPath = SP_Init::$WEBROOT.'/backup';

$backupFile = array('absolute' => $backupDir.'/'.$siteName.'.tgz', 'relative' => $backupPath.'/'.$siteName.'.tgz' );
$backupDbFile =  array('absolute' => $backupDir.'/'.$siteName.'_db.sql', 'relative' => $backupPath.'/'.$siteName.'_db.sql' );

$lastBackupTime = ( file_exists($backupFile['absolute']) ) ? _('Último backup').": ".date("F d Y H:i:s.", filemtime($backupFile['absolute'])) : _('No se encontraron backups');
?>

<table class="data round">
    <tr>
        <td class="descField">
            <? echo _('Resultado'); ?>
        </td>
        <td class="valField">
            <? echo $lastBackupTime; ?>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <? echo _('Descargar Actual'); ?>
        </td>
        <td class="valField">
        <? if ( file_exists($backupFile['absolute']) && file_exists($backupDbFile['absolute']) ): ?>
            <a href="<? echo $backupDbFile['relative']; ?>">Backup BBDD</a>
            -
            <a href="<? echo $backupFile['relative']; ?>">Backup <? echo $siteName; ?></a>
        <? 
            else:
                echo _('No hay backups para descargar'); 
            endif;
        ?>
    </td>
    </tr>
</table>

<form method="post" name="frmBackup" id="frmBackup">
	<input type="hidden" name="active" value="<? echo $activeTab ?>" />
	<input type="hidden" name="action" value="backup" />
	<input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
</form>

<div class="action">
    <ul>
        <li>
            <img src="imgs/backup.png" title="<? echo _('Realizar Backup'); ?>" class="inputImg" OnClick="configMgmt('backup');" />
        </li>
    </ul>
</div>