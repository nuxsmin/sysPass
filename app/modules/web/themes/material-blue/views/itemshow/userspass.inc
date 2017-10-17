<?php
/** @var $icons \Theme\Icons */
/** @var $user \SP\DataModel\UserData */
?>

<div id="box-popup">
    <h2 class="center"><?php echo $header; ?><i class="btn-popup-close material-icons">close</i></h2>

    <form method="post" name="updUsrPass" id="frmUpdUsrPass" class="form-action" data-onsubmit="appMgmt/save" data-hash="">
        <table class="popup-data">
            <tr>
                <td class="descField"><?php echo __('Nombre') ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="name" name="name" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $user->getUserName(); ?>" readonly disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Login'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield">
                        <input id="login" name="login" type="text"
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               value="<?php echo $user->getUserLogin(); ?>"
                               readonly disabled/>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Clave'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="fancypass" name="pass" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400 passwordfield__input"
                               maxlength="50">
                        <label class="mdl-textfield__label" for="userpass"><?php echo __('Clave'); ?></label>
                    </div>
                </td>
            </tr>

            <tr>
                <td class="descField"><?php echo __('Clave (repetir)'); ?></td>
                <td class="valField">
                    <div class="mdl-textfield mdl-js-textfield mdl-textfield--floating-label">
                        <input id="fancypassR" name="passR" type="password" required
                               class="mdl-textfield__input mdl-color-text--indigo-400"
                               maxlength="50">
                        <label class="mdl-textfield__label"
                               for="userpassR"><?php echo __('Clave (repetir)'); ?></label>
                    </div>
                </td>
            </tr>
        </table>
        <input type="hidden" name="itemId" value="<?php echo $user->getId(); ?>"/>
        <input type="hidden" name="actionId" value="<?php echo $actionId; ?>"/>
        <input type="hidden" name="sk" value="<?php echo $sk; ?>">
    </form>

    <div class="action-in-box">
        <button form="frmUpdUsrPass"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo $icons->getIconSave()->getTitle(); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    </div>
</div>

<script type="text/javascript">
    sysPassApp.sk.set("<?php echo $sk; ?>");
</script>