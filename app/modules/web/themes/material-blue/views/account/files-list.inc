<?php
/**
 * @var \SP\Core\UI\ThemeIcons   $icons
 * @var \SP\DataModel\FileData[] $files
 */
?>

<div id="files-wrap">
    <ul id="files-list " class="mdl-list">
        <?php foreach ($files as $file): ?>
            <li class="mdl-list__item">
                <span class="mdl-list__item-primary-content" title="<?php echo $file->getName(); ?>">
                    <i class="material-icons  mdl-list__item-icon">attachment</i>
                    <span><?php printf('%s (%d KB)', \SP\Html\Html::truncate($file->getName(), 50), $file->getSize() / 1024); ?></span>
                </span>

                <span class="list-actions">
                    <?php if ($file->getThumb() !== 'no_thumb'): ?>
                        <span title="<?php echo $file->getName(); ?>"
                              class="btn-action"
                              data-item-id="<?php echo $file->getId(); ?>"
                              data-action-route="<?php echo $fileViewRoute; ?>"
                              data-onclick="file/view">
                            <img src="data:image/png;base64,<?php echo $file->getThumb(); ?>" alt="thumbnail"/>
                        </span>
                    <?php endif; ?>

                    <?php if ($deleteEnabled === 1): ?>
                        <i class="material-icons btn-action <?php echo $icons->getIconDelete()->getClass(); ?>"
                           title="<?php echo __('Eliminar Archivo'); ?>"
                           data-item-id="<?php echo $file->getId(); ?>"
                           data-action-route="<?php echo $fileDeleteRoute; ?>"
                           data-onclick="file/delete">
                            <?php echo $icons->getIconDelete()->getIcon(); ?></i>
                    <?php endif; ?>

                    <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconDownload()->getClass(); ?>"
                       title="<?php echo __('Descargar Archivo'); ?>"
                       data-item-id="<?php echo $file->getId(); ?>"
                       data-action-route="<?php echo $fileDownloadRoute; ?>"
                       data-onclick="file/download">
                        <?php echo $icons->getIconDownload()->getIcon(); ?></i>

                    <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconView()->getClass(); ?>"
                       title="<?php echo __('Ver Archivo'); ?>"
                       data-item-id="<?php echo $file->getId(); ?>"
                       data-action-route="<?php echo $fileViewRoute; ?>"
                       data-onclick="file/view">
                        <?php echo $icons->getIconView()->getIcon(); ?></i>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>