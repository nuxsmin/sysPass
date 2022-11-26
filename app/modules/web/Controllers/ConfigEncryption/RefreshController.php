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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class RefreshController
 */
final class RefreshController extends SimpleControllerBase
{
    use JsonTrait;

    private MasterPassServiceInterface $masterPassService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        MasterPassServiceInterface $masterPassService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->masterPassService = $masterPassService;
    }

    /**
     * Refresh master password hash
     *
     * @return bool
     * @throws \JsonException
     */
    public function refreshAction(): bool
    {
        try {
            if ($this->config->getConfigData()->isDemoEnabled()) {
                return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('Ey, this is a DEMO!!'));
            }

            $this->masterPassService->updateConfig(Hash::hashKey(CryptSession::getSessionKey($this->session)));

            $this->eventDispatcher->notifyEvent(
                'refresh.masterPassword.hash',
                new Event($this, EventMessage::factory()->addDescription(__u('Master password hash updated')))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Master password hash updated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Error while updating the master password hash')
            );
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
