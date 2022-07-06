<?php
/**
 * @var ThemeIcons $icons
 * @var callable   $_getvar
 * @var ConfigData $configData
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;

?>

<div id="data-linkedAccounts">
    <div class="data">
        <ul class="mdl-list">
            <?php foreach ($_getvar('linkedAccounts') as $account): ?>
                <li class="mdl-list__item">
                    <span class="btn-action mdl-list__item-primary-content">
                        <i class="material-icons mdl-list__item-icon">layers</i>
                        <?php printf('%s (%s)', $account->name, $account->clientName); ?>
                    </span>
                    <span class="list-actions">
                        <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconEdit()->getClass(); ?>"
                           title="<?php echo __('Edit Account'); ?>"
                           data-action-route="account/edit"
                           data-item-id="<?php echo $account->id; ?>"
                           data-onclick="account/edit"
                           data-action-sk="<?php echo $_getvar('sk'); ?>">
                            <?php echo $icons->getIconEdit()->getIcon(); ?></i>

                        <i class="material-icons btn-action mdl-list__item-icon <?php echo $icons->getIconView()->getClass(); ?>"
                           title="<?php echo __('Account Details'); ?>"
                           data-action-route="account/view"
                           data-item-id="<?php echo $account->id; ?>"
                           data-onclick="account/view"
                           data-action-sk="<?php echo $_getvar('sk'); ?>">
                        <?php echo $icons->getIconView()->getIcon(); ?></i>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
