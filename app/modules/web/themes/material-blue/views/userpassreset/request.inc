<?php /** @var $icons \SP\Core\UI\ThemeIcons */ ?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div class="box-header">
        <?php echo __('Solicitud de Cambio de Clave'); ?>
    </div>
    <div id="box-passreset" class="box-form round">
        <form id="frmPassReset" action="" method="post" class="form-action" data-onsubmit="user/passreset"
              data-action-route="userPassReset/saveRequest">
            <fieldset id="box-data">
                <div>
                    <i class="material-icons">perm_identity</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" autofocus>
                        <label class="mdl-textfield__label"
                               for="login"><?php echo __('Usuario'); ?></label>
                    </div>
                </div>
                <div>
                    <i class="material-icons">email</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="email" name="email" type="email" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="email"><?php echo __('Email del Usuario'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="isAjax" value="1">
                <input type="hidden" name="sk" value="">
            </fieldset>

            <div id="box-buttons">
                <button id="btnBack" type="button"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <i class="material-icons"
                       title="<?php echo __('Volver a iniciar sesión'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    <?php echo __('Volver'); ?>
                </button>

                <button id="btnRequest" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo __('Solicitar'); ?>
                    <i class="material-icons"
                       title="<?php echo __('Solicitar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</div>
