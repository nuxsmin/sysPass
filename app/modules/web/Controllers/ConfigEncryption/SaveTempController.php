<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Crypt\TemporaryMasterPassServiceInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class ConfigEncryptionController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveTempController extends SimpleControllerBase
{
    use JsonTrait;

    private TemporaryMasterPassServiceInterface $temporaryMasterPassService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        TemporaryMasterPassServiceInterface $temporaryMasterPassService
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->temporaryMasterPassService = $temporaryMasterPassService;
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
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}