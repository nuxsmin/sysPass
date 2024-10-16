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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use DateTime;
use SP\Core\Application;
use SP\Domain\Account\Adapters\AccountPermission;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Dtos\AccountHistoryViewDto;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountHistoryService;
use SP\Domain\Account\Ports\AccountToUserGroupService;
use SP\Domain\Account\Ports\AccountToUserService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Core\Acl\AccountPermissionException;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Http\Ports\RequestService;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class AccountHistoryHelper
 */
final class AccountHistoryHelper extends AccountHelperBase
{
    private ?int               $accountId         = null;
    private ?AccountPermission $accountPermission = null;

    public function __construct(
        Application                                $application,
        TemplateInterface                          $template,
        RequestService                             $request,
        AclInterface                               $acl,
        AccountActionsHelper                       $accountActionsHelper,
        MasterPassService                          $masterPassService,
        private readonly AccountHistoryService     $accountHistoryService,
        private readonly AccountAclService         $accountAclService,
        private readonly CategoryService           $categoryService,
        private readonly ClientService             $clientService,
        private readonly AccountToUserService      $accountToUserService,
        private readonly AccountToUserGroupService $accountToUserGroupService
    ) {
        parent::__construct($application, $template, $request, $acl, $accountActionsHelper, $masterPassService);
    }

    /**
     * @param AccountHistoryViewDto $accountHistoryViewDto
     *
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws UnauthorizedActionException
     */
    public function setViewForAccount(AccountHistoryViewDto $accountHistoryViewDto): void
    {
        if (!$this->actionGranted) {
            throw new UnauthorizedActionException();
        }

        $this->accountId = $accountHistoryViewDto->accountId;

        $this->checkAccess($accountHistoryViewDto);

        $this->view->assign('isView', true);
        $this->view->assign('accountIsHistory', true);
        $this->view->assign('accountData', $accountHistoryViewDto);
        $this->view->assign('accountAcl', $this->accountPermission);
        $this->view->assign('actionId', $this->actionId);
        $this->view->assign('accountId', $this->accountId);

        $this->view->assign(
            'historyData',
            SelectItemAdapter::factory(
                self::mapHistoryForDateSelect($this->accountHistoryService->getHistoryForAccount($this->accountId))
            )->getItemsFromArraySelected([$accountHistoryViewDto->id])
        );

        $this->view->assign('accountPassDate', date('Y-m-d H:i:s', $accountHistoryViewDto->passDate));
        $this->view->assign(
            'accountPassDateChange',
            date('Y-m-d', $accountHistoryViewDto->passDateChange ?: 0)
        );
        $this->view->assign(
            'categories',
            SelectItemAdapter::factory($this->categoryService->getAll())
                ->getItemsFromModelSelected([$accountHistoryViewDto->categoryId])
        );
        $this->view->assign(
            'clients',
            SelectItemAdapter::factory($this->clientService->getAll())
                ->getItemsFromModelSelected([$accountHistoryViewDto->clientId])
        );
        $this->view->assign(
            'isModified',
            strtotime($accountHistoryViewDto->dateEdit) !== false
        );

        $accountActionsDto = new AccountActionsDto($this->accountId, $accountHistoryViewDto->id, 0);

        $this->view->assign(
            'accountActions',
            $this->accountActionsHelper->getActionsForAccount($this->accountPermission, $accountActionsDto)
        );
        $this->view->assign(
            'accountActionsMenu',
            $this->accountActionsHelper->getActionsGrouppedForAccount($this->accountPermission, $accountActionsDto)
        );
    }

    /**
     * @throws AccountPermissionException
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    protected function checkAccess(AccountHistoryViewDto $accountHistoryViewDto): void
    {
        $acccountAclDto = new AccountAclDto(
            $this->accountId,
            $accountHistoryViewDto->userId,
            $this->accountToUserService->getUsersByAccountId($this->accountId),
            $accountHistoryViewDto->userGroupId,
            $this->accountToUserGroupService->getUserGroupsByAccountId($this->accountId),
            DateTime::createFromFormat('Y-m-d H:i:s', $accountHistoryViewDto->dateEdit)->getTimestamp()
        );

        $this->accountPermission = $this->accountAclService->getAcl($this->actionId, $acccountAclDto, true);

        if ($this->accountPermission->checkAccountAccess($this->actionId) === false) {
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
