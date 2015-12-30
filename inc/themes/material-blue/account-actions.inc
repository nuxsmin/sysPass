<?php use SP\Util\Checks; ?>
<div class="action">
    <?php if ($showDelete): ?>
        <button id="btnDelete" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-red80"
                OnClick="sysPassUtil.Common.delAccount(<?php echo $accountId; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_DELETE; ?>,'<?php echo $sk; ?>');"
                title="<?php echo _('Eliminar Cuenta'); ?>">
            <i class="material-icons">delete</i>
        </button>
    <?php endif; ?>

    <?php if ($showLink && $showViewPass): ?>
        <button id="btnLink" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                OnClick="sysPassUtil.Common.linksMgmtSave(<?php echo $accountId; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_MGM_PUBLICLINKS_NEW; ?>,'<?php echo $sk; ?>');"
                title="<?php echo _('Crear Enlace Público'); ?>">
            <i class="material-icons">link</i>
        </button>
    <?php endif; ?>

    <?php if ($showViewPass): ?>
        <button id="btnViewPass" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                onClick="sysPassUtil.Common.viewPass(<?php echo $accountId; ?>,1,<?php echo $accountIsHistory; ?>)"
                title="<?php echo _('Ver Clave'); ?>">
            <i class="material-icons">lock_open</i>
        </button>
        <?php if (!Checks::accountPassToImageIsEnabled()): ?>
            <button id="btnClipPass"
                    class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored clip-pass-button"
                    onmousedown="sysPassUtil.Common.viewPass(<?php echo $accountId; ?>, false, <?php echo $accountIsHistory; ?>)"
                    title="<?php echo _('Copiar Clave en Portapapeles'); ?>"
                    data-account-id="<?php echo $accountId; ?>">
                <i class="material-icons">content_paste</i>
            </button>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($showEditPass): ?>
        <button id="btnEditPass" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-orange80"
                OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT_PASS; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountId; ?>)"
                title="<?php echo _('Modificar Clave de Cuenta'); ?>">
            <i class="material-icons">lock_outline</i>
        </button>
    <?php endif; ?>

    <?php if ($gotData && $accountIsHistory): ?>
        <button id="btnBack" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountParentId; ?>)"
                title="<?php echo _('Ver Actual'); ?>">
            <i class="material-icons">arrow_back</i>
        </button>
    <?php else: ?>
        <button id="btnBack" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="sysPassUtil.Common.doAction(<?php echo $lastAction; ?>, <?php echo $actionId; ?>,<?php echo $accountParentId; ?>)"
                title="<?php echo _('Atrás'); ?>">
            <i class="material-icons">arrow_back</i>
        </button>
    <?php endif; ?>

    <?php if ($showEdit): ?>
        <button id="btnEdit" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-orange80"
                OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_EDIT; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_SEARCH; ?>,<?php echo $accountId; ?>)"
                title="<?php echo _('Modificar Cuenta'); ?>">
            <i class="material-icons">mode_edit</i>
        </button>
    <?php elseif (!$showEdit && $actionId == \SP\Core\ActionsInterface::ACTION_ACC_VIEW && Checks::mailrequestIsEnabled()): ?>
        <button id="btnRequest" type="button"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored"
                OnClick="sysPassUtil.Common.doAction(<?php echo \SP\Core\ActionsInterface::ACTION_ACC_REQUEST; ?>,<?php echo \SP\Core\ActionsInterface::ACTION_ACC_VIEW; ?>,<?php echo $accountId; ?>)"
                title="<?php echo _('Solicitar Modificación'); ?>">
            <i class="material-icons">email</i>
        </button>
    <?php endif; ?>

    <?php if ($showRestore): ?>
        <button id="btnRestore" name="btnRestore" type="submit" form="frmAccount"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Restaurar cuenta desde este punto'); ?>">
            <i class="material-icons">restore</i>
        </button>
    <?php endif; ?>

    <?php if ($showSave): ?>
        <button id="btnSave" name="btnSave" type="submit" form="frmAccount"
                class="mdl-button mdl-js-button mdl-button--fab mdl-button--mini-fab mdl-button--colored bg-green80"
                title="<?php echo _('Guardar'); ?>">
            <i class="material-icons">save</i>
        </button>
    <?php endif; ?>
</div>