<?php
use SP\Util\Checks;

/** @var $icons \Theme\Icons */
/** @var \SP\Account\AccountAcl $AccountAcl */
?>
<div class="action">
    <?php if ($AccountAcl->isShowDelete()): ?>
        <button id="btnDelete" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconDelete()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_DELETE; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/delete"
                title="<?php echo _('Eliminar Cuenta'); ?>">
            <i class="material-icons"><?php echo $icons->getIconDelete()->getIcon(); ?></i>
        </button>
    <?php endif; ?>

    <?php if ($AccountAcl->isShowLink() && $AccountAcl->isShowViewPass()): ?>
        <button id="btnLink" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPublicLink()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="link/save"
                title="<?php echo _('Crear Enlace Público'); ?>">
            <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
        </button>
    <?php endif; ?>

    <?php if ($AccountAcl->isShowViewPass()): ?>
        <button id="btnViewPass" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconViewPass()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                data-item-id="<?php echo $accountIsHistory !== 1 ? $accountId : $accountHistoryId; ?>"
                data-full="1"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/showpass"
                data-history="<?php echo $accountIsHistory; ?>"
                title="<?php echo _('Ver Clave'); ?>">
            <i class="material-icons"><?php echo $icons->getIconViewPass()->getIcon(); ?></i>
        </button>
        <?php if (!Checks::accountPassToImageIsEnabled()): ?>
            <button id="btnClipPass"
                    class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconClipboard()->getClassButton(); ?> clip-pass-button"
                    data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                    data-item-id="<?php echo $accountIsHistory !== 1 ? $accountId : $accountHistoryId; ?>"
                    data-full="0"
                    data-sk="<?php echo $sk; ?>"
                    data-history="<?php echo $accountIsHistory; ?>"
                    data-onclick="account/copypass"
                    title="<?php echo _('Copiar Clave en Portapapeles'); ?>">
                <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($AccountAcl->isShowEditPass()): ?>
        <button id="btnEditPass" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEditPass()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/editpass"
                title="<?php echo _('Modificar Clave de Cuenta'); ?>">
            <i class="material-icons"><?php echo $icons->getIconEditPass()->getIcon(); ?></i>
        </button>
    <?php endif; ?>

    <?php if ($gotData && $accountIsHistory): ?>
        <button id="btnBack" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/show"
                title="<?php echo _('Ver Actual'); ?>">
            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
        </button>
    <?php else: ?>
        <button id="btnBack" type="button"
                class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                title="<?php echo _('Atrás'); ?>">
            <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
        </button>
    <?php endif; ?>

    <?php if ($AccountAcl->isShowEdit()): ?>
        <button id="btnEdit" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEdit()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/edit"
                title="<?php echo _('Modificar Cuenta'); ?>">
            <i class="material-icons"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
        </button>
    <?php elseif (!$AccountAcl->isShowEdit() && $actionId == \SP\Core\ActionsInterface::ACTION_ACC_VIEW && Checks::mailrequestIsEnabled()): ?>
        <button id="btnRequest" type="button"
                class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEmail()->getClassButton(); ?>"
                data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_REQUEST; ?>"
                data-item-id="<?php echo $accountId; ?>"
                data-sk="<?php echo $sk; ?>"
                data-onclick="account/edit"
                title="<?php echo _('Solicitar Modificación'); ?>">
            <i class="material-icons"><?php echo $icons->getIconEmail()->getIcon(); ?></i>
        </button>
    <?php endif; ?>

    <?php if ($AccountAcl->isShowRestore()): ?>
        <button id="btnRestore" name="btnRestore" type="submit" form="frmAccountRestore"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconRestore()->getClassButton(); ?>"
                title="<?php echo _('Restaurar cuenta desde este punto'); ?>">
            <i class="material-icons"><?php echo $icons->getIconRestore()->getIcon(); ?></i>
        </button>
    <?php elseif ($AccountAcl->isShowSave()): ?>
        <button id="btnSave" name="btnSave" type="submit" form="frmAccount"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
        </button>
    <?php endif; ?>
</div>