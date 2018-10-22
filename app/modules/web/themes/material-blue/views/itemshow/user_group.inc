<?php
/**
 * @var \SP\DataModel\UserToUserGroupData $groupUsers
 * @var \SP\DataModel\UserGroupData       $group
 * @var \SP\Core\UI\ThemeIcons            $icons
 * @var \SP\Config\ConfigData             $configData
 * @var callable                          $_getvar
 * @var \SP\Mvc\View\Template             $this
 */

$group = $_getvar('group');
?>

<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmGroups" id="frmGroups" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
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

                    <?php if (!$_getvar('isView')): ?>
                        <select id="selUsers" name="users[]" multiple="multiple" class="select-box"
                                title="<?php echo __('Usuarios'); ?>" <?php echo $_getvar('disabled'); ?>>
                            <option value=""><?php echo __('Seleccionar Usuarios'); ?></option>
                            <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                            foreach ($_getvar('users') as $user): ?>
                                <option
                                        value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php echo $user->getName(); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="tag-list-box">
                            <div class="tag-list-body">
                                <div class="tags-list-items">
                                    <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                                    foreach ($_getvar('users') as $user): ?>
                                        <?php if ($user->isSelected()): ?>
                                            <span class="tag"><?php echo $user->getName(); ?></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>

            <?php if ($_getvar('customFields')): ?>
                <?php include $this->includeTemplate('aux-customfields', 'common'); ?>
            <?php endif; ?>

            <?php if ($_getvar('usedBy')): ?>
                <tr>
                    <td class="descField"><?php echo __('Usado por'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Usado por'); ?></div>
                        <div class="list-wrap">
                            <ul class="mdl-list">
                                <?php /** @var stdClass $user */
                                foreach ($_getvar('usedBy') as $user): ?>
                                    <li class="mdl-list__item mdl-list__item">
                                        <span class="mdl-list__item-primary-content">
                                            <i class="material-icons mdl-list__item-icon"
                                               title="<?php echo __('Usuario'); ?>">person</i>
                                            <?php if ($user->ref === 'UserGroup'): ?>
                                                <?php printf('%s (%s)*', $user->name, $user->login); ?>
                                            <?php else: ?>
                                                <?php printf('%s (%s)', $user->name, $user->login); ?>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div><?php echo __('(*) Incluido en grupo'); ?></div>
                    </td>
                </tr>
            <?php endif; ?>

            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <?php if (!$_getvar('isView')): ?>
        <div class="action-in-box">
            <button
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                    form="frmGroups" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
                <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
            </button>
        </div>
    <?php endif; ?>
</div>
