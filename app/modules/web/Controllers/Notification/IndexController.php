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

namespace SP\Modules\Web\Controllers\Notification;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Database\Dtos\QueryResult;
use SP\Domain\Notification\UseCases\Dtos\SearchNotificationsCommand;
use SP\Domain\Notification\UseCases\SearchNotificationsUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Presenters\Helpers\Grid\NotificationGrid;
use SP\Modules\Web\Html\DataGrid\DataGridInterface;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class IndexController
 */
final class IndexController extends ControllerBase
{
    use ItemTrait;

    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly SearchNotificationsUseCase $searchNotificationsUseCase,
        private readonly NotificationGrid $notificationGrid
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    #[Action(ResponseType::PLAIN_TEXT)]
    public function indexAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::NOTIFICATION)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        $this->view->addTemplate('index');
        $this->view->assign('data', $this->getSearchGrid());

        return ActionResponse::ok($this->render());
    }

    private function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $result = $this->searchNotificationsUseCase->execute(new SearchNotificationsCommand($itemSearchData));

        return $this->notificationGrid->updatePager(
            $this->notificationGrid->getGrid(
                QueryResult::withTotalNumRows($result->notifications, $result->total)
            ),
            $itemSearchData
        );
    }
}
