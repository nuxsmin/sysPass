<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Services\Backup\FileBackupService;
use SP\Services\Export\XmlExportService;
use SP\Services\Export\XmlVerifyService;
use SP\Storage\File\FileHandler;

/**
 * Class ConfigBackupController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigBackupController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function fileBackupAction(): bool
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_WARNING,
                __u('Ey, this is a DEMO!!')
            );
        }

        try {
            SessionContext::close();

            $this->dic->get(FileBackupService::class)
                ->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notifyEvent('
            run.backup.end',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Application and database backup completed successfully'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Backup process finished')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function xmlExportAction(): bool
    {
        $exportPassword = $this->request->analyzeEncrypted('exportPwd');
        $exportPasswordR = $this->request->analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Passwords do not match')
            );
        }

        try {
            $this->eventDispatcher->notifyEvent(
                'run.export.start',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('sysPass XML export'))
                )
            );

            SessionContext::close();

            $export = $this->dic->get(XmlExportService::class);
            $export->doExport(BACKUP_PATH, $exportPassword);

            $this->eventDispatcher->notifyEvent(
                'run.export.end',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Export process finished'))
                )
            );

            $verify = $this->dic->get(XmlVerifyService::class);

            if ($export->isEncrypted()) {
                $verifyResult = $verify->verifyEncrypted(
                    $export->getExportFile(),
                    $exportPassword
                );
            } else {
                $verifyResult = $verify->verify($export->getExportFile());
            }

            $nodes = $verifyResult->getNodes();

            $this->eventDispatcher->notifyEvent(
                'run.export.verify',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Verification of exported data finished'))
                        ->addDetail(__u('Version'), $verifyResult->getVersion())
                        ->addDetail(__u('Encrypted'), $verifyResult->isEncrypted() ? __u('Yes') : __u('No'))
                        ->addDetail(__u('Accounts'), $nodes['Account'])
                        ->addDetail(__u('Clients'), $nodes['Client'])
                        ->addDetail(__u('Categories'), $nodes['Category'])
                        ->addDetail(__u('Tags'), $nodes['Tag'])
                )
            );

            // Create the XML archive after verifying the export integrity
            $export->createArchive();

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Export process finished')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return string
     */
    public function downloadExportAction(): string
    {
        try {
            SessionContext::close();

            $filePath = XmlExportService::getExportFilename(
                BACKUP_PATH,
                $this->configData->getExportHash(),
                true
            );

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent(
                'download.exportFile',
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
                ->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"')
                ->header('Set-Cookie', 'fileDownload=true; path=/')
                ->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );
        }

        return '';
    }

    /**
     * @return string
     */
    public function downloadBackupAppAction(): string
    {
        if ($this->configData->isDemoEnabled()) {
            return __('Ey, this is a DEMO!!');
        }

        try {
            SessionContext::close();

            $filePath = FileBackupService::getAppBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash(),
                true
            );

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent(
                'download.backupAppFile',
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
                ->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"')
                ->header('Set-Cookie', 'fileDownload=true; path=/')
                ->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );
        }

        return '';
    }

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

            $filePath = FileBackupService::getDbBackupFilename(
                BACKUP_PATH,
                $this->configData->getBackupHash(),
                true
            );

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent(
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
                ->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"')
                ->header('Set-Cookie', 'fileDownload=true; path=/')
                ->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );
        }

        return '';
    }

    /**
     * initialize
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     * @throws \JsonException
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            $this->returnJsonResponseException($e);
        }
    }
}