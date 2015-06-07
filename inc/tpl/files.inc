<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <span title="<?php echo $file['name'] ?>"> <?php echo SP_Html::truncate($file['name'], 25); ?>
                    (<?php echo $file['size']; ?> KB)</span>
                <?php if ($deleteEnabled === 1): ?>
                    <img src="imgs/delete.png" title="<?php echo _('Eliminar Archivo'); ?>" id="btnDelete"
                         class="inputImg" alt="Delete"
                         OnClick="delFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo $accountId; ?>);"/>
                <?php endif; ?>
                <img src="imgs/download.png" title="<?php echo _('Descargar Archivo'); ?>" id="btnDownload"
                     class="inputImg" alt="download"
                     OnClick="downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'download');"/>
                <img src="imgs/view.png" title="<?php echo _('Ver Archivo'); ?>" id="btnView" class="inputImg"
                     alt="View"
                     OnClick="downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'view');"/>
            </li>
        <?php endforeach; ?>
    </ul>
</div>