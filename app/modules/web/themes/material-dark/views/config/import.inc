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

<div id="title" class="titleNormal">
    <i class="material-icons">import_export</i>
    <?php echo __('CSV/XML Import'); ?>
</div>

<table class="data tblConfig">
    <tr>
        <td class="descField">
            <?php echo __('Default User'); ?>
            <div id="help-import_defaultuser"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-import_defaultuser">
                <p>
                    <?php echo __('Sets the default user for the accounts imported.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Default User'); ?></div>

            <select id="import_defaultuser" name="import_defaultuser"
                    class="select-box sel-chosen-user" required>
                <option value=""><?php echo __('Select User'); ?></option>
                <?php /** @var SelectItem $user */
                foreach ($_getvar('users') as $user): ?>
                    <option
                            value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>>
                        <?php echo $user->getName(); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Default Group'); ?>
            <div id="help-import_defaultgroup"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-import_defaultgroup">
                <p>
                    <?php echo __('Sets the default group for the accounts imported.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="lowres-title"><?php echo __('Default Group'); ?></div>

            <select id="import_defaultgroup" name="import_defaultgroup"
                    class="select-box sel-chosen-usergroup" required>
                <option value=""><?php echo __('Select Group'); ?></option>
                <?php /** @var SelectItem $userGroup */
                foreach ($_getvar('userGroups') as $userGroup): ?>
                    <option
                            value="<?php echo $userGroup->getId(); ?>" <?php echo $userGroup->isSelected() ? 'selected' : ''; ?>>
                        <?php echo $userGroup->getName(); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td class="valField" colspan="2">
            <div class="lowres-title"><?php echo __('File'); ?></div>

            <div id="drop-import-files" class="round active-tooltip dropzone"
                 title="<?php echo __('Drop files here or click to select'); ?>"
                 data-action-route="configImport/import">
                <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
            </div>
            <form method="post" enctype="multipart/form-data" name="upload_form"
                  id="fileUploadForm">
                <input type="file" id="inFile" multiple name="inFile"/>
            </form>
        </td>
    </tr>
</table>

<div class="tab-actions">
    <ul>
        <li>
            <button id="btnBack" type="button"
                    class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                    title="<?php echo __('Back'); ?>">
                <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
            </button>
        </li>
        <li>
            <button id="help_import_button"
                    class="btn-help mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                    title="<?php echo $icons->getIconHelp()->getTitle(); ?>"
                    data-help="help-import">
                <i class="material-icons"><?php echo $icons->getIconHelp()->getIcon(); ?></i>
            </button>

            <div for="help-import" class="help-box"
                 title="<?php echo __('CSV/XML Import'); ?>">
                <div class="help-container">
                    <p class="help-text"><?php echo __('Allows to import Accounts, Categories and Clients from XML or CSV files.'); ?></p>

                    <p class="help-text"><?php echo __('Supported XML file formats are: sysPass, KeePass and KeePassX'); ?></p>

                    <p class="help-text"><?php echo __('Drag & Drop the file into the import zone or click over the arrow.'); ?></p>

                    <p class="help-text"><?php echo __('For KeePass or KeePassX files, client name will be KeePass or KeePassX and category name like groups name.'); ?></p>

                    <p class="help-text"><?php echo __('The CSV import file requires the following format:'); ?></p>

                    <p class="help-text"><?php echo __('"account_name";"client";"category";"url";"user";"password";"notes"'); ?></p>

                    <p class="help-text"><?php echo __('In all cases, if the client or category do not exist they will be created automatically.'); ?></p>
                </div>
            </div>
        </li>
    </ul>
</div>

<div id="title" class="titleNormal">
    <?php echo __('XML'); ?>
</div>

<table class="data tblConfig">
    <tr>
        <td class="descField">
            <?php echo __('Import Password'); ?>
            <div id="help-import_pwd"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large" for="help-import_pwd">
                <p>
                    <?php echo __('It\'s only needed for sysPass XML files that have been encrypted.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="importPwd" name="importPwd" type="password"
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                       maxlength="255"/>
                <label class="mdl-textfield__label"
                       for="importPwd"><?php echo __('Import Password'); ?></label>
            </div>
        </td>
    </tr>
    <tr>
        <td class="descField">
            <?php echo __('Master Password'); ?>
            <div id="help-import_masterpwd"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-import_masterpwd">
                <p>
                    <?php echo __('It\'s only needed for sysPass XML files that have been encrypted with a different master password from the current one.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="importMasterPwd" name="importMasterPwd"
                       type="password"
                       class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input-show"
                       maxlength="255"/>
                <label class="mdl-textfield__label"
                       for="importMasterPwd"><?php echo __('Master Password'); ?></label>
            </div>
        </td>
    </tr>
</table>

<div id="title" class="titleNormal">
    <?php echo __('CSV'); ?>
</div>

<table class="data tblConfig">
    <tr>
        <td class="descField">
            <?php echo __('CSV delimiter'); ?>
            <div id="help-import_csvdelimiter"
                 class="icon material-icons <?php echo $icons->getIconHelp()->getClass(); ?>"><?php echo $icons->getIconHelp()->getIcon(); ?></div>
            <div class="mdl-tooltip mdl-tooltip--large"
                 for="help-import_csvdelimiter">
                <p>
                    <?php echo __('It\'s only needed for CSV files.'); ?>
                </p>
            </div>
        </td>
        <td class="valField">
            <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                <input id="csvDelimiter" name="csvDelimiter" type="text"
                       class="mdl-textfield__input mdl-color-text--indigo-400"
                       maxlength="1" value=";"/>
                <label class="mdl-textfield__label"
                       for="csvDelimiter"><?php echo __('CSV delimiter'); ?></label>
            </div>
        </td>
    </tr>
</table>
