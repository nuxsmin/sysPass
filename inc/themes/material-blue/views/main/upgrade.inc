<main class="mdl-layout__content">
    <div id="actions" class="upgrade">
        <div id="page-title">
            <h1><?php printf(__('Actualización  %s'), $appVersion); ?></h1>
        </div>

        <form id="frmUpgrade" method="post" action="index.php">
            <fieldset>
                <legend><?php echo __('Actualización de BBDD'); ?></legend>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="h" name="h" type="text" required class="mdl-textfield__input mdl-color-text--indigo-400"
                           autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="h"><?php echo __('Código de Seguridad'); ?></label>
                </div>

                <div id="help-code"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-code">
                    <?php echo __('Este código se encuentra en el archivo de configuración de sysPass con la etiqueta "upgradeKey"'); ?>
                </div>

                <?php if (count($constraints) > 0): ?>
                    <div>
                        <ul class="errors">
                            <li class="msg-warning">
                                <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                                <?php echo __('Se han encontrado elementos huérfanos. Por favor, modifique estos elementos o indique los IDs por defecto para los elementos huérfanos.'); ?>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <ul class="errors">
                            <?php foreach ($constraints as $msg): ?>
                                <li class="msg-warning"><?php echo $msg; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php if ($checkConstraints->accounts_user > 0
                        || $checkConstraints->accounts_useredit > 0
                        || $checkConstraints->accountshistory_user > 0
                        || $checkConstraints->accountshistory_useredit > 0
                    ): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="userid" name="userid" type="number" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="1" max="1000">
                            <label class="mdl-textfield__label"
                                   for="h"><?php echo __('Introducir un ID de usuario válido para cuentas'); ?></label>
                        </div>
                    <?php endif; ?>

                    <?php if ($checkConstraints->accounts_category > 0
                        || $checkConstraints->accountshistory_category > 0
                    ): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="categoryid" name="categoryid" type="number" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="1" max="1000">
                            <label class="mdl-textfield__label"
                                   for="h"><?php echo __('Introducir un ID de categoría válido para cuentas'); ?></label>
                        </div>
                    <?php endif; ?>

                    <?php if ($checkConstraints->accounts_customer > 0
                        || $checkConstraints->accountshistory_customer > 0
                    ): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="customerid" name="customerid" type="number" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="1" max="1000">
                            <label class="mdl-textfield__label"
                                   for="h"><?php echo __('Introducir un ID de cliente válido para cuentas'); ?></label>
                        </div>
                    <?php endif; ?>

                    <?php if ($checkConstraints->users_group > 0): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="groupid" name="groupid" type="number" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="1" max="1000">
                            <label class="mdl-textfield__label"
                                   for="h"><?php echo __('Introducir un ID de grupo válido para usuarios'); ?></label>
                        </div>
                    <?php endif; ?>

                    <?php if ($checkConstraints->users_profile > 0): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="profileid" name="profileid" type="number" required
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="1" max="1000">
                            <label class="mdl-textfield__label"
                                   for="h"><?php echo __('Introducir un ID de perfil válido para usuarios'); ?></label>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect" for="chkConfirm">
                    <input type="checkbox" id="chkConfirm" class="mdl-checkbox__input" name="chkConfirm">
                    <span class="mdl-checkbox__label"><?php echo __('He realizado una copia de seguridad completa de sysPass'); ?></span>
                </label>

                <input type="hidden" name="a" value="<?php echo $action; ?>">
                <input type="hidden" name="t" value="<?php echo $time; ?>">
                <input type="hidden" name="upgrade" value="1">
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
</main>
