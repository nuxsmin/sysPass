<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

define('APP_ROOT', '..');
require_once APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'init.php';

SP_Util::checkReferer('GET');

if (!SP_Init::isLoggedIn()) {
    return;
}

if (!SP_Util::fileIsEnabled()) {
    echo _('Gestión de archivos deshabilitada');
    return false;
}

$sk = SP_Common::parseParams('g', 'sk', false);

if (!$sk || !SP_Common::checkSessionKey($sk)) {
    SP_Common::printXML(_('CONSULTA INVÁLIDA'));
}

$accountId = SP_Common::parseParams('g', 'id', 0);
$deleteEnabled = SP_Common::parseParams('g', 'del', 0);

$files = SP_Files::getFileList($accountId, $deleteEnabled);

if (!is_array($files) || count($files) === 0) {
    return;
}
?>

<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <span title="<?php echo $file['name'] ?>"> <?php echo SP_Html::truncate($file['name'], 25); ?>
                    (<?php echo $file['size']; ?> KB)</span>
                <?php if ($deleteEnabled === 1): ?>
                    <img src="imgs/delete.png" title="<?php echo _('Eliminar Archivo'); ?>" id="btnDelete"
                         class="inputImg" alt="Delete"
                         OnClick="delFile(<?php echo $file['id']; ?>, '<?php echo SP_Common::getSessionKey(); ?>', <?php echo $accountId; ?>);"/>
                <?php endif; ?>
                <img src="imgs/download.png" title="<?php echo _('Descargar Archivo'); ?>" id="btnDownload"
                     class="inputImg" alt="download"
                     OnClick="downFile(<?php echo $file['id']; ?>, '<?php echo SP_Common::getSessionKey(); ?>', 'download');"/>
                <img src="imgs/view.png" title="<?php echo _('Ver Archivo'); ?>" id="btnView" class="inputImg"
                     alt="View"
                     OnClick="downFile(<?php echo $file['id']; ?>, '<?php echo SP_Common::getSessionKey(); ?>', 'view');"/>
            </li>
        <?php endforeach; ?>
    </ul>
</div>