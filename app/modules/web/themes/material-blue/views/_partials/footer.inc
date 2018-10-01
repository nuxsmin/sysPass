<footer>
    <div id="footer-left" class="footer-parts">
        <?php if ($loadApp === true && isset($ctx_userName)): ?>
            <div id="session">
                <span id="user-info">
                    <?php if ($ctx_userType !== null): ?>
                        <i id="user-type-footer" class="material-icons"><?php echo $ctx_userType->getIcon(); ?></i>
                        <span for="user-type-footer"
                              class="mdl-tooltip mdl-tooltip--top"><?php echo $ctx_userType->getTitle(); ?></span>
                    <?php else: ?>
                        <i class="material-icons">face</i>
                    <?php endif; ?>
                    <span id="user-name-footer"><?php echo $ctx_userName; ?></span>
                    <span for="user-name-footer"
                          class="mdl-tooltip mdl-tooltip--top"><?php printf('%s : %s', __('Grupo'), $ctx_userGroup); ?></span>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div id="footer-right" class="footer-parts">
        <div id="status">
            <?php if ($httpsEnabled): ?>
                <span id="secure-info" class="status-info">
                    <i class="material-icons mdl-color-text--teal-500">https</i>
                </span>
                <span for="secure-info"
                      class="mdl-tooltip mdl-tooltip--top"><?php echo 'HTTPS'; ?></span>
            <?php else: ?>
                <span id="secure-info" class="status-info">
                    <i class="material-icons mdl-color-text--red-900">http</i>
                </span>
                <span for="secure-info"
                      class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Indica si la conexión utiliza HTTPS.') . '<br><br>' . __('Las claves de formularios enviados se encriptan mediante PKI, el resto de datos no.'); ?></span>
            <?php endif; ?>

            <?php if (DEBUG): ?>
                <i class="material-icons mdl-color-text--red-900" title="Debug ON">bug_report</i>
            <?php endif; ?>

            <?php if ($isDemoMode): ?>
                <span class="status-info">
                    <i class="material-icons mdl-color-text--teal-500" title="<?php echo __('Demo'); ?>">play_circle_outline</i>
                </span>
            <?php endif; ?>

            <div id="updates"></div>
            <div id="notices"></div>
        </div>

        <div id="project">
            <a id="app-info" href="<?php echo $app_website_url; ?>" target="_blank">
                <?php echo $app_name, ' ', $app_version; ?>
            </a>
            <span for="app-info"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Ayuda :: FAQ :: Changelog'); ?></span>
            &nbsp;::&nbsp;
            <a id="app-project" href="<?php echo $app_blog_url; ?>" target="_blank">cygnux.org</a>
            <span for="app-project"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Un proyecto de cygnux.org'); ?></span>
        </div>
        <!-- Close Project -->
    </div>
</footer> <!-- Close footer -->
