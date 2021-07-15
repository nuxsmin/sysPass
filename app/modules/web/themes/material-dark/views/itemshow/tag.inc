<?php
/**
 * @var TagData    $tag
 * @var ThemeIcons $icons
 * @var ConfigData $configData
 * @var callable   $_getvar
 * @var Template   $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\TagData;
use SP\Mvc\View\Template;

$tag = $_getvar('tag');
?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmTags" id="frmTags" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Name'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $tag->getName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Tag name'); ?></label>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmTags"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
