<?php
/** @var $icons \Theme\Icons */
/** @var $file \SP\DataModel\FileData */
/** @var $AccountAcl \SP\Account\AccountAcl */
?>

<div id="files-wrap" class="list-wrap round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file->getAccfileName(); ?>">
                    <?php echo \SP\Html\Html::truncate($file->getAccfileName(), 35); ?>
                    <?php printf('(%d KB)', $file->getAccfileSize() / 1024); ?>
                    <?php if ($file->getAccfileThumb() !== 'no_thumb'): ?>
                        <img src="data:image/png;base64,<?php echo $file->getAccfileThumb(); ?>"
                             title="<?php echo $file->getAccfileName(); ?>" alt="thumbnail"
                             class="btn-action"
                             data-item-id="<?php echo $file->getAccfileId(); ?>"
                             data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>"
                             data-onclick="file/view"/>
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <i class="btn-action material-icons <?php echo $icons->getIconDelete()->getClass(); ?>"
                           title="<?php echo __('Eliminar Archivo'); ?>"
                           data-item-id="<?php echo $file->getAccfileId(); ?>"
                           data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DELETE; ?>"
                           data-onclick="file/delete">
                            <?php echo $icons->getIconDelete()->getIcon(); ?></i>
                    <?php endif; ?>

                    <i class="btn-action material-icons <?php echo $icons->getIconDownload()->getClass(); ?>"
                       title="<?php echo __('Descargar Archivo'); ?>"
                       data-item-id="<?php echo $file->getAccfileId(); ?>"
                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DOWNLOAD; ?>"
                       data-onclick="file/download">
                        <?php echo $icons->getIconDownload()->getIcon(); ?></i>
                    <i class="btn-action material-icons <?php echo $icons->getIconView()->getClass(); ?>"
                       title="<?php echo __('Ver Archivo'); ?>"
                       data-item-id="<?php echo $file->getAccfileId(); ?>"
                       data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>"
                       data-onclick="file/view">
                        <?php echo $icons->getIconView()->getIcon(); ?></i>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>