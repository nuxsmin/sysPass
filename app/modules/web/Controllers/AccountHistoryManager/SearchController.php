<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\AccountHistoryManager;

use JsonException;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Domain\Account\Ports\AccountHistoryServiceInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountHistoryGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class SearchController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SearchController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private AccountHistoryServiceInterface $accountHistoryService;
    private AccountHistoryGrid             $accountHistoryGrid;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountHistoryServiceInterface $accountHistoryService,
        Helpers\Grid\AccountHistoryGrid $accountHistoryGrid
    ) {
        $this->accountHistoryService = $accountHistoryService;
        $this->accountHistoryGrid = $accountHistoryGrid;

        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }


    /**
     * @return bool
     * @throws JsonException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::ACCOUNTMGR_HISTORY_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        return $this->accountHistoryGrid->updatePager(
            $this->accountHistoryGrid->getGrid($this->accountHistoryService->search($itemSearchData)),
            $itemSearchData
        );
    }
}
