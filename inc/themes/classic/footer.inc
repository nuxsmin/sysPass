            </div> <!-- Close container -->
            <footer>
                <div id="updates"></div>
                <div id="status">
                    <?php echo (DEBUG) ? 'Debug ON' : ''; ?>
                    <?php echo ($isDemoMode) ? 'Demo' : ''; ?>
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
    </body> <!-- Close BODY...almost done..go..go..go -->
</html> <!-- Close HTML...ufff too much work!! :)) -->
<!-- Insert coin . . . -->