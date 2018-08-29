<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\AuthTokenData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\AuthTokenGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\AuthTokenForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\AuthToken\AuthTokenService;
use SP\Services\User\UserService;

/**
 * Class AuthTokenController
 *
 * @package SP\Modules\Web\Controllers
 */
final class AuthTokenController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var AuthTokenService
     */
    protected $authTokenService;

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_SEARCH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @return $this
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $authTokenGrid = $this->dic->get(AuthTokenGrid::class);

        return $authTokenGrid->updatePager($authTokenGrid->getGrid($this->authTokenService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Autorización'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'authToken/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.authToken.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying auth token's data
     *
     * @param $authTokenId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    protected function setViewData($authTokenId = null)
    {
        $this->view->addTemplate('auth_token', 'itemshow');

        $authToken = $authTokenId ? $this->authTokenService->getById($authTokenId) : new AuthTokenData();

        $this->view->assign('authToken', $authToken);

        $this->view->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModelSelected([$authToken->getUserId()]));
        $this->view->assign('actions', SelectItemAdapter::factory(AuthTokenService::getTokenActions())->getItemsFromArraySelected([$authToken->getActionId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('customFields', $this->getCustomFieldsForItem(Acl::AUTHTOKEN, $authTokenId));
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Autorización'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'authToken/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.authToken.edit', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param $id
     *
     * @return bool
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->authTokenService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::AUTHTOKEN, $id);

                $this->eventDispatcher->notifyEvent('delete.authToken.selection',
                    new Event($this,
                        EventMessage::factory()
                            ->addDescription(__u('Autorizaciones eliminadas')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorizaciones eliminadas'));
            }

            $this->authTokenService->delete($id);

            $this->deleteCustomFieldsForItem(Acl::AUTHTOKEN, $id);

            $this->eventDispatcher->notifyEvent('delete.authToken',
                new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Autorización eliminada'))
                        ->addDetail(__u('Autorización'), $id))
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización eliminada'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new AuthTokenForm($this->dic);
            $form->validate(Acl::AUTHTOKEN_CREATE);

            $apiTokenData = $form->getItemData();

            $id = $this->authTokenService->create($apiTokenData);

            $this->addCustomFieldsForItem(Acl::AUTHTOKEN, $id, $this->request);

            $this->eventDispatcher->notifyEvent('create.authToken', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización creada'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     *
     * @return bool
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new AuthTokenForm($this->dic, $id);
            $form->validate(Acl::AUTHTOKEN_EDIT);

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

            $this->updateCustomFieldsForItem(Acl::AUTHTOKEN, $id, $this->request);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización actualizada'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * View action
     *
     * @param $id
     *
     * @return bool
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::AUTHTOKEN_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Autorización'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.authToken',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Autorización visualizada'))
                    ->addDetail(__u('Autorización'), $id))
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
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

        $this->authTokenService = $this->dic->get(AuthTokenService::class);
    }
}