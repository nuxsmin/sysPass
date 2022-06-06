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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\DataModel\Dto\AccountAclDto;
use SP\Domain\Account\AccountAclServiceInterface;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Category\Services\CategoryService;
use SP\Domain\Client\Services\ClientService;
use SP\Domain\Crypt\MasterPassServiceInterface;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class AccountHistoryHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountHistoryHelper extends HelperBase
{
    private Acl                                               $acl;
    private \SP\Domain\Account\AccountHistoryServiceInterface $accountHistoryService;
    private AccountActionsHelper                              $accountActionsHelper;
    private MasterPassServiceInterface     $masterPassService;
    private AccountAclServiceInterface     $accountAclService;
    private ?int                           $accountId  = null;
    private ?int                           $actionId   = null;
    private ?AccountAcl                    $accountAcl = null;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        Acl $acl,
        \SP\Domain\Account\AccountHistoryServiceInterface $accountHistoryService,
        AccountActionsHelper $accountActionsHelper,
        MasterPassServiceInterface $masterPassService,
        AccountAclServiceInterface $accountAclService
    ) {
        $this->acl = $acl;
        $this->accountHistoryService = $accountHistoryService;
        $this->accountActionsHelper = $accountActionsHelper;
        $this->masterPassService = $masterPassService;
        $this->accountAclService = $accountAclService;

        parent::__construct($application, $template, $request);
    }


    /**
     * @param  AccountHistoryData  $accountHistoryData
     * @param  int  $actionId
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Acl\AccountPermissionException
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     */
    public function setView(
        AccountHistoryData $accountHistoryData,
        int $actionId
    ): void {
        $this->actionId = $actionId;
        $this->accountId = $accountHistoryData->getAccountId();

        $this->checkActionAccess();
        $this->checkAccess($accountHistoryData);

        $this->view->assign('isView', true);
        $this->view->assign('accountIsHistory', true);
        $this->view->assign('accountData', $accountHistoryData);
        $this->view->assign('accountAcl', $this->accountAcl);
        $this->view->assign('actionId', $this->actionId);
        $this->view->assign('accountId', $this->accountId);

        $this->view->assign(
            'historyData',
            SelectItemAdapter::factory($this->accountHistoryService->getHistoryForAccount($this->accountId))
                ->getItemsFromArraySelected([$accountHistoryData->getId()])
        );

        $this->view->assign(
            'accountPassDate',
            date('Y-m-d H:i:s', $accountHistoryData->getPassDate())
        );
        $this->view->assign(
            'accountPassDateChange',
            date('Y-m-d', $accountHistoryData->getPassDateChange() ?: 0)
        );
        $this->view->assign(
            'categories',
            // FIXME: use IoC
            SelectItemAdapter::factory(CategoryService::getItemsBasic())
                ->getItemsFromModelSelected([$accountHistoryData->getCategoryId()])
        );
        $this->view->assign(
            'clients',
            // FIXME: use IoC
            SelectItemAdapter::factory(ClientService::getItemsBasic())
                ->getItemsFromModelSelected([$accountHistoryData->getClientId()])
        );
        $this->view->assign(
            'isModified',
            strtotime($accountHistoryData->getDateEdit()) !== false
        );

        $accountActionsDto = new AccountActionsDto(
            $this->accountId,
            $accountHistoryData->getId(),
            0
        );

        $this->view->assign(
            'accountActions',
            $this->accountActionsHelper->getActionsForAccount($this->accountAcl, $accountActionsDto)
        );
        $this->view->assign(
            'accountActionsMenu',
            $this->accountActionsHelper->getActionsGrouppedForAccount($this->accountAcl, $accountActionsDto)
        );
    }

    /**
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     */
    protected function checkActionAccess(): void
    {
        if (!$this->acl->checkUserAccess($this->actionId)) {
            throw new UnauthorizedPageException(SPException::INFO);
        }

        if (!$this->masterPassService->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())) {
            throw new UpdatedMasterPassException(SPException::INFO);
        }
    }

    /**
     * Comprobar si el usuario dispone de acceso al módulo
     *
     * @param  AccountHistoryData  $accountHistoryData
     *
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function checkAccess(AccountHistoryData $accountHistoryData): void
    {
        $acccountAclDto = AccountAclDto::makeFromAccountHistory(
            $accountHistoryData,
            $this->accountHistoryService->getUsersByAccountId($this->accountId),
            $this->accountHistoryService->getUserGroupsByAccountId($this->accountId)
        );

        $this->accountAcl = $this->accountAclService->getAcl($this->actionId, $acccountAclDto, true);

        if ($this->accountAcl->checkAccountAccess($this->actionId) === false) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }
}