<?php
/**
 * @var \SP\Services\Account\AccountAcl $accountAcl
 * @var \SP\Core\UI\ThemeIcons          $icons
 */
?>

<div id="data-files">
    <div class="data">
        <div id="list-account-files"
             data-action-route="<?php echo $fileListRoute; ?>"
             data-item-id="<?php echo $accountId; ?>"
             data-history="<?php $accountIsHistory; ?>"
             data-delete="<?php echo (int)$accountAcl->isShowDelete(); ?>"
             data-sk="<?php echo $sk; ?>">
        </div>

        <?php if (isset($editAction)): ?>
            <div id="box-upload-files">
                <div id="drop-account-files" class="round active-tooltip dropzone"
                     data-files-ext="<?php echo $filesAllowedExts; ?>"
                     data-item-id="<?php echo $accountId; ?>"
                     data-sk="<?php echo $sk; ?>"
                     data-action-route="<?php echo $fileUploadRoute; ?>"
                     data-delete="<?php echo (int)$accountAcl->isShowDelete(); ?>">
                    <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                </div>
                <form method="post" enctype="multipart/form-data" name="upload_form" class="file-upload"
                      id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
                <div class="mdl-tooltip" for="drop-account-files">
                    <?php printf(__('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . __('Tamaño máximo de archivo: %d MB'), $maxFileSize); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
