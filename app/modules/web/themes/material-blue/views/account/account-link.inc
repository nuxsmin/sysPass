<div id="actions" class="public-link" align="center">
    <div id="content" class="public-link">
        <div id="title" class="midroundup titleNormal">
            <?php echo $title['name']; ?>
            <i id="history-icon" class="material-icons"><?php echo $title['icon']; ?></i>
        </div>
        <table class="data round">
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
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
                        <label class="mdl-textfield__label" for="name"><?php echo __('Cliente'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Categoría'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="category" name="category" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getCategoryName(); ?>" readonly>
                        <label class="mdl-textfield__label" for="name"><?php echo __('Categoría'); ?></label>
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
                        <label class="mdl-textfield__label" for="name"><?php echo __('URL / IP'); ?></label>
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
                        <label class="mdl-textfield__label" for="name"><?php echo __('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Notas'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes"
                                  maxlength="1000" readonly><?php echo $accountData->getAccountNotes(); ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo __('Notas sobre la cuenta'); ?></label>
                    </div>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Clave'); ?></td>
                <td class="valField">
                    <?php if ($useImage): ?>
                        <div class="lowres-title"><?php echo __('Clave'); ?></div>

                        <img class="account-pass-image" src="data:image/png;base64,<?php echo $accountPass; ?>"/>
                    <?php else: ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="pass" name="pass" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php echo $accountPass; ?>" readonly>
                            <label class="mdl-textfield__label" for="name"><?php echo __('Clave'); ?></label>
                        </div>
                    <?php endif; ?>
            </tr>
        </table>

        <div class="item-actions">
            <ul>
                <?php if (!$useImage): ?>
                    <li>
                        <button id="btnClipPass"
                                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200"
                                data-clipboard-target="#account-pass"
                                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                                data-item-id="<?php echo $accountId; ?>"
                                data-sk="<?php echo $sk; ?>"
                                data-history="0"
                                data-onclick="account/copypass">
                            <i class="material-icons">content_paste</i>
                        </button>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>