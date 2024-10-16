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

namespace SP\Modules\Web\Controllers\Track;


use JsonException;
use SP\Core\Application;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedActionException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Security\Ports\TrackService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\Grid\TrackGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class SearchController
 */
final class SearchController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private TrackService $trackService;
    private TrackGrid    $trackGrid;

    public function __construct(
        Application  $application,
        WebControllerHelper $webControllerHelper,
        TrackService $trackService,
        TrackGrid    $trackGrid
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->trackService = $trackService;
        $this->trackGrid = $trackGrid;
    }

    /**
     * Search action
     *
     * @return bool
     * @throws JsonException
     * @throws UnauthorizedActionException
     * @throws ConstraintException
     * @throws QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::TRACK_SEARCH)) {
            throw new UnauthorizedActionException(SPException::ERROR);
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return DataGridInterface
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        return $this->trackGrid->updatePager(
            $this->trackGrid->getGrid($this->trackService->search($itemSearchData)),
            $itemSearchData
        );
    }
}
