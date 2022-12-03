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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Core\Acl\AccountPermissionException;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\AccountHistoryData;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Ports\AccountAclServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Category\Ports\CategoryServiceInterface;
use SP\Domain\Client\Ports\ClientServiceInterface;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Http\RequestInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class AccountHistoryHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountHistoryHelper extends AccountHelperBase
{
    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountAclServiceInterface     $accountAclService;
    private ?int                           $accountId  = null;
    private ?AccountAcl                    $accountAcl = null;
    private CategoryServiceInterface       $categoryService;
    private ClientServiceInterface         $clientService;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        Acl $acl,
        \SP\Domain\Account\Ports\AccountHistoryServiceInterface $accountHistoryService,
        AccountActionsHelper $accountActionsHelper,
        MasterPassServiceInterface $masterPassService,
        AccountAclServiceInterface $accountAclService,
        CategoryServiceInterface $categoryService,
        ClientServiceInterface $clientService
    ) {
        parent::__construct($application, $template, $request, $acl, $accountActionsHelper, $masterPassService);

        $this->accountHistoryService = $accountHistoryService;
        $this->accountAclService = $accountAclService;
        $this->categoryService = $categoryService;
        $this->clientService = $clientService;
    }


    /**
     * @param  AccountHistoryData  $accountHistoryData
     * @param  int  $actionId
     *
     * @throws \SP\Core\Acl\AccountPermissionException
     * @throws \SP\Core\Acl\UnauthorizedPageException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Domain\User\Services\UpdatedMasterPassException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
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

        $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountHistoryData->getPassDate()));
        $this->view->assign(
            'accountPassDateChange',
            date('Y-m-d', $accountHistoryData->getPassDateChange() ?: 0)
        );
        $this->view->assign(
            'categories',
            SelectItemAdapter::factory($this->categoryService->getAllBasic())
                ->getItemsFromModelSelected([$accountHistoryData->getCategoryId()])
        );
        $this->view->assign(
            'clients',
            SelectItemAdapter::factory($this->clientService->getAllBasic())
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
