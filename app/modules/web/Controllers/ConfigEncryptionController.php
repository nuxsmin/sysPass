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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\MailService;
use SP\Services\Task\TaskFactory;
use SP\Services\User\UserService;
use SP\Util\Util;

/**
 * Class ConfigEncryptionController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigEncryptionController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws \SP\Services\Config\ParameterNotFoundException
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

            $request = new UpdateMasterPassRequest(
                $currentMasterPass,
                $newMasterPass,
                $configService->getByParam('masterPwd'),
                $task
            );

            try {
                $this->eventDispatcher->notifyEvent('update.masterPassword.start', new Event($this));

                $mastePassService->changeMasterPassword($request);
                $configService->save('masterPwd', $request->getHash());
                $configService->save('lastupdatempass', time());

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

                $configService->save('masterPwd', Hash::hashKey($newMasterPass));
                $configService->save('lastupdatempass', time());
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
            $configService = $this->dic->get(ConfigService::class);
            $configService->save('masterPwd', Hash::hashKey(CryptSession::getSessionKey($this->session)));

            $this->eventDispatcher->notifyEvent('refresh.masterPassword',
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
            $sendEmail = $this->request->analyzeBool('temporary_masterpass_email');

            if ($this->configData->isMailEnabled() && $sendEmail && $groupId) {
                $mailMessage = new MailMessage();
                $mailMessage->setTitle(sprintf(__('Clave Maestra %s'), Util::getAppInfo('appname')));
                $mailMessage->addDescription(__('Se ha generado una nueva clave para el acceso a sysPass y se solicitará en el siguiente inicio.'));
                $mailMessage->addDescriptionLine();
                $mailMessage->addDescription(sprintf(__('La nueva clave es: %s'), $key));
                $mailMessage->addDescriptionLine();
                $mailMessage->addDescription(sprintf(__('Esta clave estará activa hasta: %s'), date('r', $temporaryMasterPassService->getMaxTime())));
                $mailMessage->addDescriptionLine();
                $mailMessage->addDescription(__('No olvide acceder lo antes posible para guardar los cambios.'));

                try {
                    $emails = array_map(function ($value) {
                        return $value->email;
                    }, $this->dic->get(UserService::class)->getUserEmailForGroup($groupId));

                    $this->dic->get(MailService::class)->sendBatch($mailMessage->getTitle(), $emails, $mailMessage);

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