<?php
/**
 * @var \SP\DataModel\AccountExtData $accountData
 */
?>

<div id="actions" class="public-link" align="center">
    <div id="content" class="public-link">
        <div id="title" class="titleNormal">
            <i class="material-icons"><?php echo $title['icon']; ?></i>
            <?php echo $title['name']; ?>
        </div>
        <table class="data">
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $accountData->getName(); ?>" readonly>
                        <label class="mdl-textfield__label" for="name"><?php echo __('Nombre de cuenta'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Cliente'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="client" name="client" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getClientName(); ?>" readonly>
                        <label class="mdl-textfield__label" for="client"><?php echo __('Cliente'); ?></label>
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
                        <label class="mdl-textfield__label" for="category"><?php echo __('Categoría'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('URL / IP'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="url" name="url" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getUrl(); ?>" readonly>
                        <label class="mdl-textfield__label" for="url"><?php echo __('URL / IP'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Usuario'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $accountData->getLogin(); ?>" readonly>
                        <label class="mdl-textfield__label" for="login"><?php echo __('Usuario'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Notas'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes"
                                  maxlength="1000" readonly><?php echo $accountData->getNotes(); ?></textarea>
                        <label class="mdl-textfield__label"
                               for="notes"><?php echo __('Notas sobre la cuenta'); ?></label>
                    </div>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Clave'); ?></td>
                <td class="valField">
                    <?php if ($useImage): ?>
                        <div class="lowres-title"><?php echo __('Clave'); ?></div>

                        <img class="account-pass-image" src="data:image/png;base64,<?php echo $accountPassImage; ?>"/>
                    <?php else: ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="password" name="password" type="text"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   value="<?php echo $accountData->getPass(); ?>" readonly>
                            <label class="mdl-textfield__label" for="password"><?php echo __('Clave'); ?></label>
                        </div>
                    <?php endif; ?>
            </tr>
        </table>

        <div class="item-actions">
            <ul>
                <?php if (!$useImage): ?>
                    <li>
                        <button id="btnClipPass"
                                class="clip-pass-icon mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored mdl-color--indigo-A200"
                                data-clipboard-target="#password"
                                data-item-id="<?php echo $accountData->getId(); ?>"
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