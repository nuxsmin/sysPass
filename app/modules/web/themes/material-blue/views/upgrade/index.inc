<?php
/**
 * @var \SP\Mvc\View\Template  $this
 * @var \SP\Core\UI\ThemeIcons $icons
 */
?>
<div id="actions" class="installer" align="center">
    <ul class="errors round">
        <li class="msg-warning">
            <i class="material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
            <strong><?php echo __('La aplicación necesita actualizarse'); ?></strong>
            <p class="hint">
                <?php echo __('Consulte con el administrador'); ?>
            </p>
            <p class="hint">
                <?php echo __('Para iniciar la actualización introduzca el código de seguridad'); ?>
            </p>
        </li>
    </ul>

    <form id="frmUpgrade" method="get" class="form-action" data-onsubmit="main/upgrade"
          data-action-route="upgrade/upgrade">
        <fieldset>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="key" name="key" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                       autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="key"><?php echo __('Código de Seguridad'); ?></label>
            </div>

            <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="chkConfirm">
                <input type="checkbox" id="chkConfirm" class="mdl-checkbox__input" name="chkConfirm" required>
                <span class="mdl-checkbox__label"><?php echo __('He realizado una copia de seguridad completa de sysPass'); ?></span>
            </label>
        </fieldset>

        <div>
            <ul class="errors">
                <li class="msg-warning">
                    <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                    <?php echo __('Por favor espere mientras el proceso se ejecuta'); ?>
                </li>
            </ul>
        </div>

        <div class="buttons">
            <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                    type="submit">
                <?php echo __('Actualizar'); ?>
                <i class="material-icons"
                   title="<?php echo __('Iniciar Actualización'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>