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

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Crypt\Services\MasterPass;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class SaveController
 */
final class SaveController extends SimpleControllerBase
{
    use JsonTrait;

    public function __construct(
        Application                        $application,
        SimpleControllerHelper             $simpleControllerHelper,
        private readonly MasterPassService $masterPassService,
        private readonly ConfigService     $configService
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @return ActionResponse
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws ConstraintException
     * @throws QueryException
     * @throws Exception
     */
    #[Action(ResponseType::JSON)]
    public function saveAction(): ActionResponse
    {
        $currentMasterPass = $this->request->analyzeEncrypted('current_masterpass');
        $newMasterPass = $this->request->analyzeEncrypted('new_masterpass');
        $newMasterPassR = $this->request->analyzeEncrypted('new_masterpass_repeat');
        $confirmPassChange = $this->request->analyzeBool('confirm_masterpass_change', false);
        $noAccountPassChange = $this->request->analyzeBool('no_account_change', false);


        if (!$this->masterPassService->checkUserUpdateMPass($this->session->getUserData()->lastUpdateMPass)) {
            return ActionResponse::ok(__u('Master password updated'), __u('Please, restart the session for update it'));
        }

        if (empty($newMasterPass) || empty($currentMasterPass)) {
            return ActionResponse::error(__u('Master password not entered'));
        }

        if ($confirmPassChange === false) {
            return ActionResponse::ok(__u('The password update must be confirmed'));
        }

        if ($newMasterPass === $currentMasterPass) {
            return ActionResponse::ok(__u('Passwords are the same'));
        }

        if ($newMasterPass !== $newMasterPassR) {
            return ActionResponse::ok(__u('Master passwords do not match'));
        }

        if (!$this->masterPassService->checkMasterPassword($currentMasterPass)) {
            return ActionResponse::ok(__u('The current master password does not match'));
        }

        if (!$this->config->getConfigData()->isMaintenance()) {
            return ActionResponse::warning(
                __u('Maintenance mode not enabled'),
                __u('Please, enable it to avoid unwanted behavior from other sessions')
            );
        }

        if ($this->config->getConfigData()->isDemoEnabled()) {
            return ActionResponse::warning(__u('Ey, this is a DEMO!!'));
        }

        if (!$noAccountPassChange) {
            $request = new UpdateMasterPassRequest(
                $currentMasterPass,
                $newMasterPass,
                $this->configService->getByParam(MasterPass::PARAM_MASTER_PASS_HASH),
            );

            $this->eventDispatcher->notify('update.masterPassword.start', new Event($this));

            $this->masterPassService->changeMasterPassword($request);

            $this->eventDispatcher->notify('update.masterPassword.end', new Event($this));
        } else {
            $this->eventDispatcher->notify('update.masterPassword.hash', new Event($this));

            $this->masterPassService->updateConfig(Hash::hashKey($newMasterPass));
        }

        return ActionResponse::ok(__u('Master password updated'), __u('Please, restart the session to update it'));
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
