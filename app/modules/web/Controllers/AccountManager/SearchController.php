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

namespace SP\Modules\Web\Controllers\AccountManager;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Domain\Account\AccountSearchServiceInterface;
use SP\Domain\Account\AccountServiceInterface;
use SP\Domain\Account\Search\AccountSearchFilter;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers;
use SP\Modules\Web\Controllers\Helpers\Grid\AccountGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class AccountManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SearchController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    private AccountServiceInterface       $accountService;
    private AccountSearchServiceInterface $accountSearchService;
    private AccountGrid                   $accountGrid;

    /**
     * @throws \SP\Core\Exceptions\SessionTimeout
     * @throws \SP\Domain\Auth\Services\AuthException
     */
    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountSearchServiceInterface $accountSearchService,
        Helpers\Grid\AccountGrid $accountGrid
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->accountSearchService = $accountSearchService;
        $this->accountGrid = $accountGrid;

        $this->checkLoggedIn();
    }

    /**
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::ACCOUNTMGR_SEARCH)) {
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
     * @return \SP\Html\DataGrid\DataGridInterface
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $filter = AccountSearchFilter::build($itemSearchData->getSeachString())
            ->setLimitCount($itemSearchData->getLimitCount())
            ->setLimitStart($itemSearchData->getLimitStart());

        return $this->accountGrid->updatePager(
            $this->accountGrid->getGrid($this->accountSearchService->getByFilter($filter)),
            $itemSearchData
        );
    }
}