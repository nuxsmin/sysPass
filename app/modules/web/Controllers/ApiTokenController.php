<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\AuthTokenData;
use SP\Forms\AuthTokenForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\ApiTokens\ApiTokensUtil;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
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
    use JsonTrait;
    use ItemTrait;

    /**
     * @var AuthTokenService
     */
    protected $authTokenService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \SP\Core\Dic\ContainerException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_SEARCH)) {
            return;
        }

        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $grid = $itemsGridHelper->getApiTokensGrid($this->authTokenService->search($this->getSearchData($this->configData)))->updatePager();

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', Request::analyze('activetab', 0));
        $this->view->assign('data', $grid);

        $this->returnJsonResponseData(['html' => $this->render()]);
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

            $this->eventDispatcher->notifyEvent('show.authToken.create', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
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

            $this->eventDispatcher->notifyEvent('show.authToken.edit', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_DELETE)) {
            return;
        }

        try {
            $this->authTokenService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

            $this->eventDispatcher->notifyEvent('delete.authToken', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización eliminada'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves create action
     *
     * @throws \SP\Core\Dic\ContainerException
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

            $this->eventDispatcher->notifyEvent('create.authToken', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización creada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (EnvironmentIsBrokenException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (CryptoException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     * @throws \SP\Core\Dic\ContainerException
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::APITOKEN_EDIT)) {
            return;
        }

        try {
            $form = new AuthTokenForm($id);
            $form->validate(ActionsInterface::APITOKEN_EDIT);

            if ($form->isRefresh()){
                $this->authTokenService->refreshAndUpdate($form->getItemData());

                $this->eventDispatcher->notifyEvent('refresh.authToken', $this);
            } else {
                $this->authTokenService->update($form->getItemData());

                $this->eventDispatcher->notifyEvent('edit.authToken', $this);
            }

            $this->updateCustomFieldsForItem(ActionsInterface::APITOKEN, $id);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Autorización actualizada'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (CryptoException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
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

            $this->eventDispatcher->notifyEvent('show.authToken', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Initialize class
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->authTokenService = $this->dic->get(AuthTokenService::class);
    }
}