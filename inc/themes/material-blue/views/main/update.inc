<div>
    <?php if (!empty($noticesTitle)): ?>
        <a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank"
           class="mdl-badge active-tooltip" data-badge="<?php echo $numNotices; ?>"
           title="<?php echo $noticesTitle; ?>">
            <i class="material-icons mdl-color-text--amber-200">feedback</i>
        </a>
    <?php endif; ?>
</div>

<div>
    <?php if ($hasUpdates): ?>
        <a id="link-updates" class="active-tooltip" href="<?php echo $url; ?>" target="_blank"
           title="<?php echo $description; ?>">
            <?php echo $title; ?>
            &nbsp;&nbsp;
            <div id="help-hasupdates" class="icon material-icons mdl-color-text--indigo-200">cloud_download</div>
        </a>
    <?php elseif ($status === true): ?>
        <div class="icon material-icons mdl-color-text--teal-200" title="<?php echo _('Actualizado'); ?>">check_circle</div>
    <?php elseif ($status === false): ?>
        <div class="icon material-icons mdl-color-text--amber-200" title="<?php echo _('Error al comprobar actualizaciones'); ?>">
            warning
        </div>
    <?php endif; ?>
</div>
