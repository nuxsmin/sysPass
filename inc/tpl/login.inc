    <div id="boxLogin" class="round shadow">
        <div id="boxLogo"><img id="imgLogo" src="imgs/logo.png" title="sysPass"/></div>
        <div id="boxData">
            <form method="post" name="frmLogin" id="frmLogin" action="" OnSubmit="return doLogin();">
                <?php if ($demoEnabled): ?>
                    <input type="text" name="user" id="user" class="round5" placeholder="<?php echo _('Usuario'); ?>"
                           value=""
                           title="> demo <"/><br/>
                    <input type="password" name="pass" id="pass" class="round5" placeholder="<?php echo _('Clave'); ?>"
                           value=""
                           title="> syspass <"/><br/>
                    <span id="smpass" style="display: none">
                        <input type="password" name="mpass" id="mpass" class="round5"
                               placeholder="<?php echo _('Clave Maestra'); ?>"
                               value="" title="> 01234567890 <" disabled/>
                        <br/>
                    </span>
                <?php else: ?>
                    <input type="text" name="user" id="user" class="round5" placeholder="<?php echo _('Usuario'); ?>"
                           value=""/><br/>
                    <input type="password" name="pass" id="pass" class="round5" placeholder="<?php echo _('Clave'); ?>"
                           value=""
                           autocomplete="off"/><br/>
                    <span id="smpass" style="display: none">
                        <input type="password" name="mpass" id="mpass" class="round5"
                               placeholder="<?php echo _('Clave Maestra'); ?>"
                               value="" autocomplete="off" disabled/>
                        <br/>
                    </span>
                <?php endif; ?>
                <input type="image" id="btnLogin" src="imgs/login.png" title="<?php echo _('Acceder') ?>"/>
                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
                <?php if (count($_GET) > 0): ?>
                    <?php foreach ($_GET as $param => $value): ?>
                        <input type="hidden" name="g_<?php echo SP_Html::sanitize($param); ?>"
                               value="<?php echo SP_Html::sanitize($value); ?>"/>
                    <?php endforeach; ?>
                <?php endif; ?>
            </form>
        </div>
        <!-- Close boxData -->
        <?php if ($mailEnabled): ?>
            <div id="boxActions">
                <a href="index.php?a=passreset"><?php echo _('¿Olvidó su clave?'); ?></a>
            </div>
        <?php endif; ?>
    </div><!-- Close boxLogin -->

<?php if ($isLogout): ?>
    <div id="boxLogout" class="round5"><?php echo _('Sesión finalizada'); ?></div>
    <script>$('#boxLogout').fadeOut(1500, function () {
            location.href = 'index.php';
        });</script>
<?php endif; ?>

<?php if ($updated): ?>
    <div id="boxUpdated" class="round5"><?php echo _('Aplicación actualizada correctamente'); ?></div>
<?php endif; ?>

<?php if ($demoEnabled): ?>
<div id="whatsNewIcon">
    <img src="imgs/gearscolorful.png" title="<?php echo _('Nuevas Características'); ?>" alt="<?php echo _('Nuevas Características'); ?>" onclick="$('#whatsNew').show(500);"/>
    <h2><?php echo _('Nuevas Características'); ?></h2>
</div>
<div id="whatsNew" class="round5 shadow">
    <ul>
    <?php foreach ($newFeatures as $feature): ?>
        <?php echo '<li>', $feature , '</li>'; ?>
    <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>