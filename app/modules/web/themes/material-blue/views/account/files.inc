<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Models\AccountView;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\UI\ThemeIconsInterface;

use function SP\__;

/**
 * @var ThemeIconsInterface $icons
 * @var callable $_getvar
 * @var AccountView $accountView
 * @var AccountPermission $accountAcl
 * @var ConfigDataInterface $configData
 */
?>

<div id="data-files">
    <div class="data">
        <div id="list-account-files"
             data-action-route="<?php
             echo $_getvar('fileListRoute'); ?>"
             data-item-id="<?php
             echo $_getvar('accountId'); ?>"
             data-history="<?php
             echo $_getvar('accountIsHistory', false); ?>"
             data-delete="<?php
             echo (int)$accountAcl->isShowDelete(); ?>">
        </div>

        <?php
        if ($_getvar('editAction')): ?>
            <div id="box-upload-files">
                <div id="drop-account-files"
                     class="round active-tooltip dropzone"
                     data-files-ext="<?php
                     echo $_getvar('filesAllowedExts'); ?>"
                     data-item-id="<?php
                     echo $_getvar('accountId'); ?>"
                     data-action-route="<?php
                     echo $_getvar('fileUploadRoute'); ?>"
                     data-delete="<?php
                     echo (int)$accountAcl->isShowDelete(); ?>">
                    <i class="material-icons md-60 mdl-color-text--teal-500">cloud_upload</i>
                </div>
                <form method="post" enctype="multipart/form-data"
                      name="upload_form" class="file-upload"
                      id="fileUploadForm">
                    <input type="file" id="inFile" multiple name="inFile"/>
                </form>
                <div class="mdl-tooltip" for="drop-account-files">
                    <?php
                    printf(
                        __('Drop files here (max. 5) or click to select')
                        . '<br><br>'
                        . __('Maximum file size: %d MB'),
                        $_getvar('maxFileSize')
                    ); ?>
                </div>
            </div>
        <?php
        endif; ?>
    </div>
</div>
