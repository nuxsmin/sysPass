<?php
/**
 * @var ThemeIcons $icons
 * @var callable   $_getvar
 * @var ConfigData $configData
 * @var FileData   $file
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\FileData;
use SP\Html\Html;

?>

<div id="files-wrap">
    <ul id="files-list " class="mdl-list">
        <?php foreach ($_getvar('files') as $file):
            $extension = mb_strtoupper($file->getExtension());
            ?>
            <li class="mdl-list__item">
                <span class="mdl-list__item-primary-content"
                      title="<?php echo $file->getName(); ?>">
                    <i class="material-icons  mdl-list__item-icon">attachment</i>
                    <span><?php printf('%s (%d KB)', Html::truncate($file->getName(), 50), $file->getSize() / 1024); ?></span>
                </span>

                <span class="list-actions">
                    <?php if ($file->getThumb() !== 'no_thumb'): ?>
                        <span title="<?php echo $file->getName(); ?>"
                              class="btn-action"
                              data-item-id="<?php echo $file->getId(); ?>"
                              data-action-route="<?php echo $_getvar('fileViewRoute'); ?>"
                              data-onclick="file/view">
                            <img src="data:image/png;base64,<?php echo $file->getThumb(); ?>"
                                 alt="thumbnail"/>
                        </span>
                    <?php endif; ?>

                    <?php if ($_getvar('deleteEnabled') === 1): ?>
                        <i class="material-icons btn-action <?php echo $icons->getIconDelete()->getClass(); ?>"
                           title="<?php echo __('Delete File'); ?>"
                           data-item-id="<?php echo $file->getId(); ?>"
                           data-action-route="<?php echo $_getvar('fileDeleteRoute'); ?>"
                           data-onclick="file/delete">
                            <?php echo $icons->getIconDelete()->getIcon(); ?></i>
                    <?php endif; ?>

                    <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconDownload()->getClass(); ?>"
                       title="<?php echo __('Download File'); ?>"
                       data-item-id="<?php echo $file->getId(); ?>"
                       data-action-route="<?php echo $_getvar('fileDownloadRoute'); ?>"
                       data-item-type="<?php echo $extension; ?>"
                       data-onclick="file/download">
                        <?php echo $icons->getIconDownload()->getIcon(); ?></i>

                    <?php if ($extension !== 'PDF'): ?>
                        <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconView()->getClass(); ?>"
                           title="<?php echo __('View File'); ?>"
                           data-item-id="<?php echo $file->getId(); ?>"
                           data-action-route="<?php echo $_getvar('fileViewRoute'); ?>"
                           data-onclick="file/view">
                        <?php echo $icons->getIconView()->getIcon(); ?></i>
                    <?php endif; ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>