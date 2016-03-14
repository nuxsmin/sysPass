<?php
/** @var $icons \Theme\Icons */
/** @var $file \SP\DataModel\FileData */
?>

<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file->getAccfileName(); ?>">
                    <?php echo \SP\Html\Html::truncate($file->getAccfileName(), 35); ?> (<?php echo $file->getAccfileSize(); ?>KB)
                    <?php if ($file->getAccfileThumb() !== 'no_thumb'): ?>
                        <img src="data:image/png;base64,<?php echo $file->getAccfileThumb(); ?>"
                             title="<?php echo $file->getAccfileName(); ?>" alt="thumbnail"
                             OnClick="sysPassUtil.Common.downFile(<?php echo $file->getAccfileId(); ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>);"/>
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <i class="material-icons <?php echo $icons->getIconDelete()->getClass(); ?>"
                           title="<?php echo _('Eliminar Archivo'); ?>"
                           OnClick="sysPassUtil.Common.delFile(<?php echo $file->getAccfileId(); ?>, '<?php echo $sk; ?>', <?php echo $accountId; ?>, <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DELETE; ?>);"><?php echo $icons->getIconDelete()->getIcon(); ?></i>
                    <?php endif; ?>
                    <i class="material-icons <?php echo $icons->getIconDownload()->getClass(); ?>"
                       title="<?php echo _('Descargar Archivo'); ?>"
                       OnClick="sysPassUtil.Common.downFile(<?php echo $file->getAccfileId(); ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DOWNLOAD; ?>, true);"><?php echo $icons->getIconDownload()->getIcon(); ?></i>
                    <i class="material-icons <?php echo $icons->getIconView()->getClass(); ?>"
                       title="<?php echo _('Ver Archivo'); ?>"
                       OnClick="sysPassUtil.Common.downFile(<?php echo $file->getAccfileId(); ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>);"><?php echo $icons->getIconView()->getIcon(); ?></i>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>