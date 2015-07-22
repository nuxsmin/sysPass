<div id="fancyContainer" align="center">
    <h2 class="midround"><?php echo $header; ?></H2>

    <form method="post" name="frmCategories" id="frmCategories">
        <table class="fancydata">
            <tbody>
            <tr>
                <td class="descField"><?php echo _('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo $category["category_name"]; ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo _('Nombre de la categoría'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo _('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text" class="mdl-textfield__input fg-blue100"
                               maxlength="50" value="<?php echo $category["category_description"]; ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo _('Descripción de la categoría'); ?></label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="activeTab" value="<?php echo $activeTab ?>"/>
        <input type="hidden" name="onCloseAction" value="<?php echo $onCloseAction ?>"/>
        <input type="hidden" name="itemId" value="<?php echo $category["category_id"]; ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <div id="resCheck"><span id="resFancyAccion"></span></div>
    <div class="action-in-box">
        <button class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="appMgmtSave('frmCategories');" title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    </div>
</div>