<?php /** @var $icons \Theme\Icons */ ?>

<div class="data-container">
    <div id="title" class="midroundup titleNormal">
        <?php echo $title['name']; ?>
        <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
    </div>

    <form method="post" name="editpass" id="frmEditPass" class="form-action" data-onsubmit="account/save" data-hash="">
        <table class="data round">
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getAccountName(); ?>" readonly>
                        <label class="mdl-textfield__label" for="name"><?php echo __('Nombre de cuenta'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Cliente'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="customer" name="customer" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getCustomerName(); ?>" readonly>
                        <label class="mdl-textfield__label" for="customer"><?php echo __('Cliente'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getAccountUrl(); ?>" readonly>
                        <label class="mdl-textfield__label" for="url"><?php echo __('URL / IP'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Usuario'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="user" name="user" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getAccountLogin(); ?>" readonly>
                        <label class="mdl-textfield__label" for="user"><?php echo __('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpass" name="pass" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label" for="accountpass"><?php echo __('Clave'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Clave (repetir)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpassR" name="passR" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label" for="accountpassR"><?php echo __('Clave'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Fecha Caducidad Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="accountpassdatechange" name="accountpassdatechange" type="date"
                               class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                               value="<?php echo $accountPassDateChange; ?>">
                        <label class="mdl-textfield__label"
                               for="accountpassdatechange"><?php echo __('Fecha'); ?></label>
                    </div>
                    <i class="material-icons btn-action"
                       title="<?php echo __('Seleccionar Fecha'); ?>">date_range</i>
                </td>
            </tr>
        </table>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $accountId; ?>"/>
        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">

        <div class="item-actions">
            <ul>
                <li>
                    <button id="btnBack" type="button"
                            class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                            title="<?php echo __('Atrás'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    </button>
                </li>
                <li>
                    <button id="btnSave" name="btnSave" type="submit" form="frmEditPass"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                            title="<?php echo __('Guardar'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                    </button>
                </li>
            </ul>
        </div>
    </form>
</div>

<script>
    sysPassApp.sk.set("<?php echo $sk; ?>");
    $('input:password:visible:first').focus();
</script>
