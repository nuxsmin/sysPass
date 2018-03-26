<?php
/** @var \SP\Account\AccountAcl $accountAcl */
/** @var \SP\DataModel\AccountHistoryData $accountData */
/** @var \SP\Mvc\View\Template $this */
?>

<div class="data-container">
    <div id="title" class="titleNormal">
        <i class="material-icons" title="<?php echo __('Histórico'); ?>"><?php echo $title['icon']; ?></i>
        <?php echo $title['name']; ?>
    </div>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#account-panel" class="mdl-tabs__tab is-active"><?php echo __('Cuenta'); ?></a>

            <?php if ($accountAcl->isShowDetails()): ?>
                <a href="#detail-panel" class="mdl-tabs__tab"><?php echo __('Detalles'); ?></a>
            <?php endif; ?>
        </div>

        <div class="mdl-tabs__panel is-active" id="account-panel">
            <form method="post" name="frmaccount" id="frmAccount" class="form-action"
                  data-onsubmit="account/save"
                  data-action-route="<?php echo isset($formRoute) ? $formRoute : ''; ?>"
                  data-item-id="<?php echo isset($accountId) ? $accountId : ''; ?>"
                  data-hash="">
                <input type="hidden" name="sk" value="">
                <input type="hidden" name="isAjax" value="1">

                <table class="data">
                    <tr>
                        <td class="descField"><?php echo __('Nombre'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="name" name="name" type="text" required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="50"
                                       value="<?php echo $accountData->getName() ?>"
                                       tabindex="1" readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Nombre de cuenta'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Cliente'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Cliente'); ?></div>

                            <select id="client_id" name="client_id" class="select-box sel-chosen-client"
                                    tabindex="2" required disabled>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $clients */
                                foreach ($clients as $client): ?>
                                    <option
                                            value="<?php echo $client->getId(); ?>" <?php echo $client->isSelected() ? 'selected' : ''; ?>><?php echo $client->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Categoría'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Categoría'); ?></div>

                            <select id="category_id" name="category_id" class="select-box"
                                    tabindex="3" required disabled>
                                <option value=""><?php echo __('Seleccionar Categoría'); ?></option>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $categories */
                                foreach ($categories as $category): ?>
                                    <option
                                            value="<?php echo $category->getId(); ?>" <?php echo $category->isSelected() ? 'selected' : ''; ?>><?php echo $category->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('URL / IP'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="url" name="url" type="text"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="255" tabindex="4"
                                       value="<?php echo $accountData->getUrl(); ?>" readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('URL o IP de acceso'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Usuario'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="login" name="login" type="text"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="50" tabindex="5"
                                       value="<?php echo $accountData->getLogin(); ?>" readonly>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Usuario de acceso'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Fecha Caducidad Clave'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="password_date_expire" name="password_date_expire" type="date"
                                       class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                       tabindex="8"
                                       value="<?php echo $accountPassDateChange; ?>" readonly>
                                <label class="mdl-textfield__label"
                                       for="password_date_expire"><?php echo __('Fecha'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Notas'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes" tabindex="9"
                                  maxlength="5000" readonly><?php echo $accountData->getNotes(); ?></textarea>
                                <label class="mdl-textfield__label"
                                       for="notes"><?php echo __('Notas sobre la cuenta'); ?></label>
                            </div>
                    </tr>
                    <?php if ($accountAcl->isShowHistory() && count($historyData) > 0): ?>
                        <tr>
                            <td class="descField"><?php echo __('Historial'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Historial'); ?></div>

                                <select id="history_id" name="history_id" class="select-box" tabindex="12"
                                        data-action-route="account/viewHistory"
                                        data-onchange="account/viewHistory">
                                    <option value=""><?php echo __('Seleccionar fecha'); ?></option>
                                    <?php foreach ($historyData as $historyId => $historyData): ?>
                                        <option
                                                value="<?php echo $historyId; ?>" <?php echo ($accountIsHistory && $historyId === $accountHistoryId) ? 'selected' : ''; ?>><?php echo $historyData; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Última Modificación'); ?></td>
                        <td class="valField"><?php printf('%s (%s)', $accountData->getDateEdit(), $accountData->getUserEditName() ?: $accountData->getUserEditLogin()); ?></td>
                    </tr>
                </table>

            </form>
        </div>

        <div class="mdl-tabs__panel" id="detail-panel">
            <?php if ($accountAcl->isShowDetails()): include $this->includeTemplate('details'); endif; ?>
        </div>

    </div>

    <?php include $this->includeTemplate('actions'); ?>
</div>
<script>
    (function () {
        sysPassApp.sk.set("<?php echo $sk; ?>");
    })();
</script>
