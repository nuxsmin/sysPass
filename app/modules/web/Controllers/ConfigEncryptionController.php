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
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Config\ConfigServiceInterface;
use SP\Domain\Config\Services\ConfigService;
use SP\Domain\Crypt\MasterPassServiceInterface;
use SP\Domain\Crypt\Services\MasterPassService;
use SP\Domain\Crypt\Services\TemporaryMasterPassService;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\Crypt\TemporaryMasterPassServiceInterface;
use SP\Domain\Task\Services\TaskFactory;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class ConfigEncryptionController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigEncryptionController extends SimpleControllerBase
{
    use JsonTrait;

    private MasterPassServiceInterface $masterPassService;
    private ConfigService              $configService;
    private TemporaryMasterPassService $temporaryMasterPassService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        MasterPassServiceInterface $masterPassService,
        TemporaryMasterPassServiceInterface $temporaryMasterPassService,
        ConfigServiceInterface $configService
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->masterPassService = $masterPassService;
        $this->configService = $configService;
        $this->temporaryMasterPassService = $temporaryMasterPassService;
    }

    /**
     * @return bool
     * @throws \JsonException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    public function saveAction(): bool
    {
        $currentMasterPass = $this->request->analyzeEncrypted('current_masterpass');
        $newMasterPass = $this->request->analyzeEncrypted('new_masterpass');
        $newMasterPassR = $this->request->analyzeEncrypted('new_masterpass_repeat');
        $confirmPassChange = $this->request->analyzeBool('confirm_masterpass_change', false);
        $noAccountPassChange = $this->request->analyzeBool('no_account_change', false);
        $taskId = $this->request->analyzeString('taskId');

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
                $task = $taskId !== null
                    ? TaskFactory::create(__FUNCTION__, $taskId)
                    : null;

                $request = new UpdateMasterPassRequest(
                    $currentMasterPass,
                    $newMasterPass,
                    $this->configService->getByParam(MasterPassService::PARAM_MASTER_PASS_HASH),
                    $task
                );

                $this->eventDispatcher->notifyEvent(
                    'update.masterPassword.start',
                    new Event($this)
                );

                $this->masterPassService->changeMasterPassword($request);

                $this->eventDispatcher->notifyEvent(
                    'update.masterPassword.end',
                    new Event($this)
                );
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent(
                    'exception',
                    new Event($e)
                );

                return $this->returnJsonResponseException($e);
            } finally {
                if (isset($task)) {
                    TaskFactory::end($task);
                }
            }
        } else {
            try {
                $this->eventDispatcher->notifyEvent(
                    'update.masterPassword.hash',
                    new Event($this)
                );

                $this->masterPassService->updateConfig(Hash::hashKey($newMasterPass));
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent(
                    'exception',
                    new Event($e)
                );

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
     * Refresh master password hash
     *
     * @return bool
     * @throws DependencyException
     * @throws NotFoundException
     * @throws \JsonException
     */
    public function refreshAction(): bool
    {
        try {
            if ($this->config->getConfigData()->isDemoEnabled()) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_WARNING,
                    __u('Ey, this is a DEMO!!')
                );
            }

            $this->masterPassService->updateConfig(Hash::hashKey(CryptSession::getSessionKey($this->session)));

            $this->eventDispatcher->notifyEvent(
                'refresh.masterPassword.hash',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Master password hash updated'))
                )
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Master password hash updated')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );


            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Error while updating the master password hash')
            );
        }
    }

    /**
     * Create a temporary master pass
     *
     * @return bool
     * @throws \JsonException
     */
    public function saveTempAction(): bool
    {
        try {
            $key =
                $this->temporaryMasterPassService->create(
                    $this->request->analyzeInt('temporary_masterpass_maxtime', 3600)
                );

            $groupId = $this->request->analyzeInt('temporary_masterpass_group', 0);
            $sendEmail = $this->configData->isMailEnabled()
                         && $this->request->analyzeBool('temporary_masterpass_email');

            if ($sendEmail) {
                try {
                    if ($groupId > 0) {
                        $this->temporaryMasterPassService->sendByEmailForGroup($groupId, $key);
                    } else {
                        $this->temporaryMasterPassService->sendByEmailForAllUsers($key);
                    }

                    return $this->returnJsonResponse(
                        JsonResponse::JSON_SUCCESS,
                        __u('Temporary password generated'),
                        [__u('Email sent')]
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent(
                        'exception',
                        new Event($e)
                    );

                    return $this->returnJsonResponse(
                        JsonResponse::JSON_WARNING,
                        __u('Temporary password generated'),
                        [__u('Error while sending the email')]
                    );
                }
            }

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Temporary password generated')
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
     * @return void
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_CRYPT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            $this->returnJsonResponseException($e);
        }
    }
}