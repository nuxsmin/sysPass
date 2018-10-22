<?php
/**
 * @var  \SP\DataModel\AuthTokenData $authToken
 * @var \SP\Core\UI\ThemeIcons       $icons
 * @var \SP\Config\ConfigData        $configData
 * @var callable                     $_getvar
 * @var \SP\Mvc\View\Template        $this
 */

$authToken = $_getvar('authToken');
?>
<div id="box-popup">
    <h2 class="center"><?php echo $_getvar('header'); ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="frmTokens" id="frmTokens" class="form-action"
          data-onsubmit="appMgmt/save"
          data-action-route="<?php echo $_getvar('route', ''); ?>"
          data-hash="">
        <table class="popup-data">
            <tbody>
            <tr>
                <td class="descField"><?php echo __('Usuario'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Usuario'); ?></div>

                    <select id="selUsers" name="users" class="select-box" required <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Seleccionar Usuario'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $user */
                        foreach ($_getvar('users') as $user): ?>
                            <option value="<?php echo $user->getId(); ?>" <?php echo $user->isSelected() ? 'selected' : ''; ?>><?php printf('%s (%s)', $user->getName(), $user->getItemProperty('login')); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="descField"><?php echo __('Acción'); ?></td>
                <td class="valField">
                    <div class="lowres-title"><?php echo __('Acción'); ?></div>

                    <select id="selActions" name="actions"
                            class="select-box" required <?php echo $_getvar('disabled'); ?>>
                        <option value=""><?php echo __('Seleccionar Acción'); ?></option>
                        <?php /** @var \SP\Mvc\View\Components\SelectItem $action */
                        foreach ($_getvar('actions') as $action): ?>
                            <option value="<?php echo $action->getId(); ?>" <?php echo $action->isSelected() ? 'selected' : ''; ?>><?php echo $action->getName(); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <?php if (!$_getvar('isView')): ?>
                <tr>
                    <td class="descField"><?php echo __('Clave'); ?></td>
                    <td class="valField">
                        <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                            <input id="pass" name="pass" type="password"
                                   class="mdl-textfield__input passwordfield__input mdl-color-text--indigo-400"
                                   maxlength="50">
                            <label class="mdl-textfield__label"
                                   for="pass"><?php echo __('Clave'); ?></label>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="descField"><?php echo __('Opciones'); ?></td>
                    <td class="valField">
                        <label class="mdl-switch mdl-js-switch mdl-js-ripple-effect" for="refreshtoken"
                               title="<?php echo __('Regenerar Autorización'); ?>">
                            <input type="checkbox" id="refreshtoken"
                                   class="mdl-switch__input mdl-color-text--indigo-400"
                                   name="refreshtoken"/>
                            <span class="mdl-switch__label"><?php echo __('Regenerar Autorización'); ?></span>
                        </label>
                    </td>
                </tr>
            <?php else: ?>
                <tr>
                    <td class="descField"><?php echo __('Token'); ?></td>
                    <td class="valField">
                        <div class="lowres-title"><?php echo __('Token'); ?></div>

                        <?php echo $authToken->getToken(); ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <input type="hidden" name="isAjax" value="1">
    </form>
    <div class="action-in-box">
        <button
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                form="frmTokens" title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>