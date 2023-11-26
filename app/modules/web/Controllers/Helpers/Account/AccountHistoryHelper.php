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

use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Models\AccountHistory;
use SP\Domain\Account\Ports\AccountAclServiceInterface;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Account\Ports\AccountToUserGroupServiceInterface;
use SP\Domain\Account\Ports\AccountToUserServiceInterface;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Category\Ports\CategoryServiceInterface;
use SP\Domain\Client\Ports\ClientServiceInterface;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Http\RequestInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class AccountHistoryHelper
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
final class AccountHistoryHelper extends AccountHelperBase
{
    private ?int        $accountId  = null;
    private ?AccountAcl $accountAcl = null;

    public function __construct(
        Application $application,
        TemplateInterface $template,
        RequestInterface $request,
        Acl $acl,
        AccountActionsHelper $accountActionsHelper,
        MasterPassServiceInterface $masterPassService,
        private AccountHistoryServiceInterface $accountHistoryService,
        private AccountAclServiceInterface $accountAclService,
        private CategoryServiceInterface $categoryService,
        private ClientServiceInterface $clientService,
        private AccountToUserServiceInterface $accountToUserService,
        private AccountToUserGroupServiceInterface $accountToUserGroupService
    ) {
        parent::__construct($application, $template, $request, $acl, $accountActionsHelper, $masterPassService);
    }

    /**
     * @param AccountHistory $accountHistoryData
     * @param  int  $actionId
     *
     * @throws AccountPermissionException
     * @throws UnauthorizedPageException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     * @throws UpdatedMasterPassException
     * @throws NoSuchItemException
     */
    public function setView(AccountHistory $accountHistoryData, int $actionId): void
    {
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
            SelectItemAdapter::factory(self::mapHistoryForDateSelect($this->accountHistoryService->getHistoryForAccount($this->accountId)))
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
     * @param  AccountHistory  $accountHistoryData
     *
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    protected function checkAccess(AccountHistory $accountHistoryData): void
    {
        $acccountAclDto = AccountAclDto::makeFromAccountHistory(
            $accountHistoryData,
            $this->accountToUserService->getUsersByAccountId($this->accountId),
            $this->accountToUserGroupService->getUserGroupsByAccountId($this->accountId)
        );

        $this->accountAcl = $this->accountAclService->getAcl($this->actionId, $acccountAclDto, true);

        if ($this->accountAcl->checkAccountAccess($this->actionId) === false) {
            throw new AccountPermissionException(SPException::INFO);
        }
    }

    /**
     * Maps history items to fill in a date select
     */
    public static function mapHistoryForDateSelect(array $history): array
    {
        $values = array_map(static function ($item) {
            // Comprobamos si la entrada en el historial es la primera (no tiene editor ni fecha de edición)
            if (empty($item->dateEdit) || $item->dateEdit === '0000-00-00 00:00:00') {
                return sprintf('%s - %s', $item->dateAdd, $item->userAdd);
            }

            return sprintf('%s - %s', $item->dateEdit, $item->userEdit);
        }, $history);

        $keys = array_map(static fn($item) => $item->id, $history);

        return array_combine($keys, $values);
    }
}
