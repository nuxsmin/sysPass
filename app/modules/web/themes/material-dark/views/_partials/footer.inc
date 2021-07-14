<?php
/**
 * @var callable $_getvar
 * @var Template $this
 */

use SP\Html\Assets\IconInterface;
use SP\Mvc\View\Template;

?>
<footer>
    <div id="footer-left" class="footer-parts">
        <?php if ($_getvar('loadApp') === true && $_getvar('ctx_userName')): ?>
            <div id="session">
                <span id="user-info">
                    <?php
                    /** @var IconInterface $ctx_userType */
                    $ctx_userType = $_getvar('ctx_userType');

                    if ($ctx_userType): ?>
                        <i id="user-type-footer"
                           class="material-icons"><?php echo $ctx_userType->getIcon(); ?></i>
                        <span for="user-type-footer"
                              class="mdl-tooltip mdl-tooltip--top"><?php echo $ctx_userType->getTitle(); ?></span>
                    <?php else: ?>
                        <i class="material-icons">face</i>
                    <?php endif; ?>
                    <span id="user-name-footer"><?php echo $_getvar('ctx_userName'); ?></span>
                    <span for="user-name-footer"
                          class="mdl-tooltip mdl-tooltip--top"><?php printf('%s : %s', __('Group'), $_getvar('ctx_userGroup')); ?></span>
                </span>
            </div>
        <?php endif; ?>
    </div>

    <div id="footer-right" class="footer-parts">
        <div id="status">
            <?php if ($_getvar('httpsEnabled')): ?>
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
                      class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Tells whether the connection uses HTTPS or not.') . '<br><br>' . __('Passwords sent from forms are encrypted using PKI, the remain data don\'t.'); ?></span>
            <?php endif; ?>

            <?php if (DEBUG): ?>
                <i class="material-icons mdl-color-text--red-900"
                   title="Debug ON">bug_report</i>
            <?php endif; ?>

            <?php if ($_getvar('isDemo')): ?>
                <span class="status-info">
                    <i class="material-icons mdl-color-text--teal-500"
                       title="<?php echo __('Demo'); ?>">play_circle_outline</i>
                </span>
            <?php endif; ?>

            <div id="updates"></div>
            <div id="notices"></div>
        </div>

        <div id="project">
            <a id="app-info" href="<?php echo $_getvar('app_website_url'); ?>"
               target="_blank">
                <?php echo $_getvar('app_name'), ' ', $_getvar('app_version'); ?>
            </a>
            <span for="app-info"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('Help :: FAQ :: Changelog'); ?></span>
            &nbsp;::&nbsp;
            <a id="app-project" href="<?php echo $_getvar('app_blog_url'); ?>"
               target="_blank">cygnux.org</a>
            <span for="app-project"
                  class="mdl-tooltip mdl-tooltip--top mdl-tooltip--large"><?php echo __('A cygnux.org project'); ?></span>
        </div>
        <!-- Close Project -->
    </div>
</footer> <!-- Close footer -->
