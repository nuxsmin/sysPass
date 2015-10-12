<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file['name'] ?>">
                    <?php echo \SP\Html\Html::truncate($file['name'], 35); ?> (<?php echo $file['size']; ?> KB)
                    <?php if ($file['thumb']): ?>
                        <img src="data:image/png;base64,<?php echo $file['thumb']; ?>"
                             title="<?php echo $file['name']; ?>" alt="thumbnail"
                             OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'view');"/>
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <i class="material-icons fg-red80"
                           title="<?php echo _('Eliminar Archivo'); ?>"
                           OnClick="sysPassUtil.Common.delFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo $accountId; ?>);">delete</i>
                    <?php endif; ?>
                    <i class="material-icons fg-blue80" title="<?php echo _('Descargar Archivo'); ?>"
                       OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'download');">file_download</i>
                    <i class="material-icons fg-blue80" title="<?php echo _('Ver Archivo'); ?>"
                       OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', 'view');">visibility</i>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>