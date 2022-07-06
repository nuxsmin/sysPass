<?php
/**
 * @var callable       $_getvar
 * @var ThemeIcons     $icons
 * @var AccountExtData $accountData
 * @var AccountAcl     $accountAcl
 * @var ConfigData     $configData
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\AccountExtData;
use SP\Services\Account\AccountAcl;

?>
<div id="box-popup" class="box-password-view">
    <h2 class="center"><?php echo $_getvar('header'); ?>&nbsp;
        <?php if ($_getvar('isLinked')): ?>
            <i title="<?php echo __('Linked Account'); ?>"
               class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
        <?php endif; ?>
    </h2>
    <table>
        <tbody>
        <tr>
            <?php if (!$_getvar('isImage')): ?>
                <td class="dialog-text dialog-user-text"><?php echo wordwrap($_getvar('login'), 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copy Username'); ?>"
                            data-clipboard-target=".dialog-user-text">
                        <?php echo __('User'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-user-text">
                    <img src="data:image/png;base64,<?php echo $_getvar('login'); ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        <tr>
            <?php if (!$_getvar('isImage')): ?>
                <td class="dialog-text dialog-pass-text"><?php echo wordwrap($_getvar('pass'), 50, PHP_EOL, true); ?></td>
                <td class="dialog-button right">
                    <button class="dialog-clip-button mdl-button mdl-js-button mdl-js-ripple-effect"
                            title="<?php echo __('Copy Password'); ?>"
                            data-clipboard-target=".dialog-pass-text">
                        <?php echo __('Password'); ?>
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </td>
            <?php else: ?>
                <td class="dialog-pass-text">
                    <img src="data:image/png;base64,<?php echo $_getvar('pass'); ?>"/>
                </td>
                <td>&nbsp;</td>
            <?php endif; ?>
        </tr>
        </tbody>
    </table>
</div>
