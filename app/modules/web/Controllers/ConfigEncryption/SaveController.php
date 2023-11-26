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

namespace SP\Modules\Web\Controllers\ConfigEncryption;


use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Domain\Crypt\Services\MasterPassService;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\Task\Ports\TaskInterface;
use SP\Domain\Task\Services\Task;
use SP\Domain\Task\Services\TaskFactory;
use SP\Http\JsonResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\File\FileException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class SaveController
 */
final class SaveController extends SimpleControllerBase
{
    use JsonTrait;

    private MasterPassServiceInterface $masterPassService;
    private ConfigServiceInterface     $configService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        MasterPassServiceInterface $masterPassService,
        ConfigServiceInterface $configService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->masterPassService = $masterPassService;
        $this->configService = $configService;
    }

    /**
     * @return bool
     * @throws JsonException
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function saveAction(): bool
    {
        $currentMasterPass = $this->request->analyzeEncrypted('current_masterpass');
        $newMasterPass = $this->request->analyzeEncrypted('new_masterpass');
        $newMasterPassR = $this->request->analyzeEncrypted('new_masterpass_repeat');
        $confirmPassChange = $this->request->analyzeBool('confirm_masterpass_change', false);
        $noAccountPassChange = $this->request->analyzeBool('no_account_change', false);


        if (!$this->masterPassService->checkUserUpdateMPass($this->session->getUserData()->getLastUpdateMPass())) {
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

        if (!$this->masterPassService->checkMasterPassword($currentMasterPass)) {
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

        if (!$noAccountPassChange) {
            try {
                $task = $this->getTask();

                $request = new UpdateMasterPassRequest(
                    $currentMasterPass,
                    $newMasterPass,
                    $this->configService->getByParam(MasterPassService::PARAM_MASTER_PASS_HASH),
                    $task
                );

                $this->eventDispatcher->notify('update.masterPassword.start', new Event($this));

                $this->masterPassService->changeMasterPassword($request);

                $this->eventDispatcher->notify('update.masterPassword.end', new Event($this));
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                return $this->returnJsonResponseException($e);
            } finally {
                if (isset($task)) {
                    TaskFactory::end($task);
                }
            }
        } else {
            try {
                $this->eventDispatcher->notify('update.masterPassword.hash', new Event($this));

                $this->masterPassService->updateConfig(Hash::hashKey($newMasterPass));
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('Error while saving the Master Password\'s hash')
                );
            }
        }

        return $this->returnJsonResponse(
            JsonResponse::JSON_SUCCESS_STICKY,
            __u('Master password updated'),
            [__u('Please, restart the session to update it')]
        );
    }

    /**
     * @throws FileException
     */
    private function getTask(): ?TaskInterface
    {
        $taskId = $this->request->analyzeString('taskId');

        return $taskId !== null
            ? TaskFactory::register(new Task(__FUNCTION__, $taskId))
            : null;
    }

    /**
     * @return void
     * @throws JsonException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_CRYPT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
