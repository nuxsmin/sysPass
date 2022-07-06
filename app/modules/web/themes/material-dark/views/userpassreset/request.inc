<?php /** @var $icons ThemeIcons */

use SP\Core\UI\ThemeIcons; ?>

<div id="box-pub-noheader">
    <div class="box-spacer"></div>
    <div class="box-header">
        <?php echo __('Request Password Change'); ?>
    </div>
    <div id="box-passreset" class="box-form round">
        <form id="frmPassReset" action="" method="post" class="form-action"
              data-onsubmit="user/passreset"
              data-action-route="userPassReset/saveRequest">
            <fieldset id="box-data">
                <div class="form-control">
                    <i class="material-icons">perm_identity</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="login" name="login" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" autofocus>
                        <label class="mdl-textfield__label"
                               for="login"><?php echo __('User'); ?></label>
                    </div>
                </div>
                <div class="form-control">
                    <i class="material-icons">email</i>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="email" name="email" type="email" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="email"><?php echo __('User\'s Email'); ?></label>
                    </div>
                </div>
                <input type="hidden" name="isAjax" value="1">
                <input type="hidden" name="sk" value="<?php echo $sk; ?>">
            </fieldset>

            <div id="box-buttons">
                <button id="btnBack" type="button"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <i class="material-icons"
                       title="<?php echo __('Go back to login'); ?>"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                    <?php echo __('Back'); ?>
                </button>

                <button id="btnRequest"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent">
                    <?php echo __('Request'); ?>
                    <i class="material-icons"
                       title="<?php echo __('Request'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</div>
