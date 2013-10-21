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
include_once (APP_ROOT."/inc/init.php");

SP_Util::checkReferer('GET');

if ( ! SP_Init::isLoggedIn() ) {
    return;
}

SP_Users::checkUserAccess("accfiles") || die ('<DIV CLASS="error">'._('No tiene permisos para acceder a esta página').'</DIV');

if ( SP_Config::getValue('filesenabled') == 0 ){
    echo _('Gestión de archivos deshabilitada');
    return FALSE;              
}

$accountId = ( isset($_GET['id']) ) ? (int)$_GET['id'] : 0 ;
$deleteEnabled = ( isset($_GET['del']) ) ? (int)$_GET['del'] : 0 ;

$files = SP_Files::getFileList($accountId, $deleteEnabled);

if ( ! is_array($files) || count($files) === 0 ){
	return;
}
?>

<form action="ajax/ajax_files.php" method="post" name="files_form" id="files_form">
    <select name="fileId" size="4" class="files" id="files">
    <? foreach ($files as $file): ?>
    <option value='<? echo $file['id']; ?>'><? echo $file['name'] ?> (<? echo $file['size']; ?> KB)</option>
    <? endforeach;?>
    </select>
	<input type="hidden" name="sk" value="<? echo SP_Common::getSessionKey(TRUE); ?>">
    <input name="action" type="hidden" id="action" value="download">
</form>
<div class="actionFiles">
    <img src="imgs/download.png" title="<? echo _('Descargar archivo'); ?>" id="btnDownload" class="inputImg" alt="download" OnClick="downFile();" />
    <img src="imgs/view.png" title="<? echo _('Ver archivo'); ?>" id="btnView" class="inputImg" alt="View" OnClick="downFile(1);" />
<? if ( $deleteEnabled === 1 ): ?>
    <img src="imgs/delete.png" title="<? echo _('Eliminar archivo'); ?>" id="btnDelete" class="inputImg" alt="Delete" OnClick="delFile(<? echo $accountId; ?>);" />
<? endif; ?>
</div>