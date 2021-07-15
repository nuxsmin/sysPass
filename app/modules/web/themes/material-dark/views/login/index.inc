<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Template;

?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div id="box-login" class="box-form round">
        <form method="post" name="frmLogin" id="frmLogin" class="form-action"
              data-onsubmit="main/login"
              data-route="login/login">
            <fieldset id="box-data">
                <div class="form-control">
                    <i class="material-icons">perm_identity</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="user" name="user" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="80" autocomplete="off" autofocus>
                        <label class="mdl-textfield__label"
                               for="user"><?php echo __('User'); ?></label>
                    </div>
                </div>

                <div class="form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="pass" name="pass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="pass"><?php echo __('Password'); ?></label>
                    </div>
                </div>

                <div id="soldpass" class="extra-hidden form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="oldpass" name="oldpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="oldpass"><?php echo __('Previous Password'); ?></label>
                    </div>
                </div>

                <div id="smpass" class="extra-hidden form-control">
                    <i class="material-icons">vpn_key</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="mpass" name="mpass" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="255" autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="mpass"><?php echo __('Master Password'); ?></label>
                    </div>
                </div>

                <?php if ($_getvar('from') && $_getvar('from_hash')): ?>
                    <input type="hidden" name="from"
                           value="<?php echo $_getvar('from'); ?>"/>
                    <input type="hidden" name="h"
                           value="<?php echo $_getvar('from_hash'); ?>"/>
                <?php endif; ?>

                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="isAjax" value="1"/>
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            </fieldset>
            <div id="box-buttons">
                <button id="btnLogin" type="submit" form="frmLogin"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-js-ripple-effect mdl-button--colored mdl-color--indigo-400"
                        title="<?php echo __('Sign in'); ?>">
                    <i class="material-icons">play_arrow</i>
                </button>
            </div>
        </form>

        <!-- Close boxData -->
        <?php if ($_getvar('mailEnabled')): ?>
            <div id="box-actions">
                <a href="index.php?r=userPassReset"><?php echo __('Forgot password?'); ?></a>
            </div>
        <?php endif; ?>
    </div><!-- Close boxLogin -->

    <?php if ($_getvar('updated')): ?>
        <div id="box-updated"
             class="round5"><?php echo __('Application successfully updated'); ?></div>
    <?php endif; ?>

    <?php if ($_getvar('isDemo')): ?>
        <div id="demo-info">
            <ul>
                <li title="<?php echo __('User'); ?>">
                    <i class="material-icons">perm_identity</i>
                    <span>demo</span>
                </li>
                <li title="<?php echo __('Password'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>syspass</span>
                </li>
                <li title="<?php echo __('Master Password'); ?>">
                    <i class="material-icons">vpn_key</i>
                    <span>12345678900</span>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>