<?php
/**
 * @var  CategoryData $category
 * @var ThemeIcons    $icons
 * @var ConfigData    $configData
 * @var callable      $_getvar
 * @var Template      $this
 */

use SP\Config\ConfigData;
use SP\Core\UI\ThemeIcons;
use SP\DataModel\CategoryData;
use SP\Mvc\View\Template;

$category = $_getvar('category');
?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i
                class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmCategories" id="frmCategories"
          class="form-action"
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
                               value="<?php echo $category->getName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Category name'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Description'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50"
                               value="<?php echo $category->getDescription(); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo __('Category description'); ?></label>
                    </div>
                </td>
            </tr>
            <?php if ($_getvar('customFields')): ?>
                <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmCategories"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>
