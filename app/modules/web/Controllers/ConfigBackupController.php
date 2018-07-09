<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Context\SessionContext;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Services\Backup\FileBackupService;
use SP\Services\Export\XmlExportService;

/**
 * Class ConfigBackupController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigBackupController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function fileBackupAction()
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, esto es una DEMO!!'));
        }

        try {
            SessionContext::close();

            $this->dic->get(FileBackupService::class)
                ->doBackup(BACKUP_PATH);

            $this->eventDispatcher->notifyEvent('run.backup.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Copia de la aplicación y base de datos realizada correctamente')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Proceso de backup finalizado'));
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function xmlExportAction()
    {
        $exportPassword = $this->request->analyzeEncrypted('exportPwd');
        $exportPasswordR = $this->request->analyzeEncrypted('exportPwdR');

        if (!empty($exportPassword) && $exportPassword !== $exportPasswordR) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Las claves no coinciden'));
        }

        try {
            $this->eventDispatcher->notifyEvent('run.export.start',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Exportación de sysPass en XML')))
            );

            SessionContext::close();

            $this->dic->get(XmlExportService::class)
                ->doExport($exportPassword);

            $this->eventDispatcher->notifyEvent('run.export.end',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Proceso de exportación finalizado')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Proceso de exportación finalizado'));
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::BACKUP_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}