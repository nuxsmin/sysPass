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

namespace SP\Modules\Web\Controllers\Plugin;


use Exception;
use JsonException;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\PluginManagerInterface;
use SP\Http\JsonMessage;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Plugin\Repositories\PluginModel;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Plugin\PluginManager;

/**
 * Class ViewController
 */
final class ViewController extends ControllerBase
{
    use JsonTrait;

    private PluginManagerInterface $pluginService;
    private PluginManager          $pluginManager;

    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        PluginManagerInterface $pluginService,
        PluginManager          $pluginManager
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->pluginService = $pluginService;
        $this->pluginManager = $pluginManager;
    }

    /**
     * View action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::PLUGIN_VIEW)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View Plugin'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notify('show.plugin', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying items's data
     *
     * @param  int|null  $pluginId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $pluginId = null): void
    {
        $this->view->addTemplate('plugin');

        $pluginData = $pluginId
            ? $this->pluginService->getById($pluginId)
            : new PluginModel();
        $pluginInfo = $this->pluginManager->getPlugin($pluginData->getName());

        $this->view->assign('plugin', $pluginData);
        $this->view->assign('pluginInfo', $pluginInfo);

        $this->view->assign('nextAction', Acl::getActionRoute(AclActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}
