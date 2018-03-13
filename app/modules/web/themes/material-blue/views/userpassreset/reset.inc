<?php /** @var $icons \SP\Core\UI\ThemeIcons */ ?>

<div id="actions">
    <form id="frmPassReset" action="" method="post" class="form-action" data-onsubmit="user/passreset"
          data-action-route="userPassReset/saveReset">
        <fieldset id="resetdata">
            <legend><?php echo __('Solicitud de Cambio de Clave'); ?></legend>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="pass" name="pass" type="password" required
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                       value="" maxlength="255"
                       autocomplete="off">
                <label class="mdl-textfield__label"
                       for="pass"><?php echo __('Clave'); ?></label>
            </div>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="passR" name="passR" type="password" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="" maxlength="255"
                       autocomplete="off">
                <label class="mdl-textfield__label"
                       for="pass"><?php echo __('Clave (repetir)'); ?></label>
            </div>
            <input type="hidden" name="hash" value="<?php echo $hash; ?>">
            <input type="hidden" name="isAjax" value="1">
            <input type="hidden" name="sk" value="">
        </fieldset>

        <div class="buttons">
            <button id="btnBack" type="button"
                    class="mdl-button mdl-js-button mdl-button--raised mdl-button--primary">
                <i class="material-icons"
                   title="<?php echo __('Volver a iniciar sesión'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                <?php echo __('Volver'); ?>
            </button>

            <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--primary">
                <?php echo __('Cambiar'); ?>
                <i class="material-icons"
                   title="<?php echo __('Cambiar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>
