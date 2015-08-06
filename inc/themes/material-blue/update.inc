<div>
    <?php if (!empty($noticesTitle)): ?>
        <a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank"
           class="mdl-badge active-tooltip" data-badge="<?php echo $numNotices; ?>"
           title="<?php echo $noticesTitle; ?>">
            <i class="material-icons fg-orange40">feedback</i>
        </a>
    <?php endif; ?>
</div>

<div>
    <?php if ($hasUpdates): ?>
        <a id="link-updates" class="active-tooltip" href="<?php echo $url; ?>" target="_blank"
           title="<?php echo $description; ?>">
            <?php echo $title; ?>
            &nbsp;&nbsp;
            <div id="help-hasupdates" class="icon material-icons fg-blue40">cloud_download</div>
        </a>
    <?php elseif ($status === true): ?>
        <div class="icon material-icons fg-green40" title="<?php echo _('Actualizado'); ?>">check_circle</div>
    <?php elseif ($status === false): ?>
        <div class="icon material-icons fg-orange40" title="<?php echo _('Error al comprobar actualizaciones'); ?>">
            warning
        </div>
    <?php endif; ?>
</div>
