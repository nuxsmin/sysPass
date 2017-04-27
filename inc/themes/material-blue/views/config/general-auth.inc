<div id="title" class="midroundup titleNormal">
    <?php echo __('Autentificación'); ?>
</div>

<table id="tblAuth" class="data tblConfig round">
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="authbasic_enabled">
                <input type="checkbox" id="authbasic_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="authbasic_enabled"
                    <?php echo $Config->isAuthBasicEnabled() ? 'checked' : ''; ?>/>
                <span class="mdl-switch__label"><?php echo __('Usar Auth Basic'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField"></td>
        <td class="valField">
            <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="authbasicautologin_enabled">
                <input type="checkbox" id="authbasicautologin_enabled" class="mdl-switch__input mdl-color-text--indigo-400"
                       name="authbasicautologin_enabled"
                    <?php echo $Config->isAuthBasicAutoLoginEnabled() ? 'checked' : ''; ?>/>
                <span class="mdl-switch__label"><?php echo __('Usar auto-login con Auth Basic'); ?></span>
            </label>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Dominio'); ?>
            <div id="help-domain"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-domain">
                <p>
                    <?php echo __('El dominio será añadido al nombre de usuario de sysPass para comprobarlo con el de Auth Basic.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="authbasic_domain" name="authbasic_domain" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="128" value="<?php echo $Config->getAuthBasicDomain(); ?>">
                <label class="mdl-textfield__label"
                       for="authbasic_domain"><?php echo __('Nombre de dominio'); ?></label>
            </div>
        </td>
    </tr>
</table>