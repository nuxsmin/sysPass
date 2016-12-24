            </div> <!-- Close container -->
            <footer>
                <div id="updates"></div>
                <div id="status">
                    <span class="status-info"><?php echo (DEBUG) ? 'Debug ON' : ''; ?></span>
                    <span class="status-info active-tooltip" title="<?php echo _('Indica si la conexión utiliza HTTPS.') . '<br><br>' . _('Las claves de formularios enviados se encriptan mediante PKI, el resto de datos no.'); ?>"><?php echo ($httpsEnabled) ? _('Seguro') : _('Inseguro'); ?></span>
                    <span class="status-info"><?php echo ($isDemoMode) ? 'Demo' : ''; ?></span>
                </div>
                <div id="project">
                    <a href="<?php echo $appInfo['appwebsite']; ?>" target="_blank"
                       title="<?php echo _('Ayuda :: FAQ :: Changelog'); ?>"><?php echo $appInfo['appname'], ' ', $appVersion; ?></a>
                    &nbsp;::&nbsp;
                    <a href="<?php echo $appInfo['appblog']; ?>" target="_blank"
                       title="<?php echo _('Un proyecto de cygnux.org'); ?>">cygnux.org</a>
                </div>
            <!-- Close Project -->
            </footer> <!-- Close footer -->
        </div> <!-- Close wrap -->