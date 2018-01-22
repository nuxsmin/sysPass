<?php

use SP\Util\ArrayUtil;

/** @var $icons \Theme\Icons */
/** @var $group \SP\DataModel\UserGroupData */
/** @var $groupUsers \SP\DataModel\UserToUserGroupData */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmGroups" id="frmGroups" class="form-action"
          data-onsubmit="appMgmt/save"
          data-route="<?php echo isset($route) ? $route : ''; ?>"
          data-activetab="<?php echo isset($activeTab) ? $activeTab : ''; ?>"
          data-nextaction="<?php echo $nextAction; ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Nombre'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="name" name="name" type="text" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $group->getName(); ?>">
                        <label class="mdl-textfield__label"
                               for="name"><?php echo __('Nombre del grupo'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Descripción'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="description" name="description" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50" value="<?php echo $group->getDescription(); ?>">
                        <label class="mdl-textfield__label"
                               for="description"><?php echo __('Descripción del grupo'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Usuarios'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Usuarios'); ?></div>

                    <select id="selUsers" name="users[]" multiple="multiple" class="select-box"
                            title="<?php echo __('Usuarios'); ?>" <?php echo $disabled; ?>>
                        <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem[] $users */
                        foreach ($users as $user): ?>
                            <option
                                    value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <?php if ($customFields): ?>
                <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php endif; ?>

            </tbody>
        </table>

        <input type="hidden" name="sk" value="">
        <input type="hidden" name="isAjax" value="1">
    </form>
    <?php if (!$isView): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    form="frmGroups" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>