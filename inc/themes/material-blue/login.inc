<div id="boxLogin" class="round shadow">
    <?php if (!$isLogout): ?>
        <form method="post" name="frmLogin" id="frmLogin" action=""
              OnSubmit="sysPassUtil.Common.doLogin(); return false;">
            <div id="boxData">
                <?php if ($demoEnabled): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="user" name="user" type="text" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="80"
                               title="> demo <">
                        <label class="mdl-textfield__label"
                               for="user"><?php echo _('Usuario'); ?></label>
                    </div>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="pass" name="pass" type="password" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80"
                               title="> syspass <">
                        <label class="mdl-textfield__label"
                               for="pass"><?php echo _('Clave'); ?></label>
                    </div>

                    <span id="smpass" style="display: none">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mpass" name="mpass" type="password" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80" title="> 12345678900 <">
                        <label class="mdl-textfield__label"
                               for="mpass"><?php echo _('Clave Maestra'); ?></label>
                    </div>
                </span>
                <?php else: ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="user" name="user" type="text" class="mdl-textfield__input mdl-color-text--indigo-400" maxlength="80"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="user"><?php echo _('Usuario'); ?></label>
                    </div>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="pass" name="pass" type="password" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="pass"><?php echo _('Clave'); ?></label>
                    </div>

                    <span id="smpass" style="display: none">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mpass" name="mpass" type="password" class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="mpass"><?php echo _('Clave Maestra'); ?></label>
                    </div>
                </span>
                <?php endif; ?>
                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
                <?php if (is_array($getParams)): ?>
                    <?php foreach ($getParams as $param => $value): ?>
                        <input type="hidden" name="<?php echo $param; ?>" value="<?php echo $value; ?>"/>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
            <div id="boxButton">
                <button id="btnLogin" type="submit" form="frmLogin"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-button--colored mdl-color--indigo-500"
                        title="<?php echo _('Acceder'); ?>">
                    <i class="material-icons">play_arrow</i>
                </button>
            </div>
        </form>

        <!-- Close boxData -->
        <?php if ($mailEnabled): ?>
        <div id="boxActions">
            <a href="index.php?a=passreset"><?php echo _('¿Olvidó su clave?'); ?></a>
        </div>
    <?php endif; ?>
    <?php else: ?>
        <div id="boxLogout" class="round5"><?php echo _('Sesión finalizada'); ?></div>
        <script>
            window.onload = function () {
                $('#boxLogout').fadeOut(1500, function () {
                    location.href = 'index.php';
                });
            }
        </script>
    <?php endif; ?>
</div><!-- Close boxLogin -->

<?php if ($updated): ?>
    <div id="boxUpdated" class="round5"><?php echo _('Aplicación actualizada correctamente'); ?></div>
<?php endif; ?>

<?php if ($demoEnabled): ?>
    <div id="whatsNewIcon">
        <img src="imgs/gearscolorful.png" title="<?php echo _('Nuevas Características'); ?>"
             alt="<?php echo _('Nuevas Características'); ?>" onclick="$('#whatsNew').show(500);"/>

        <h2><?php echo _('Nuevas Características'); ?></h2>
    </div>
    <div id="whatsNew" class="round5 shadow">
        <ul>
            <?php foreach ($newFeatures as $feature): ?>
                <?php echo '<li>', $feature, '</li>'; ?>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
