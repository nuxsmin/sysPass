<?php
use SP\Util\Checks;

/** @var $icons \Theme\Icons */
/** @var \SP\Account\AccountAcl $AccountAcl */
?>
<div class="item-actions">
    <ul>
        <?php if ($gotData && $accountIsHistory): ?>
            <li>
                <button id="btnBack" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/show"
                        title="<?php echo __('Ver Actual'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
        <?php else: ?>
            <li>
                <button id="btnBack" type="button"
                        class="btn-back mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconBack()->getClassButton(); ?>"
                        title="<?php echo __('Atrás'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconBack()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowDelete()): ?>
            <li>
                <button id="btnDelete" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconDelete()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_DELETE; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/delete"
                        title="<?php echo __('Eliminar Cuenta'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconDelete()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowLink() && $AccountAcl->isShowViewPass() && $accountData->getAccountParentId() === 0 && $accountIsHistory !== 1): ?>
            <?php if ($publicLinkUrl === null): ?>
                <li>
                    <button id="btnLink" type="button"
                            class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPublicLink()->getClassButton(); ?>"
                            data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW; ?>"
                            data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                            data-item-id="<?php echo $accountId; ?>"
                            data-sk="<?php echo $sk; ?>"
                            data-onclick="link/save"
                            title="<?php echo __('Crear Enlace Público'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                    </button>
                </li>
            <?php else: ?>
                <li>
                    <button id="btnLink" type="button"
                            class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconPublicLink()->getClassButton(); ?>"
                            data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_REFRESH; ?>"
                            data-nextaction-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>"
                            data-item-id="<?php echo $publicLinkId; ?>"
                            data-activetab="<?php echo $accountId; ?>"
                            data-sk="<?php echo $sk; ?>"
                            data-onclick="link/refresh"
                            title="<?php echo __('Actualizar Enlace'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconPublicLink()->getIcon(); ?></i>
                    </button>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowViewPass()): ?>
            <li>
                <button id="btnViewPass" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconViewPass()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                        data-item-id="<?php echo $accountIsHistory !== 1 ? $accountId : $accountHistoryId; ?>"
                        data-parent-id="<?php echo $accountData->getAccountParentId(); ?>"
                        data-full="1"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/showpass"
                        data-history="<?php echo $accountIsHistory; ?>"
                        title="<?php echo __('Ver Clave'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconViewPass()->getIcon(); ?></i>
                </button>
            </li>
            <?php if (!Checks::accountPassToImageIsEnabled()): ?>
                <li>
                    <button id="btnClipPass"
                            class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconClipboard()->getClassButton(); ?> clip-pass-button"
                            data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW_PASS; ?>"
                            data-item-id="<?php echo $accountIsHistory !== 1 ? $accountId : $accountHistoryId; ?>"
                            data-parent-id="<?php echo $accountData->getAccountParentId(); ?>"
                            data-full="0"
                            data-sk="<?php echo $sk; ?>"
                            data-history="<?php echo $accountIsHistory; ?>"
                            data-onclick="account/copypass"
                            title="<?php echo __('Copiar Clave en Portapapeles'); ?>">
                        <i class="material-icons"><?php echo $icons->getIconClipboard()->getIcon(); ?></i>
                    </button>
                </li>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowCopy()): ?>
            <li>
                <button id="btnEdit" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconCopy()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_COPY; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/copy"
                        title="<?php echo __('Copiar Cuenta'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconCopy()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowEditPass()): ?>
            <li>
                <button id="btnEditPass" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEditPass()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-parent-id="<?php echo $accountData->getAccountParentId(); ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/editpass"
                        title="<?php echo __('Modificar Clave de Cuenta'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconEditPass()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowEdit()): ?>
            <li>
                <button id="btnEdit" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEdit()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/edit"
                        title="<?php echo __('Modificar Cuenta'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconEdit()->getIcon(); ?></i>
                </button>
            </li>
        <?php elseif (!$AccountAcl->isShowEdit() && $actionId == \SP\Core\ActionsInterface::ACTION_ACC_VIEW && Checks::mailrequestIsEnabled()): ?>
            <li>
                <button id="btnRequest" type="button"
                        class="btn-action mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconEmail()->getClassButton(); ?>"
                        data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_REQUEST; ?>"
                        data-item-id="<?php echo $accountId; ?>"
                        data-sk="<?php echo $sk; ?>"
                        data-onclick="account/edit"
                        title="<?php echo __('Solicitar Modificación'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconEmail()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>

        <?php if ($AccountAcl->isShowRestore()): ?>
            <li>
                <button id="btnRestore" name="btnRestore" type="submit" form="frmAccountRestore"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconRestore()->getClassButton(); ?>"
                        title="<?php echo __('Restaurar cuenta desde este punto'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconRestore()->getIcon(); ?></i>
                </button>
            </li>
        <?php elseif ($AccountAcl->isShowSave()): ?>
            <li>
                <button id="btnSave" name="btnSave" type="submit" form="frmAccount"
                        class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored <?php echo $icons->getIconSave()->getClassButton(); ?>"
                        title="<?php echo __('Guardar'); ?>">
                    <i class="material-icons"><?php echo $icons->getIconSave()->getIcon(); ?></i>
                </button>
            </li>
        <?php endif; ?>
    </ul>
</div>