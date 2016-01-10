<table class="data round extra-info secure-info">
    <tr>
        <td class="descField"><?php echo _('Archivos'); ?></td>
        <td class="valField">
            <div id="downFiles"></div>
            <?php if ($accountIsHistory): ?>
                <script>sysPassUtil.Common.getFiles(<?php echo $accountParentId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');</script>
            <?php else: ?>
                <script>sysPassUtil.Common.getFiles(<?php echo $accountId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');    </script>
            <?php if ($actionId == \SP\Core\ActionsInterface::ACTION_ACC_EDIT): ?>
                <div id="dropzone" class="round active-tooltip"
                     data-files-ext="<?php echo $filesAllowedExts; ?>"
                     title="<?php echo _('Soltar archivos aquí (max. 5) o click para seleccionar') . '<br><br>' . _('Tamaño máximo de archivo') . ' ' . $maxFileSize . ' MB'; ?>">
                    <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                </div>
                <form method="post" enctype="multipart/form-data" name="upload_form" id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
                <script>
                    var upload = sysPassUtil.Common.fileUpload({targetId: 'dropzone', url: '/ajax/ajax_files.php'});
                    upload.setRequestData({
                        accountId: <?php echo $accountId; ?>,
                        sk: '<?php echo $sk; ?>',
                        actionId: <?php echo \SP\Core\ActionsInterface::ACTION_ACC_FILES_UPLOAD; ?>
                    });
                    upload.setRequestDoneAction(function () {
                        sysPassUtil.Common.getFiles(<?php echo $accountId; ?>, <?php echo intval($showDelete); ?>, '<?php echo $sk; ?>');
                    });
                </script>
            <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
</table>