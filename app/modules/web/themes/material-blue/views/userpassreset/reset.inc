<?php
/**
 * @var \SP\Core\UI\ThemeIcons $icons
 * @var \SP\Config\ConfigData  $configData
 * @var callable               $_getvar
 * @var \SP\Mvc\View\Template  $this
 */
?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div class="box-header">
        <?php echo __('Solicitud de Cambio de Clave'); ?>
    </div>
    <div id="box-passreset" class="box-form round">
        <form id="frmUserPassReset" action="" method="post" class="form-action" data-onsubmit="user/passreset"
              data-action-route="userPassReset/saveReset">
            <fieldset id="box-data">
                <div class="form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password" name="password" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               value="" maxlength="255"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password"><?php echo __('Clave'); ?></label>
                    </div>
                </div>
                <div class="form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="password_repeat" name="password_repeat" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="" maxlength="255"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="password_repeat"><?php echo __('Clave (repetir)'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="hash" value="<?php echo $_getvar('hash'); ?>">
                <input type="hidden" name="isAjax" value="1">
            </fieldset>

            <div id="box-buttons">
                <button id="btnBack" type="button"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <i class="material-icons"
                       title="<?php echo __('Volver a iniciar sesión'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    <?php echo __('Volver'); ?>
                </button>

                <button id="btnChange" class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo __('Cambiar'); ?>
                    <i class="material-icons"
                       title="<?php echo __('Cambiar'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</div>
