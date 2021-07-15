<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */
?>
<main class="mdl-layout__content">
    <div id="actions" class="upgrade">
        <div id="page-title">
            <h1><?php use SP\Config\ConfigData;
                use SP\Core\UI\ThemeIcons;
                use SP\Mvc\View\Template;
                use SP\Util\VersionUtil;

                printf(__('Update %s'), $_getvar('upgradeVersion')); ?></h1>
        </div>

        <form id="frmUpgrade" method="get" class="form-action"
              data-onsubmit="main/upgrade">
            <fieldset>
                <?php if ($_getvar('type') === 'db'): ?>
                    <legend><?php echo __('DB Update'); ?></legend>
                <?php elseif ($_getvar('type') === 'app'): ?>
                    <legend><?php echo __('Application Update'); ?></legend>
                <?php endif; ?>

                <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                    <input id="h" name="h" type="text" required
                           class="mdl-textfield__input mdl-color-text--indigo-400"
                           autocomplete="off" autofocus>
                    <label class="mdl-textfield__label"
                           for="h"><?php echo __('Security Code'); ?></label>
                </div>

                <div id="help-code"
                     class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                    <?php echo $icons->getIconHelp()->getIcon(); ?>
                </div>
                <div class="mdl-tooltip mdl-tooltip--large" for="help-code">
                    <?php echo __('This code is set in sysPass config file within the tag name "upgradekey"'); ?>
                </div>

                <?php if (VersionUtil::checkVersion($_getvar('version'), '130.16011001')
                    && count($_getvar('constraints')) > 0):
                    ?>
                    <div>
                        <ul class="errors">
                            <li class="msg-warning">
                                <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                                <?php echo __('Some orphaned items have been found. Please, modify those items or enter the default IDs for them.'); ?>
                                <br>
                                <?php echo __('If items IDs are not set, they will be created.'); ?>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <ul class="errors">
                            <?php foreach ($_getvar('constraints') as $msg): ?>
                                <li class="msg-warning"><?php echo $msg; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <?php
                    $checkConstraints = $_getvar('checkConstraints');

                    if ($checkConstraints->accounts_user > 0
                        || $checkConstraints->accounts_useredit > 0
                        || $checkConstraints->accountshistory_user > 0
                        || $checkConstraints->accountshistory_useredit > 0
                    ): ?>
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="userid" name="userid" type="number"
                                   class="mdl-textfield__input mdl-color-text--indigo-400"
                                   autocomplete="off" min="0" max="1000"
                                   value="0">
                            <label class="mdl-textfield__label"
                                   for="userid"><?php echo __('Enter a valid user ID for the accounts'); ?></label>
                        </div>
                    <?php endif; ?>

                    <?php if ($checkConstraints->accounts_category > 0
                    || $checkConstraints->accountshistory_category > 0): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="categoryid" name="categoryid" type="number"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" min="0" max="1000" value="0">
                        <label class="mdl-textfield__label"
                               for="categoryid"><?php echo __('Enter a valid category ID for the accounts'); ?></label>
                    </div>
                <?php endif; ?>

                    <?php if ($checkConstraints->accounts_customer > 0
                    || $checkConstraints->accountshistory_customer > 0): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="customerid" name="customerid" type="number"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" min="0" max="1000" value="0">
                        <label class="mdl-textfield__label"
                               for="customerid"><?php echo __('Enter a valid client ID for the accounts'); ?></label>
                    </div>
                <?php endif; ?>

                    <?php if ($checkConstraints->users_group > 0
                    || $checkConstraints->accounts_group > 0
                    || $checkConstraints->accountshistory_group > 0): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="groupid" name="groupid" type="number"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" min="0" max="1000" value="0">
                        <label class="mdl-textfield__label"
                               for="groupid"><?php echo __('Enter a valid group ID for the users'); ?></label>
                    </div>
                <?php endif; ?>

                    <?php if ($checkConstraints->users_profile > 0): ?>
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="profileid" name="profileid" type="number"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off" min="0" max="1000" value="0">
                        <label class="mdl-textfield__label"
                               for="profileid"><?php echo __('Enter a valid profile ID for the users'); ?></label>
                    </div>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (VersionUtil::checkVersion($_getvar('version'), '210.17022601')): ?>
                    <div>
                        <ul class="errors">
                            <li class="msg-warning">
                                <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                                <?php echo __('This update uses a new encryption schema, so it will be needed to reencrypt the whole encrypted data.'); ?>
                                <br>
                                <strong><?php printf(__('It will be updated %s accounts. This process could take some time long.'), $_getvar('numAccounts')); ?></strong>
                                <br>
                                <?php printf(__('You could get more info on: %s'), '<a target="_blank" href="https://doc.syspass.org">https://doc.syspass.org</a>'); ?>
                            </li>
                        </ul>
                    </div>

                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="masterkey" name="masterkey" type="password"
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                               maxlength="255" required/>
                        <label class="mdl-textfield__label"
                               for="masterkey"><?php echo __('Current Master Password'); ?></label>
                    </div>

                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="userlogin" name="userlogin" type="text"
                               required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               autocomplete="off">
                        <label class="mdl-textfield__label"
                               for="userlogin"><?php echo __('Enter a valid user login'); ?></label>
                    </div>

                    <?php if ($_getvar('numAccounts') >= 500): ?>
                        <input type="hidden" name="useTask" value="1">
                        <input type="hidden" name="taskId"
                               value="<?php echo $_getvar('taskId'); ?>">
                        <input type="hidden" name="lock" value="upgrade">
                    <?php endif; ?>
                <?php endif; ?>

                <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                       for="chkConfirm">
                    <input type="checkbox" id="chkConfirm"
                           class="mdl-checkbox__input" name="chkConfirm">
                    <span class="mdl-checkbox__label"><?php echo __('I\'ve done a full sysPass backup'); ?></span>
                </label>

                <input type="hidden" name="a"
                       value="<?php echo $_getvar('action'); ?>">
                <input type="hidden" name="type"
                       value="<?php echo $_getvar('type'); ?>">
                <input type="hidden" name="version"
                       value="<?php echo $_getvar('version'); ?>">
                <input type="hidden" name="upgrade" value="1">
                <input type="hidden" name="isAjax" value="1">
            </fieldset>

            <div>
                <ul class="errors">
                    <li class="msg-warning">
                        <i class="icon material-icons <?php echo $icons->getIconWarning()->getClass(); ?>"><?php echo $icons->getIconWarning()->getIcon(); ?></i>
                        <?php echo __('Please, wait while the process is running'); ?>
                    </li>
                </ul>
            </div>

            <div class="buttons">
                <button id="btnChange"
                        class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                        type="submit">
                    <?php echo __('Update'); ?>
                    <i class="material-icons"
                       title="<?php echo __('Start Update'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
                </button>
            </div>
        </form>
    </div>
</main>
