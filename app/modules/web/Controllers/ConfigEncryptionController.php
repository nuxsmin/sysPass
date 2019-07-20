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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Repositories\NoSuchItemException;
use SP\Services\Config\ConfigService;
use SP\Services\Crypt\MasterPassService;
use SP\Services\Crypt\TemporaryMasterPassService;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\ServiceException;
use SP\Services\Task\TaskFactory;

/**
 * Class ConfigEncryptionController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigEncryptionController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function saveAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        $mastePassService = $this->dic->get(MasterPassService::class);

        $currentMasterPass = $this->request->analyzeEncrypted('current_masterpass');
        $newMasterPass = $this->request->analyzeEncrypted('new_masterpass');
        $newMasterPassR = $this->request->analyzeEncrypted('new_masterpass_repeat');
        $confirmPassChange = $this->request->analyzeBool('confirm_masterpass_change', false);
        $noAccountPassChange = $this->request->analyzeBool('no_account_change', false);
        $taskId = $this->request->analyzeString('taskId');

        if (!$mastePassService->checkUserUpdateMPass($this->session->getUserData()->getLastUpdateMPass())) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS_STICKY,
                __u('Master password updated'),
                [__u('Please, restart the session for update it')]
            );
        }

        if (empty($newMasterPass) || empty($currentMasterPass)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Master password not entered')
            );
        }

        if ($confirmPassChange === false) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('The password update must be confirmed')
            );
        }

        if ($newMasterPass === $currentMasterPass) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Passwords are the same')
            );
        }

        if ($newMasterPass !== $newMasterPassR) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Master passwords do not match')
            );
        }

        if (!$mastePassService->checkMasterPassword($currentMasterPass)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('The current master password does not match')
            );
        }

        if (!$this->config->getConfigData()->isMaintenance()) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_WARNING,
                __u('Maintenance mode not enabled'),
                [__u('Please, enable it to avoid unwanted behavior from other sessions')]
            );
        }

        if ($this->config->getConfigData()->isDemoEnabled()) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_WARNING,
                __u('Ey, this is a DEMO!!')
            );
        }

        $configService = $this->dic->get(ConfigService::class);

        if (!$noAccountPassChange) {
            try {
                $task = $taskId !== null ? TaskFactory::create(__FUNCTION__, $taskId) : null;

                $request = new UpdateMasterPassRequest(
                    $currentMasterPass,
                    $newMasterPass,
                    $configService->getByParam(MasterPassService::PARAM_MASTER_PASS_HASH),
                    $task
                );

                $this->eventDispatcher->notifyEvent('update.masterPassword.start', new Event($this));

                $mastePassService->changeMasterPassword($request);

                $this->eventDispatcher->notifyEvent('update.masterPassword.end', new Event($this));
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                return $this->returnJsonResponseException($e);
            } finally {
                if (isset($task)) {
                    TaskFactory::end($task);
                }
            }
        } else {
            try {
                $this->eventDispatcher->notifyEvent('update.masterPassword.hash', new Event($this));

                $mastePassService->updateConfig(Hash::hashKey($newMasterPass));
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Error while saving the Master Password\'s hash'));
            }
        }

        return $this->returnJsonResponse(
            JsonResponse::JSON_SUCCESS_STICKY,
            __u('Master password updated'),
            [__u('Please, restart the session for update it')]
        );
    }

    /**
     * Refresh master password hash
     */
    public function refreshAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            if ($this->config->getConfigData()->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

            $masterPassService = $this->dic->get(MasterPassService::class);
            $masterPassService->updateConfig(Hash::hashKey(CryptSession::getSessionKey($this->session)));

            $this->eventDispatcher->notifyEvent('refresh.masterPassword.hash',
                new Event($this, EventMessage::factory()->addDescription(__u('Master password hash updated'))));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Master password hash updated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));


            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Error while updating the master password hash'));
        }
    }

    /**
     * Create a temporary master pass
     */
    public function saveTempAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $temporaryMasterPassService = $this->dic->get(TemporaryMasterPassService::class);
            $key = $temporaryMasterPassService->create($this->request->analyzeInt('temporary_masterpass_maxtime', 3600));

            $groupId = $this->request->analyzeInt('temporary_masterpass_group', 0);
            $sendEmail = $this->configData->isMailEnabled()
                && $this->request->analyzeBool('temporary_masterpass_email');

            if ($sendEmail) {
                try {
                    if ($groupId > 0) {
                        $temporaryMasterPassService->sendByEmailForGroup($groupId, $key);
                    } else {
                        $temporaryMasterPassService->sendByEmailForAllUsers($key);
                    }

                    return $this->returnJsonResponse(
                        JsonResponse::JSON_SUCCESS,
                        __u('Temporary password generated'),
                        [__u('Email sent')]
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));

                    return $this->returnJsonResponse(
                        JsonResponse::JSON_WARNING,
                        __u('Temporary password generated'),
                        [__u('Error while sending the email')]
                    );
                }
            }

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Temporary password generated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_CRYPT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}