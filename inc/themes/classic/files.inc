<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file['name'] ?>">
                    <?php echo \SP\Html\Html::truncate($file['name'], 35); ?> (<?php echo $file['size']; ?> KB)
                    <?php if ($file['thumb']): ?>
                        <img src="data:image/png;base64,<?php echo $file['thumb']; ?>"
                             title="<?php echo $file['name']; ?>" alt="thumbnail"
                             OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'view');" />
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <img src="imgs/delete.png" title="<?php echo _('Eliminar Archivo'); ?>" id="btnDelete"
                             class="inputImg" alt="Delete"
                             OnClick="sysPassUtil.Common.delFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo $accountId; ?>);"/>
                    <?php endif; ?>
                    <img src="imgs/download.png" title="<?php echo _('Descargar Archivo'); ?>" id="btnDownload"
                         class="inputImg" alt="download"
                         OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'download');"/>
                    <img src="imgs/view.png" title="<?php echo _('Ver Archivo'); ?>" id="btnView" class="inputImg"
                         alt="View"
                         OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'view');"/>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>