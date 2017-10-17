<?php if (isset($numNotices) && $numNotices > 0): ?>
    <a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank">
        <div id="notices-info" class="material-icons mdl-badge mdl-badge--overlap mdl-color-text--amber-200"
             data-badge="<?php echo $numNotices; ?>">feedback
        </div>
    </a>
    <span for="notices-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo $noticesTitle; ?></span>
<?php endif; ?>

<?php if ($hasUpdates): ?>
    <a id="link-updates" href="<?php echo $url; ?>" target="_blank">
        <?php echo $title; ?>
        &nbsp;&nbsp;
        <div id="help-hasupdates" class="icon material-icons mdl-color-text--indigo-200">cloud_download</div>
    </a>
    <span for="link-updates" class="mdl-tooltip mdl-tooltip--top"><?php echo $description; ?></span>
<?php elseif ($updateStatus === true): ?>
    <div id="updates-info" class="icon material-icons mdl-color-text--teal-200">check_circle</div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo __('Actualizado'); ?></span>
<?php elseif ($updateStatus === false): ?>
    <div id="updates-info" class="icon material-icons mdl-color-text--amber-200">
        warning
    </div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo __('Error al comprobar actualizaciones'); ?></span>
<?php endif; ?>