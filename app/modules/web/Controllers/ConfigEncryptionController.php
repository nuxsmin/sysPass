<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\TaskFactory;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\ServiceException;
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

        $currentMasterPass = Request::analyzeEncrypted('curMasterPwd');
        $newMasterPass = Request::analyzeEncrypted('newMasterPwd');
        $newMasterPassR = Request::analyzeEncrypted('newMasterPwdR');
        $confirmPassChange = Request::analyze('confirmPassChange', 0, false, 1);
        $noAccountPassChange = Request::analyze('chkNoAccountChange', 0, false, 1);

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
            Util::lockApp();

            $request = new UpdateMasterPassRequest(
                $currentMasterPass,
                $newMasterPass,
                $configService->getByParam('masterPwd'),
                TaskFactory::create(__FUNCTION__, 'masterpass')
            );

            try {
                $mastePassService->changeMasterPassword($request);
                $configService->save('masterPwd', $request->getHash());
                $configService->save('lastupdatempass', time());
            } catch (\Exception $e) {
                processException($e);

                $this->returnJsonResponseException($e);
            } finally {
                Util::unlockApp();

                TaskFactory::end($request->getTask()->getTaskId());
            }
        } else {
            try {
                $configService->save('masterPwd', Hash::hashKey($newMasterPass));
                $configService->save('lastupdatempass', time());
            } catch (\Exception $e) {
                processException($e);

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
            $configService->save('masterPwd', Hash::hashKey(CryptSession::getSessionKey()));

            $this->eventDispatcher->notifyEvent('refresh.masterPassword', new Event($this, [__u('Hash de clave maestra actualizado')]));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Hash de clave maestra actualizado'));
        } catch (\Exception $e) {
            processException($e);

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
            $temporaryMasterPassService->create(Request::analyze('tmpass_maxtime', 3600));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clave Temporal Generada'));
        } catch (ServiceException $e) {
            $this->returnJsonResponseException($e);
        }


//        $tempMasterGroup = Request::analyze('tmpass_group', 0);
//        $tempMasterEmail = Request::analyze('tmpass_chkSendEmail', 0, false, 1);
//
//            $this->LogMessage->addDescription(__('Clave Temporal Generada', false));
//
//            if ($tempMasterEmail) {
//                $Message = new NoticeMessage();
//                $Message->setTitle(sprintf(__('Clave Maestra %s'), Util::getAppInfo('appname')));
//                $Message->addDescription(__('Se ha generado una nueva clave para el acceso a sysPass y se solicitará en el siguiente inicio.'));
//                $Message->addDescription('');
//                $Message->addDescription(sprintf(__('La nueva clave es: %s'), $tempMasterPass));
//                $Message->addDescription('');
//                $Message->addDescription(__('No olvide acceder lo antes posible para guardar los cambios.'));
//
//                if ($tempMasterGroup !== 0) {
//                    Email::sendEmailBatch($Message, UserUtil::getUserGroupEmail($tempMasterGroup));
//                } else {
//                    Email::sendEmailBatch($Message, UserUtil::getUsersEmail());
//                }
//            }
//
//            $this->JsonResponse->setStatus(0);

    }

    protected function initialize()
    {
        try {
            $this->checkAccess(ActionsInterface::ENCRYPTION_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}