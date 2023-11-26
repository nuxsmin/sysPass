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

namespace SP\Modules\Web\Controllers\ConfigBackup;

use Exception;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Export\Services\BackupFiles;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class ConfigBackupController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DownloadBackupDbController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @return string
     */
    public function downloadBackupDbAction(): string
    {
        if ($this->configData->isDemoEnabled()) {
            return __('Ey, this is a DEMO!!');
        }

        try {
            SessionContext::close();

            $filePath = BackupFiles::getDbBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash(),
                true
            );

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notify(
                'download.backupDbFile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('File downloaded'))
                        ->addDetail(__u('File'), str_replace(APP_ROOT, '', $file->getFile()))
                )
            );

            $this->router
                ->response()
                ->header('Cache-Control', 'max-age=60, must-revalidate')
                ->header('Content-length', $file->getFileSize())
                ->header('Content-type', $file->getFileType())
                ->header('Content-Description', ' sysPass file')
                ->header('Content-transfer-encoding', 'chunked')
                ->header('Content-Disposition', 'attachment; filename="'.basename($file->getFile()).'"')
                ->header('Set-Cookie', 'fileDownload=true; path=/')
                ->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));
        }

        return '';
    }

    /**
     * initialize
     *
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
