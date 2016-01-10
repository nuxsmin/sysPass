<div id="files-wrap" class="round">
    <ul id="files-list">
        <?php foreach ($files as $file): ?>
            <li class="files-item round">
                <div class="files-item-info" title="<?php echo $file['name'] ?>">
                    <?php echo \SP\Html\Html::truncate($file['name'], 35); ?> (<?php echo $file['size']; ?> KB)
                    <?php if ($file['thumb']): ?>
                        <img src="data:image/png;base64,<?php echo $file['thumb']; ?>"
                             title="<?php echo $file['name']; ?>" alt="thumbnail"
                             OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>);" />
                    <?php endif; ?>
                </div>
                <div class="files-item-actions">
                    <?php if ($deleteEnabled === 1): ?>
                        <img src="<?php echo $icons->getIconDelete()->getIcon(); ?>" title="<?php echo $icons->getIconDelete()->getTitle(); ?>" id="btnDelete"
                             class="inputImg" alt="Delete"
                             OnClick="sysPassUtil.Common.delFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo $accountId; ?>, <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DELETE; ?>);"/>
                    <?php endif; ?>
                    <img src="<?php echo $icons->getIconDownload()->getIcon(); ?>" title="<?php echo $icons->getIconDownload()->getTitle(); ?>" id="btnDownload"
                         class="inputImg" alt="download"
                         OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_DOWNLOAD; ?>, true);"/>
                    <img src="<?php echo $icons->getIconView()->getIcon(); ?>" title="<?php echo $icons->getIconView()->getTitle(); ?>" id="btnView" class="inputImg"
                         alt="View"
                         OnClick="sysPassUtil.Common.downFile(<?php echo $file['id']; ?>, '<?php echo $sk; ?>', <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_VIEW; ?>);"/>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>