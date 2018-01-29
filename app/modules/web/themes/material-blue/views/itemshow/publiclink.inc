<?php
/**
 * @var $publicLink SP\DataModel\PublicLinkListData
 */
?>
<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>
    <form method="post" name="frmPublickLink" id="frmPublickLink" class="form-action"
          data-onsubmit="appMgmt/save"
          data-route="<?php echo isset($route) ? $route : ''; ?>"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction="<?php echo $nextAction; ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Cuenta'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Cuenta'); ?></div>

                    <select id="accountId" name="accountId" class="select-box" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Cuenta'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $accounts */
                        foreach ($accounts as $account): ?>
                            <option
                                    value="<?php echo $account->getId(); ?>" <?php echo $account->isSelected() ? 'selected' : ''; ?>><?php printf('%s (%s)', $account->getName(), $account->getItemProperty('clientName')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Notificar'); ?></td>
                <td class="valField">
                    <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="notify"
                           title="<?php echo __('Notificar'); ?>">
                        <input type="checkbox" id="notify"
                               class="mdl-switch__input mdl-color-text--indigo-400"
                               name="notify" <?php echo $isView && $publicLink->isNotify() ? 'CHECKED' : ''; ?> <?php echo $disabled; ?>/>
                        <span class="mdl-switch__label"><?php echo __('Notificar'); ?></span>
                    </label>
                </td>
            </tr>
            <?php if ($isView): ?>
                <tr>
                    <td class="descField"><?php echo __('Fecha Creación'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Fecha Creación'); ?></div>

                        <?php echo $publicLink->getDateAddFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Fecha Caducidad'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Fecha Caducidad'); ?></div>

                        <?php echo $publicLink->getDateExpireFormat(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Usuario'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Usuario'); ?></div>

                        <?php echo $publicLink->getUserLogin(); ?>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Visitas'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Visitas'); ?></div>

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

                        <a href="<?php echo $publicLinkURL; ?>" target="_blank"><?php echo $publicLinkURL; ?></a>
                    </td>
                </tr>
                <?php if (is_array($usageInfo)): ?>
                    <tr>
                        <td class="descField"><?php echo __('Uso'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Uso'); ?></div>

                            <div class="list-wrap">
                                <ul class="mdl-list">
                                    <?php /** @var array $usageInfo */
                                    foreach ($usageInfo as $useInfo): ?>
                                        <li class="mdl-list__item">
                                <span class="mdl-list__item-primary-content">
                                <i class="material-icons mdl-list__item-icon">access_time</i>
                                    <?php printf('%s - %s', $isDemo ? '*.*.*.*' : $useInfo['who'], date('Y-m-d H:i', $useInfo['time'])); ?>
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
        <?php if (!$isView): ?>
            <input type="hidden" name="sk" value="">
            <input type="hidden" name="isAjax" value="1">
        <?php endif; ?>
    </form>

    <?php if (!$isView): ?>
        <div class="action-in-box">
            <button form="frmPublickLink"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>