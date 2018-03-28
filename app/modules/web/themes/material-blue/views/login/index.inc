<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div id="box-login" class="box-form round">
        <form method="post" name="frmLogin" id="frmLogin" class="form-action"
              data-onsubmit="main/login"
              data-route="login/login">
            <fieldset id="box-data">
                <div>
                    <i class="material-icons">perm_identity</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="user" name="user" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="user"><?php echo __('Usuario'); ?></label>
                    </div>
                </div>

                <div>
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="pass" name="pass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="pass"><?php echo __('Clave'); ?></label>
                    </div>
                </div>

                <div id="soldpass" class="extra-hidden">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="oldpass" name="oldpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="oldpass"><?php echo __('Clave Anterior'); ?></label>
                    </div>
                </div>

                <div id="smpass" class="extra-hidden">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mpass" name="mpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="mpass"><?php echo __('Clave Maestra'); ?></label>
                    </div>
                </div>

                <?php if (isset($from, $from_hash)): ?>
                    <input type="hidden" name="from" value="<?php echo $from; ?>"/>
                    <input type="hidden" name="h" value="<?php echo $from_hash; ?>"/>
                <?php endif; ?>

                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
            </fieldset>
            <div id="box-buttons">
                <button id="btnLogin" type="submit" form="frmLogin"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-button--colored mdl-color--indigo-400"
                        title="<?php echo __('Acceder'); ?>">
                    <i class="material-icons">play_arrow</i>
                </button>
            </div>
        </form>

        <!-- Close boxData -->
        <?php if ($mailEnabled): ?>
            <div id="box-actions">
                <a href="index.php?r=userPassReset"><?php echo __('¿Olvidó su clave?'); ?></a>
            </div>
        <?php endif; ?>
    </div><!-- Close boxLogin -->

    <?php if (isset($updated)): ?>
        <div id="box-updated" class="round5"><?php echo __('Aplicación actualizada correctamente'); ?></div>
    <?php endif; ?>

    <?php if ($isDemoMode): ?>
        <div id="demo-info">
            <ul>
                <li title="<?php echo __('Usuario'); ?>">
                    <i class="material-icons">perm_identity</i>
                    <span>demo</span>
                </li>
                <li title="<?php echo __('Clave'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>syspass</span>
                </li>
                <li title="<?php echo __('Clave Maestra'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>12345678900</span>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>