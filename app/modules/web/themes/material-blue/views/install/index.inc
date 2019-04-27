<?php
/**
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\Mvc\View\Components\SelectItem;
use SP\Mvc\View\Template;

?>
<div id="actions" class="installer" align="center">
    <div id="page-title">
        <h1><?php printf(__('Installation %s'), $_getvar('app_version')); ?></h1>
    </div>

    <?php include $this->includePartial('error-list'); ?>

    <form id="frmInstall" class="form-action" data-onsubmit="main/install"
          data-route="install/install">
        <input type="hidden" name="install" value="true"/>
        <input type="hidden" name="isAjax" value="1"/>

        <fieldset id="adminaccount">
            <legend><?php echo __('sysPass Admin'); ?></legend>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="adminlogin" name="adminlogin" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="admin" maxlength="80" autocomplete="off"
                       tabindex="0"
                       autofocus>
                <label class="mdl-textfield__label"
                       for="adminlogin"><?php echo __('sysPass admin user'); ?></label>
            </div>

            <div id="help-login"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                <?php echo $icons->getIconHelp()->getIcon(); ?>
            </div>
            <div class="mdl-tooltip" for="help-login">
                <?php echo __('sysPass administrator\'s login'); ?>
            </div>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="adminpass" name="adminpass" type="password" required
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                       value="" maxlength="255" autocomplete="off">
                <label class="mdl-textfield__label"
                       for="adminlogin"><?php echo __('Password'); ?></label>
            </div>
        </fieldset>

        <fieldset id="masterpwd">
            <legend><?php echo __('Master Password'); ?></legend>
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="masterpassword" name="masterpassword" type="password"
                       required
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                       value="" maxlength="255" autocomplete="off">
                <label class="mdl-textfield__label"
                       for="masterpassword"><?php echo __('Master Password'); ?></label>
            </div>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="masterpasswordR" name="masterpasswordr"
                       type="password" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="" maxlength="255" autocomplete="off">
                <label class="mdl-textfield__label"
                       for="masterpasswordR"><?php echo __('Password (repeat)'); ?></label>
            </div>
        </fieldset>

        <fieldset id='databaseField'>
            <legend><?php echo __('DB Configuration'), ' (MySQL)'; ?></legend>

            <input type='hidden' id='hasMySQL' value='true'/>
            <input type="hidden" id="dbtype" name="dbtype" value="mysql"/>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="dbuser" name="dbuser" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="root" autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="dbuser"><?php echo __('DB access user'); ?></label>
            </div>

            <div id="help-dblogin"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                <?php echo $icons->getIconHelp()->getIcon(); ?>
            </div>
            <div class="mdl-tooltip" for="help-dblogin">
                <?php echo __('An user with MySQL admin rights'); ?>
            </div>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="dbpass" name="dbpass" type="password" required
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                       value="" autocomplete="off" autofocus>
                <label class="mdl-textfield__label"
                       for="dbpass"><?php echo __('DB access password'); ?></label>
            </div>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="dbname" name="dbname" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="syspass" autocomplete="off" autofocus
                       pattern="[0-9a-zA-Z$_-]+">
                <label class="mdl-textfield__label"
                       for="dbuser"><?php echo __('sysPass database name'); ?></label>
            </div>

            <div id="help-dbname"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                <?php echo $icons->getIconHelp()->getIcon(); ?>
            </div>
            <div class="mdl-tooltip" for="help-dbname">
                <?php echo __('sysPass database name'); ?>
            </div>

            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="dbhost" name="dbhost" type="text" required
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       value="localhost" autocomplete="off" autofocus
                       pattern="([0-9a-zA-Z._-]+|([0-9]{1,3}.)+)(:[0-9]{1,5})?|unix:.*">
                <label class="mdl-textfield__label"
                       for="dbhost"><?php echo __('sysPass database server'); ?></label>
            </div>

            <div id="help-dbhost"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                <?php echo $icons->getIconHelp()->getIcon(); ?>
            </div>
            <div class="mdl-tooltip" for="help-dbhost">
                <?php echo __('Server name to install sysPass database'); ?>
            </div>
        </fieldset>

        <fieldset id="site">
            <legend><?php echo __('General'); ?></legend>

            <div class="form-field">
                <label for="sel-sitelang"><?php echo __('Language'); ?></label>
                <select name="sitelang" id="sel-sitelang" size="1"
                        class="select-box"
                        title="<?php echo __('Language'); ?>">
                    <?php /** @var SelectItem $lang */
                    foreach ($_getvar('langs') as $lang): ?>
                        <option value="<?php echo $lang->getId(); ?>" <?php echo $lang->isSelected() ? 'selected' : ''; ?>><?php echo $lang->getName(); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-field">
                <label for="hostingmode">
                    <?php echo __('Hosting Mode'); ?>
                    <div id="help-hostingmode"
                         class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>">
                        <?php echo $icons->getIconHelp()->getIcon(); ?>
                    </div>

                    <div class="mdl-tooltip" for="help-hostingmode">
                        <?php echo __('It does not create or verify the user\'s permissions on the DB'); ?>
                    </div>
                </label>

                <div>
                    <label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"
                           for="hostingmode">
                        <input type="checkbox" id="hostingmode"
                               class="mdl-checkbox__input" name="hostingmode">
                    </label>
                </div>
            </div>
        </fieldset>

        <div class="buttons">
            <button class="mdl-button mdl-js-button mdl-button--raised mdl-button--accent"
                    type="submit">
                <?php echo __('Install'); ?>
                <i class="material-icons"
                   title="<?php echo __('Install'); ?>"><?php echo $icons->getIconPlay()->getIcon(); ?></i>
            </button>
        </div>
    </form>
</div>
