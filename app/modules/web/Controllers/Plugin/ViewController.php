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

namespace SP\Modules\Web\Controllers\Plugin;

use Exception;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Plugin\Models\Plugin;
use SP\Domain\Plugin\Ports\PluginManagerService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class ViewController
 */
final class ViewController extends ControllerBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        private readonly PluginManagerService $pluginManagerService
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    #[Action(ResponseType::JSON)]
    public function viewAction(int $id): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::PLUGIN_VIEW)) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        try {
            $this->view->assign('header', __('View Plugin'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notify('show.plugin', new Event($this));

            return ActionResponse::ok('', ['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error($e->getMessage());
        }
    }

    private function setViewData(int $pluginId): void
    {
        $this->view->addTemplate('plugin');

        $plugin = $this->pluginManagerService->getById($pluginId) ?? new Plugin();

        $this->view->assign('plugin', $plugin);
        $this->view->assign('pluginInfo', null);
        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ITEMS_MANAGE));
        $this->view->assign('disabled', 'disabled');
        $this->view->assign('readonly', 'readonly');
    }
}
