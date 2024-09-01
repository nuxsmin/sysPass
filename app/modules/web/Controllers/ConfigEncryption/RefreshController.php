<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\Session as CryptSession;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class RefreshController
 */
final class RefreshController extends SimpleControllerBase
{
    public function __construct(
        Application                        $application,
        SimpleControllerHelper             $simpleControllerHelper,
        private readonly MasterPassService $masterPassService
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * Refresh master password hash
     *
     * @return ActionResponse
     * @throws ConstraintException
     * @throws CryptException
     * @throws QueryException
     */
    #[Action(ResponseType::JSON)]
    public function refreshAction(): ActionResponse
    {
        if ($this->config->getConfigData()->isDemoEnabled()) {
            return ActionResponse::warning(__u('Ey, this is a DEMO!!'));
        }

        $this->masterPassService->updateConfig(Hash::hashKey(CryptSession::getSessionKey($this->session)));

        $this->eventDispatcher->notify(
            'refresh.masterPassword.hash',
            new Event($this, EventMessage::build()->addDescription(__u('Master password hash updated')))
        );

        return ActionResponse::ok(__u('Master password hash updated'));
    }

    /**
     * @return void
     * @throws SessionTimeout
     * @throws UnauthorizedPageException
     * @throws SPException
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_CRYPT);
    }
}
