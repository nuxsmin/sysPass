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
use SP\DataModel\AuthTokenData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\ApiTokens\ApiTokensUtil;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AuthTokenForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\User\UserService;

/**
 * Class ApiTokenController
 *
 * @package SP\Modules\Web\Controllers
 */
class ApiTokenController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var AuthTokenService
     */
    protected $authTokenService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_SEARCH)) {
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

        return $itemsGridHelper->updatePager($itemsGridHelper->getApiTokensGrid($this->authTokenService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Autorización'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'apiToken/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.authToken.create', new Event($this));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $authTokenId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($authTokenId = null)
    {
        $this->view->addTemplate('authtoken', 'itemshow');

        $authToken = $authTokenId ? $this->authTokenService->getById($authTokenId) : new AuthTokenData();

        $this->view->assign('authToken', $authToken);

        $this->view->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected([$authToken->getUserId()]));
        $this->view->assign('actions', SelectItemAdapter::factory(ApiTokensUtil::getTokenActions())->getItemsFromArraySelected([$authToken->getActionId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(ActionsInterface::APITOKEN, $authTokenId));
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Autorización'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'apiToken/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.authToken.edit', new Event($this));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
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
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->authTokenService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

                $this->eventDispatcher->notifyEvent('delete.authToken.selection',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Autorizaciones eliminadas')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorizaciones eliminadas'));
            } else {
                $this->authTokenService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

                $this->eventDispatcher->notifyEvent('delete.authToken',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Autorización eliminada'))
                            ->addDetail(__u('Autorización'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización eliminada'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_CREATE)) {
            return;
        }

        try {
            $form = new AuthTokenForm();
            $form->validate(ActionsInterface::APITOKEN_CREATE);

            $apiTokenData = $form->getItemData();

            $id = $this->authTokenService->create($apiTokenData);

            $this->addCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

            $this->eventDispatcher->notifyEvent('create.authToken', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización creada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_EDIT)) {
            return;
        }

        try {
            $form = new AuthTokenForm($id);
            $form->validate(ActionsInterface::APITOKEN_EDIT);

            if ($form->isRefresh()) {
                $this->authTokenService->refreshAndUpdate($form->getItemData());

                $this->eventDispatcher->notifyEvent('refresh.authToken',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Autorización actualizada'))
                            ->addDetail(__u('Autorización'), $id))
                );
            } else {
                $this->authTokenService->update($form->getItemData());

                $this->eventDispatcher->notifyEvent('edit.authToken',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Autorización actualizada'))
                            ->addDetail(__u('Autorización'), $id))
                );
            }

            $this->updateCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización actualizada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Autorización'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.authToken',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Autorización visualizada'))
                        ->addDetail(__u('Autorización'), $id))
            );
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
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

        $this->authTokenService = $this->dic->get(AuthTokenService::class);
    }
}