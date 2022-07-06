<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>

<?php if ($_getvar('numNotices', 0) > 0): ?>
    <a href="https://github.com/nuxsmin/sysPass/labels/Notices" target="_blank">
        <div id="notices-info"
             class="material-icons mdl-badge mdl-badge--overlap mdl-color-text--amber-200"
             data-badge="<?php echo $_getvar('numNotices'); ?>">feedback
        </div>
    </a>
    <span for="notices-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo $_getvar('noticesTitle'); ?></span>
<?php endif; ?>

<?php if ($_getvar('hasUpdates')): ?>
    <a id="link-updates" href="<?php echo $_getvar('url'); ?>" target="_blank">
        <?php echo $_getvar('title'); ?>
        &nbsp;&nbsp;
        <div id="help-hasupdates"
             class="icon material-icons mdl-color-text--indigo-200">
            cloud_download
        </div>
    </a>
    <span for="link-updates"
          class="mdl-tooltip mdl-tooltip--top"><?php echo $_getvar('description'); ?></span>
<?php elseif ($_getvar('updateStatus') === true): ?>
    <div id="updates-info" class="icon material-icons mdl-color-text--teal-200">
        check_circle
    </div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo __('Updated'); ?></span>
<?php elseif ($_getvar('updateStatus') === false): ?>
    <div id="updates-info"
         class="icon material-icons mdl-color-text--amber-200">
        warning
    </div>
    <span for="updates-info"
          class="mdl-tooltip mdl-tooltip--top"><?php echo __('Error while checking for updates'); ?></span>
<?php endif; ?>