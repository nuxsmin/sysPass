<?php /** @var \SP\Account\AccountAcl $AccountAcl */ ?>

<table class="data round extra-info">
    <tr>
        <td class="descField"><?php echo _('Archivos'); ?></td>
        <td class="valField">
            <div id="list-account-files"
                 data-item-id="<?php echo $accountIsHistory ? $accountParentId : $accountId; ?>"
                 data-history="<?php $accountIsHistory; ?>"
                 data-delete="<?php echo (int)$AccountAcl->isShowDelete(); ?>"
                 data-sk="<?php echo $sk; ?>">
            </div>
            <?php if ($actionId === \SP\Core\ActionsInterface::ACTION_ACC_EDIT): ?>
                <div id="drop-account-files" class="round active-tooltip dropzone"
                     data-files-ext="<?php echo $filesAllowedExts; ?>"
                     data-item-id="<?php echo $accountId; ?>"
                     data-sk="<?php echo $sk; ?>"
                     data-action-id="<?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_UPLOAD; ?>"
                     data-delete="<?php echo (int)$AccountAcl->isShowDelete(); ?>"
                     title="<?php echo _('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . _('Tamaño máximo de archivo') . ' ' . $maxFileSize . ' MB'; ?>">
                    <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                </div>
                <form method="post" enctype="multipart/form-data" name="upload_form" class="file-upload" id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
            <?php endif; ?>
        </td>
    </tr>
</table>