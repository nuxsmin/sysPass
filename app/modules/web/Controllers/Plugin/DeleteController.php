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
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Plugin\Ports\PluginServiceInterface;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class DeleteController
 */
final class DeleteController extends ControllerBase
{
    use ItemTrait;
    use JsonTrait;

    private PluginServiceInterface $pluginService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        PluginServiceInterface $pluginService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->pluginService = $pluginService;
    }

    /**
     * resetAction
     *
     * @param  int|null  $id
     *
     * @return bool
     * @throws JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(AclActionsInterface::PLUGIN_DELETE)) {
                return $this->returnJsonResponse(
                    JsonMessage::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->pluginService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notify('delete.plugin.selection', new Event($this));

                return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Plugins deleted'));
            }

            $this->pluginService->delete($id);

            $this->eventDispatcher->notify('delete.plugin', new Event($this));

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Plugin deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
