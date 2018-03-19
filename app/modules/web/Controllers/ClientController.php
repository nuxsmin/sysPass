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


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ClientData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\ClientForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Client\ClientService;

/**
 * Class ClientController
 *
 * @package SP\Modules\Web\Controllers
 */
class ClientController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var ClientService
     */
    protected $clientService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return $this
     */
    protected function getSearchGrid()
    {
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount());

        return $itemsGridHelper->updatePager($itemsGridHelper->getClientsGrid($this->clientService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Cliente'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'client/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.client.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $clientId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($clientId = null)
    {
        $this->view->addTemplate('client', 'itemshow');

        $client = $clientId ? $this->clientService->getById($clientId) : new ClientData();

        $this->view->assign('client', $client);

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::CLIENT, $clientId, $this->session));
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Cliente'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'client/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.client.edit', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->clientService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::CLIENT, $id);

                $this->eventDispatcher->notifyEvent('delete.client.selection',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Clientes eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Clientes eliminados'));
            } else {
                $this->clientService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::CLIENT, $id);

                $this->eventDispatcher->notifyEvent('delete.client',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Cliente eliminado'))
                            ->addDetail(__u('Cliente'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cliente eliminado'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_CREATE)) {
            return;
        }

        try {
            $form = new ClientForm();
            $form->validate(ActionsInterface::CLIENT_CREATE);

            $itemData = $form->getItemData();

            $this->clientService->create($itemData);

            $this->eventDispatcher->notifyEvent('create.client',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cliente creado'))
                        ->addDetail(__u('Cliente'), $itemData->getName()))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cliente creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_EDIT)) {
            return;
        }

        try {
            $form = new ClientForm($id);
            $form->validate(ActionsInterface::CLIENT_EDIT);

            $this->clientService->update($form->getItemData());

            $this->eventDispatcher->notifyEvent('edit.client',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Cliente actualizado'))
                        ->addDetail(__u('Cliente'), $id))
            );

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Cliente actualizado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * View action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::CLIENT_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Cliente'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.client', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Services\Auth\AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->clientService = $this->dic->get(ClientService::class);
    }
}