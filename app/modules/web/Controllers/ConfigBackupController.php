<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
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
     * @throws SPException
     */
    public function fileBackupAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
        }

        try {
            SessionContext::close();

            $this->dic->get(FileBackupService::class)
                ->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notifyEvent('run.backup.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Application and database backup completed successfully')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Backup process finished'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     */
    public function xmlExportAction()
    {
        $exportPassword = $this->request->analyzeEncrypted('exportPwd');
        $exportPasswordR = $this->request->analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Passwords do not match'));
        }

        try {
            $this->eventDispatcher->notifyEvent('run.export.start',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('sysPass XML export')))
            );

            SessionContext::close();

            $export = $this->dic->get(XmlExportService::class);
            $export->doExport(BACKUP_PATH, $exportPassword);

            $this->eventDispatcher->notifyEvent('run.export.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Export process finished')))
            );

            $verify = $this->dic->get(XmlVerifyService::class);

            if ($export->isEncrypted()) {
                $verifyResult = $verify->verifyEncrypted($export->getExportFile(), $exportPassword);
            } else {
                $verifyResult = $verify->verify($export->getExportFile());
            }

            $nodes = $verifyResult->getNodes();

            $this->eventDispatcher->notifyEvent('run.export.verify',
                new Event($this, EventMessage::factory()
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

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Export process finished'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function downloadExportAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        try {
            SessionContext::close();

            $filePath = XmlExportService::getExportFilename(BACKUP_PATH, $this->configData->getExportHash(), true);

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent('download.exportFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('File downloaded'))
                    ->addDetail(__u('File'), str_replace(APP_ROOT, '', $file->getFile())))
            );

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', $file->getFileSize());
            $response->header('Content-type', $file->getFileType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'chunked');
            $response->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"');
            $response->header('Set-Cookie', 'fileDownload=true; path=/');
            $response->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        return '';
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function downloadBackupAppAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if ($this->configData->isDemoEnabled()) {
            return __('Ey, this is a DEMO!!');
        }

        try {
            SessionContext::close();

            $filePath = FileBackupService::getAppBackupFilename(BACKUP_PATH, $this->configData->getBackupHash(), true);

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent('download.backupAppFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('File downloaded'))
                    ->addDetail(__u('File'), str_replace(APP_ROOT, '', $file->getFile())))
            );

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', $file->getFileSize());
            $response->header('Content-type', $file->getFileType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'chunked');
            $response->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"');
            $response->header('Set-Cookie', 'fileDownload=true; path=/');
            $response->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        return '';
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function downloadBackupDbAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        if ($this->configData->isDemoEnabled()) {
            return __('Ey, this is a DEMO!!');
        }

        try {
            SessionContext::close();

            $filePath = FileBackupService::getDbBackupFilename(BACKUP_PATH, $this->configData->getBackupHash(), true);

            $file = new FileHandler($filePath);
            $file->checkFileExists();

            $this->eventDispatcher->notifyEvent('download.backupDbFile',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('File downloaded'))
                    ->addDetail(__u('File'), str_replace(APP_ROOT, '', $file->getFile())))
            );

            $response = $this->router->response();
            $response->header('Cache-Control', 'max-age=60, must-revalidate');
            $response->header('Content-length', $file->getFileSize());
            $response->header('Content-type', $file->getFileType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-transfer-encoding', 'chunked');
            $response->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"');
            $response->header('Set-Cookie', 'fileDownload=true; path=/');
            $response->send();

            $file->readChunked();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));
        }

        return '';
    }

    /**
     * initialize
     *
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_BACKUP);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}