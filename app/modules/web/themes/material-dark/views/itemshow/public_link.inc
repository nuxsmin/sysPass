<?php
/**
 * @var PublicLinkListData $publicLink
 * @var ThemeIcons         $icons
 * @var ConfigData         $configData
 * @var callable           $_getvar
 * @var Template           $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\PublicLinkListData;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

$publicLink = $_getvar('publicLink');
?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>
    <form method="post" name="frmPublickLink" id="frmPublickLink"
          class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Account'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Account'); ?></div>

                    <select id="accountId" name="accountId"
                            class="select-box" <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Select Account'); ?></option>
                        <?php /** @var SelectItem $account */
                        foreach ($_getvar('accounts') as $account): ?>
                            <option
                                    value="<?php echo $account->getId(); ?>" <?php echo $account->isSelected() ? 'selected' : ''; ?>><?php printf('%s (%s)', $account->getName(), $account->getItemProperty('clientName')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Notify'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect"
                           for="notify"
                           title="<?php echo __('Notify'); ?>">
                        <input type="checkbox" id="notify"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="notify" <?php echo $_getvar('isView') && $publicLink->isNotify() ? 'CHECKED' : ''; ?> <?php echo $_getvar('disabled'); ?>/>
                        <span class="mdl-switch__label"><?php echo __('Notify'); ?></span>
                    </label>
                </td>
            </tr>
            <?php if ($_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php echo __('Creation Date'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Creation Date'); ?></div>

                        <?php echo $publicLink->getDateAddFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Expiry Date '); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Expiry Date '); ?></div>

                        <?php echo $publicLink->getDateExpireFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('User'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('User'); ?></div>

                        <?php echo $publicLink->getUserLogin(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Visits'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Visits'); ?></div>

                        <?php echo $publicLink->getCountViewsString(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Hash'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Hash'); ?></div>

                        <?php echo $publicLink->getHash(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('URL'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('URL'); ?></div>

                        <a href="<?php echo $_getvar('publicLinkURL'); ?>"
                           target="_blank"><?php echo $_getvar('publicLinkURL'); ?></a>
                    </td>
                </tr>
                <?php if (is_array($_getvar('usageInfo'))): ?>
                    <tr>
                        <td class="descField"><?php echo __('Use'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Use'); ?></div>

                            <div class="list-wrap">
                                <ul class="mdl-list">
                                    <?php foreach ($_getvar('usageInfo') as $useInfo): ?>
                                        <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">access_time</i>
                                    <?php printf('%s - %s', $_getvar('isDemo') ? '*.*.*.*' : $useInfo['who'], date('Y-m-d H:i', $useInfo['time'])); ?>
                                </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>

    <?php if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button form="frmPublickLink"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
