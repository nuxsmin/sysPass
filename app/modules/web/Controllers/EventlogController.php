<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\EventLog\EventlogService;

/**
 * Class EventlogController
 *
 * @package SP\Modules\Web\Controllers
 */
class EventlogController extends ControllerBase
{
    use JsonTrait, ItemTrait;

    /**
     * @var EventlogService
     */
    protected $eventLogService;

    /**
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::EVENTLOG)) {
            return;
        }

        $this->view->addTemplate('index');

        $this->view->assign('data', $this->getSearchGrid());

        $this->view();
    }

    /**
     * @return $this
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getSearchGrid()
    {
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData);

        return $itemsGridHelper->updatePager($itemsGridHelper->getEventLogGrid($this->eventLogService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::EVENTLOG_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table-simple', 'grid');
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * clearAction
     */
    public function clearAction()
    {
        try {
            $this->eventLogService->clear();

            $this->eventDispatcher->notifyEvent('show.account.search',
                new Event($this, EventMessage::factory()->addDescription(__u('Registro de eventos vaciado')))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Registro de eventos vaciado'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    protected function initialize()
    {
        $this->eventLogService = $this->dic->get(EventlogService::class);
    }
}