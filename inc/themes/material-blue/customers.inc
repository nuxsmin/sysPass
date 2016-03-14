<?php

/** @var $icons \Theme\Icons */
/** @var $customer \SP\DataModel\CustomerData */

?>

<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCustomers" id="frmCustomers" onsubmit="sysPassUtil.Common.appMgmtSave('frmCustomers'); return false;">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $customer->getCustomerName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Nombre del cliente'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $customer->getCustomerDescription(); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo _('Descripción del cliente'); ?></label>
                    </div>
                </td>
            </tr>

            <?php if($customFields): ?>
                <?php include 'aux-customfields.inc'; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $customer->getCustomerId(); ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmCustomers" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>