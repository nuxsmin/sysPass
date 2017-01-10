<main class="mdl-layout__content">
    <div id="actions" class="upgrade">
        <div id="page-title">
            <h1><?php printf(_('Actualización  %s'), $appVersion); ?></h1>
        </div>

        <form id="frmUpgrade" method="post" action="index.php">
            <fieldset>
                <legend><?php echo _('Actualización de BBDD'); ?></legend>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="h" name="h" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                           autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="h"><?php echo _('Código de Seguridad'); ?></label>
                </div>

                <div id="help-code"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip" for="help-code">
                    <?php echo _('Este código se encuentra en el archivo de configuración de sysPass con la etiqueta "upgradeKey"'); ?>
                </div>

                <input type="hidden" name="a" value="<?php echo $action; ?>">
                <input type="hidden" name="t" value="<?php echo $time; ?>">
                <input type="hidden" name="upgrade" value="1">
            </fieldset>

            <div class="buttons">
                <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                        type="submit">
                    <?php echo _('Actualizar'); ?>
                    <i class="material-icons"
                       title="<?php echo _('Iniciar Actualización'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</main>
