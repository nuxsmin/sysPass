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

use PHPMailer\PHPMailer\Exception;
use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class SaveTempController
 */
final class SaveTempController extends SimpleControllerBase
{

    public function __construct(
        Application                                 $application,
        SimpleControllerHelper                      $simpleControllerHelper,
        private readonly TemporaryMasterPassService $temporaryMasterPassService
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * Create a temporary master pass
     *
     * @return ActionResponse
     * @throws Exception
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     */
    #[Action(ResponseType::JSON)]
    public function saveTempAction(): ActionResponse
    {
        $key =
            $this->temporaryMasterPassService->create(
                $this->request->analyzeInt('temporary_masterpass_maxtime', 3600)
            );

        $groupId = $this->request->analyzeInt('temporary_masterpass_group', 0);
        $sendEmail = $this->configData->isMailEnabled()
                     && $this->request->analyzeBool('temporary_masterpass_email');

        if ($sendEmail) {
            if ($groupId > 0) {
                $this->temporaryMasterPassService->sendByEmailForGroup($groupId, $key);
            } else {
                $this->temporaryMasterPassService->sendByEmailForAllUsers($key);
            }

            return ActionResponse::ok(__u('Temporary password generated'), __u('Email sent'));
        }

        return ActionResponse::ok(__u('Temporary password generated'));
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
