<?php
/**
 * @var \SP\Services\Account\AccountAcl $accountAcl
 * @var \SP\DataModel\AccountExtData    $accountData
 * @var \SP\Config\ConfigData           $configData
 */

$showLinked = $gotData && count($linkedAccounts) > 0;
$showFiles = $gotData && $configData->isFilesEnabled() && $accountAcl->isShowFiles();
$showDetails = $gotData && $accountAcl->isShowDetails();
$showPermissions = !$isView && ($accountAcl->isShowPermission() || $allowPrivate || $allowPrivateGroup);
$showCustomFields = count($customFields) > 0;

?>

<div class="data-container">
    <div id="title" class="titleNormal">
        <i class="material-icons"><?php echo $title['icon']; ?></i>
        <?php echo $title['name']; ?>
    </div>

    <div class="mdl-tabs mdl-js-tabs mdl-js-ripple-effect">
        <div class="mdl-tabs__tab-bar">
            <a href="#account-panel" class="mdl-tabs__tab is-active"><?php echo __('Cuenta'); ?></a>

            <?php if ($showCustomFields): ?>
                <a href="#customfield-panel" class="mdl-tabs__tab"><?php echo __('Campos Personalizados'); ?></a>
            <?php endif; ?>

            <?php if ($showPermissions): ?>
                <a href="#permission-panel" class="mdl-tabs__tab"><?php echo __('Permisos'); ?></a>
            <?php endif; ?>

            <?php if ($showFiles): ?>
                <a href="#file-panel" class="mdl-tabs__tab"><?php echo __('Archivos'); ?></a>
            <?php endif; ?>

            <?php if ($showDetails): ?>
                <a href="#detail-panel" class="mdl-tabs__tab"><?php echo __('Detalles'); ?></a>
            <?php endif; ?>

            <?php if ($showLinked): ?>
                <a href="#linked-panel" class="mdl-tabs__tab"><?php echo __('Enlaces'); ?></a>
            <?php endif; ?>
        </div>

        <form method="post" name="frmaccount" id="frmAccount" class="form-action"
              data-onsubmit="account/save"
              data-action-route="<?php echo isset($formRoute) ? $formRoute : ''; ?>"
              data-item-id="<?php echo isset($accountId) ? $accountId : ''; ?>"
              data-hash="">

            <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            <input type="hidden" name="isAjax" value="1">

            <div class="mdl-tabs__panel is-active" id="account-panel">
                <table class="data">
                    <tr>
                        <td class="descField"><?php echo __('Nombre'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="name" name="name" type="text" required
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="50"
                                       value="<?php echo $gotData ? htmlentities($accountData->getName()) : ''; ?>" <?php echo $readonly; ?>>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Nombre de cuenta'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Cliente'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Cliente'); ?></div>

                            <select id="client_id" name="client_id" class="select-box"
                                    required <?php echo $disabled; ?>>
                                <option value=""><?php echo __('Seleccionar Cliente'); ?></option>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $clients */
                                foreach ($clients as $client): ?>
                                    <option
                                            value="<?php echo $client->getId(); ?>" <?php echo ($gotData && $client->getId() === $accountData->getClientId()) ? 'selected' : ''; ?>><?php echo $client->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($addClientEnabled): ?>
                                <i class="material-icons btn-action" title="<?php echo __('Nuevo Cliente'); ?>"
                                   data-action-route="<?php echo $addClientRoute; ?>"
                                   data-item-route="items/clients"
                                   data-item-dst="client_id"
                                   data-item-id="0"
                                   data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Categoría'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Categoría'); ?></div>

                            <select id="category_id" name="category_id" class="select-box"
                                    required <?php echo $disabled; ?>>
                                <option value=""><?php echo __('Seleccionar Categoría'); ?></option>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $categories */
                                foreach ($categories as $category): ?>
                                    <option
                                            value="<?php echo $category->getId(); ?>" <?php echo ($gotData && $category->getId() === $accountData->getCategoryId()) ? 'selected' : ''; ?>><?php echo $category->getName(); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($addCategoryEnabled): ?>
                                <i class="material-icons btn-action" title="<?php echo __('Nueva Categoría'); ?>"
                                   data-action-route="<?php echo $addCategoryRoute; ?>"
                                   data-item-route="items/categories"
                                   data-item-dst="category_id"
                                   data-item-id="0"
                                   data-onclick="appMgmt/show"><?php echo $icons->getIconAdd()->getIcon(); ?></i>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('URL / IP'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                <input id="url" name="url" type="text"
                                       class="mdl-textfield__input mdl-color-text--indigo-400"
                                       maxlength="255"
                                       value="<?php echo $gotData ? $accountData->getUrl() : ''; ?>" <?php echo $readonly; ?>>
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
                                       maxlength="50"
                                       value="<?php echo $gotData ? htmlentities($accountData->getLogin()) : ''; ?>" <?php echo $readonly; ?>>
                                <label class="mdl-textfield__label"
                                       for="name"><?php echo __('Usuario de acceso'); ?></label>
                            </div>
                        </td>
                    </tr>
                    <?php if ($accountAcl->isShowPass()): ?>
                        <tr>
                            <td class="descField"><?php echo __('Clave'); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="password" name="password" type="password" required
                                           class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                                           maxlength="255"
                                           autocomplete="off">
                                    <label class="mdl-textfield__label"
                                           for="password"><?php echo __('Clave'); ?></label>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="descField"><?php echo __('Clave (repetir)'); ?></td>
                            <td class="valField">
                                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                    <input id="password_repeat" name="password_repeat" type="password" required
                                           class="mdl-textfield__input mdl-color-text--indigo-400"
                                           maxlength="255" autocomplete="off">
                                    <label class="mdl-textfield__label"
                                           for="password_repeat"><?php echo __('Clave (repetir)'); ?></label>
                                </div>
                            </td>
                        </tr>
                        <?php if ($configData->isAccountExpireEnabled()): ?>
                            <tr>
                                <td class="descField"><?php echo __('Fecha Caducidad Clave'); ?></td>
                                <td class="valField">
                                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                                        <input id="password_date_expire" name="password_date_expire" type="date"
                                               class="mdl-textfield__input mdl-color-text--indigo-400 password-datefield__input"
                                               value="<?php echo $accountPassDateChange; ?>"
                                               data-dst-unix="password_date_expire_unix" <?php echo $readonly; ?>>
                                        <input type='hidden' name='password_date_expire_unix' value=""/>
                                        <label class="mdl-textfield__label"
                                               for="password_date_expire"><?php echo __('Fecha'); ?></label>
                                    </div>

                                    <i class="material-icons btn-action"
                                       title="<?php echo __('Seleccionar Fecha'); ?>">date_range</i>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                    <tr>
                        <td class="descField"><?php echo __('Notas'); ?></td>
                        <td class="valField">
                            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <textarea class="mdl-textfield__input mdl-color-text--indigo-400" rows="3" id="notes"
                                  name="notes"
                                  maxlength="5000" <?php echo $readonly; ?>><?php echo $gotData ? htmlentities($accountData->getNotes()) : ''; ?></textarea>
                                <label class="mdl-textfield__label"
                                       for="notes"><?php echo __('Notas sobre la cuenta'); ?></label>
                            </div>
                    </tr>
                    <tr>
                        <td class="descField"><?php echo __('Etiquetas'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Etiquetas'); ?></div>

                            <?php if (!$isView): ?>
                                <select id="tags" name="tags[]" multiple="multiple"
                                        class="select-box-tags">
                                    <option value=""><?php echo __('Seleccionar Etiquetas'); ?></option>
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem[] $tags */
                                    foreach ($tags as $tag): ?>
                                        <option value="<?php echo $tag->getId(); ?>" <?php echo $tag->isSelected() ? 'selected' : ''; ?>><?php echo $tag->getName(); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <?php /** @var \SP\Mvc\View\Components\SelectItem[] $tags */
                                foreach ($tags as $tag): ?>
                                    <?php if ($tag->isSelected()): ?>
                                        <span class="tag"><?php echo $tag->getName(); ?></span>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (isset($linkedAccounts) && count($linkedAccounts) === 0): ?>
                        <tr>
                            <td class="descField"><?php echo __('Cuenta Vinculada'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Cuenta Vinculada'); ?></div>

                                <select id="parent_account_id" name="parent_account_id"
                                        class="select-box select-box-deselect" <?php echo $disabled; ?>
                                        data-sk="<?php echo $sk; ?>"
                                        data-action-route="items/accountsUser"
                                        data-item-id="<?php echo $gotData ? $accountId : ''; ?>"
                                        data-selected-id="<?php echo $gotData ? $accountData->getParentId() : ''; ?>">
                                    <option value=""><?php echo __('Seleccionar Cuenta'); ?></option>
                                </select>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($accountAcl->isShowHistory() && count($historyData) > 0): ?>
                        <tr>
                            <td class="descField"><?php echo __('Historial'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Historial'); ?></div>

                                <select id="history_id" name="history_id" class="select-box"
                                        data-action-route="account/viewHistory"
                                        data-onchange="account/viewHistory">
                                    <option value=""><?php echo __('Seleccionar fecha'); ?></option>
                                    <?php foreach ($historyData as $historyId => $historyData): ?>
                                        <option
                                                value="<?php echo $historyId; ?>" <?php echo ($gotData && $accountIsHistory && $historyId === $accountHistoryId) ? 'selected' : ''; ?>><?php echo $historyData; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($editAction) && $isModified && $accountData->getUserEditName()): ?>
                        <tr>
                            <td class="descField"><?php echo __('Última Modificación'); ?></td>
                            <td class="valField"><?php printf('%s (%s)', $accountData->getDateEdit(), $accountData->getUserEditName()); ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if (isset($publicLinkShow) && $publicLinkShow && $publicLinkUrl !== null): ?>
                        <tr>
                            <td class="descField"><?php echo __('Enlace Público'); ?></td>
                            <td class="valField">
                                <div class="lowres-title"><?php echo __('Enlace Público'); ?></div>

                                <a href="<?php echo $publicLinkUrl; ?>" target="_blank"
                                   title="<?php echo __('Enlace Público'); ?>">
                                    <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>

                    <tr>
                        <td class="descField"><?php echo __('Enlace Directo'); ?></td>
                        <td class="valField">
                            <div class="lowres-title"><?php echo __('Enlace Directo'); ?></div>

                            <a href="<?php echo $deepLink; ?>" target="_blank"
                               title="<?php echo __('Enlace Directo'); ?>">
                                <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                            </a>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="mdl-tabs__panel" id="customfield-panel">
                <?php if ($showCustomFields): ?>
                    <div id="data-customfields">
                        <table class="data">
                            <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mdl-tabs__panel" id="permission-panel">
                <?php if ($showPermissions): include $this->includeTemplate('account-permissions'); endif; ?>
            </div>
        </form>

        <div class="mdl-tabs__panel" id="file-panel">
            <?php if ($showFiles) : include $this->includeTemplate('files'); endif; ?>
        </div>

        <div class="mdl-tabs__panel" id="detail-panel">
            <?php if ($showDetails): include $this->includeTemplate('details'); endif; ?>
        </div>

        <div class="mdl-tabs__panel" id="linked-panel">
            <?php if ($showLinked): include $this->includeTemplate('linkedAccounts'); endif; ?>
        </div>
    </div>

    <?php include $this->includeTemplate('actions'); ?>
</div>