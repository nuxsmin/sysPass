<?php
/** @var $icons \SP\Core\UI\ThemeIcons */
/** @var $files \SP\DataModel\FileData[] */
?>

<div id="files-wrap" class="list-wrap round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file->getName(); ?>">
                    <?php echo \SP\Html\Html::truncate($file->getName(), 35); ?>
                    <?php printf('(%d KB)', $file->getSize() / 1024); ?>
                    <?php if ($file->getThumb() !== 'no_thumb'): ?>
                        <img src="data:image/png;base64,<?php echo $file->getThumb(); ?>"
                             title="<?php echo $file->getName(); ?>" alt="thumbnail"
                             class="btn-action"
                             data-item-id="<?php echo $file->getId(); ?>"
                             data-action-route="<?php echo $fileViewRoute; ?>"
                             data-onclick="file/view"/>
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <i class="btn-action material-icons <?php echo $icons->getIconDelete()->getClass(); ?>"
                           title="<?php echo __('Eliminar Archivo'); ?>"
                           data-item-id="<?php echo $file->getId(); ?>"
                           data-action-route="<?php echo $fileDeleteRoute; ?>"
                           data-onclick="file/delete">
                            <?php echo $icons->getIconDelete()->getIcon(); ?></i>
                    <?php endif; ?>

                    <i class="btn-action material-icons <?php echo $icons->getIconDownload()->getClass(); ?>"
                       title="<?php echo __('Descargar Archivo'); ?>"
                       data-item-id="<?php echo $file->getId(); ?>"
                       data-action-route="<?php echo $fileDownloadRoute; ?>"
                       data-onclick="file/download">
                        <?php echo $icons->getIconDownload()->getIcon(); ?></i>
                    <i class="btn-action material-icons <?php echo $icons->getIconView()->getClass(); ?>"
                       title="<?php echo __('Ver Archivo'); ?>"
                       data-item-id="<?php echo $file->getId(); ?>"
                       data-action-route="<?php echo $fileViewRoute; ?>"
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