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
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\Task\TaskFactory;
use SP\Util\Util;

/**
 * Class ConfigEncryptionController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigEncryptionController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @throws \SP\Repositories\NoSuchItemException
     * @throws \SP\Services\ServiceException
     */
    public function saveAction()
    {
        $mastePassService = $this->dic->get(MasterPassService::class);

        $currentMasterPass = $this->request->analyzeEncrypted('current_masterpass');
        $newMasterPass = $this->request->analyzeEncrypted('new_masterpass');
        $newMasterPassR = $this->request->analyzeEncrypted('new_masterpass_repeat');
        $confirmPassChange = $this->request->analyzeBool('confirm_masterpass_change', false);
        $noAccountPassChange = $this->request->analyzeBool('no_account_change', false);
        $taskId = $this->request->analyzeString('taskId');

        if (!$mastePassService->checkUserUpdateMPass($this->session->getUserData()->getLastUpdateMPass())) {
            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS_STICKY, __u('Clave maestra actualizada'), [__u('Reinicie la sesión para cambiarla')]);
        }

        if (empty($newMasterPass) || empty($currentMasterPass)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Clave maestra no indicada'));
        }

        if ($confirmPassChange === false) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Se ha de confirmar el cambio de clave'));
        }

        if ($newMasterPass === $currentMasterPass) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Las claves son idénticas'));
        }

        if ($newMasterPass !== $newMasterPassR) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Las claves maestras no coinciden'));
        }

        if (!$mastePassService->checkMasterPassword($currentMasterPass)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('La clave maestra actual no coincide'));
        }

        if ($this->config->getConfigData()->isDemoEnabled()) {
            $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, esto es una DEMO!!'));
        }

        $configService = $this->dic->get(ConfigService::class);

        if (!$noAccountPassChange) {
            Util::lockApp($this->session->getUserData()->getId(), 'masterpass');

            $task = $taskId !== null ? TaskFactory::create(__FUNCTION__, $taskId) : null;

            try {
                $request = new UpdateMasterPassRequest(
                    $currentMasterPass,
                    $newMasterPass,
                    $configService->getByParam(MasterPassService::PARAM_MASTER_PASS_HASH),
                    $task
                );

                $this->eventDispatcher->notifyEvent('update.masterPassword.start', new Event($this));

                $mastePassService->changeMasterPassword($request);

                $this->eventDispatcher->notifyEvent('update.masterPassword.end', new Event($this));
            } catch (\Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                $this->returnJsonResponseException($e);
            } finally {
                Util::unlockApp();

                if ($task) {
                    TaskFactory::end($task->getTaskId());
                }
            }
        } else {
            try {
                $this->eventDispatcher->notifyEvent('update.masterPassword.hash', new Event($this));

                $mastePassService->updateConfig(Hash::hashKey($newMasterPass));
            } catch (\Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Error al guardar el hash de la clave maestra'));
            }
        }

        $this->returnJsonResponse(JsonResponse::JSON_SUCCESS_STICKY, __u('Clave maestra actualizada'), [__u('Reinicie la sesión para cambiarla')]);
    }

    /**
     * Refresh master password hash
     */
    public function refreshAction()
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, esto es una DEMO!!'));
        }

        try {
            $masterPassService = $this->dic->get(MasterPassService::class);
            $masterPassService->updateConfig(Hash::hashKey(CryptSession::getSessionKey($this->session)));

            $this->eventDispatcher->notifyEvent('refresh.masterPassword.hash',
                new Event($this, EventMessage::factory()->addDescription(__u('Hash de clave maestra actualizado'))));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Hash de clave maestra actualizado'));
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));


            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Error al actualizar el hash de la clave maestra'));
        }
    }

    /**
     * Create a temporary master pass
     */
    public function saveTempAction()
    {
        try {
            $temporaryMasterPassService = $this->dic->get(TemporaryMasterPassService::class);
            $key = $temporaryMasterPassService->create($this->request->analyzeInt('temporary_masterpass_maxtime', 3600));

            $groupId = $this->request->analyzeInt('temporary_masterpass_group');
            $sendEmail = $this->configData->isMailEnabled()
                && $this->request->analyzeBool('temporary_masterpass_email')
                && $groupId > 0;

            if ($sendEmail) {
                try {
                    $temporaryMasterPassService->sendByEmailForGroup($groupId, $key);

                    $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clave Temporal Generada'), [__u('Email enviado')]);
                } catch (\Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));

                    $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Clave Temporal Generada'), [__u('Error al enviar email')]);
                }
            }

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clave Temporal Generada'));
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
            $this->checkAccess(Acl::ENCRYPTION_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}