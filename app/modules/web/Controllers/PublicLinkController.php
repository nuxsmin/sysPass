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
use SP\Account\AccountUtil;
use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\Core\SessionUtil;
use SP\DataModel\PublicLinkListData;
use SP\Forms\PublicLinkForm;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\PublicLink\PublicLinkService;

/**
 * Class PublicLinkController
 *
 * @package SP\Modules\Web\Controllers
 */
class PublicLinkController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait;
    use ItemTrait;

    /**
     * @var PublicLinkService
     */
    protected $publicLinkService;

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_SEARCH)) {
            return;
        }

        $itemsGridHelper = new ItemsGridHelper($this->view, $this->config, $this->session, $this->eventDispatcher);
        $grid = $itemsGridHelper->getPublicLinksGrid($this->publicLinkService->search($this->getSearchData($this->configData)))->updatePager();

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
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Enlace Público'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'publicLink/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.publicLink.create', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(1, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $publicLinkId
     * @throws SPException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($publicLinkId = null)
    {
        $this->view->addTemplate('publiclink', 'itemshow');

        $publicLink = $publicLinkId ? PublicLinkService::mapItemsForList([$this->publicLinkService->getById($publicLinkId)]) : new PublicLinkListData();

        $this->view->assign('publicLink', is_array($publicLink) ? $publicLink[0] : $publicLink);
        $this->view->assign('accounts', AccountUtil::getAccountsForUser($this->session));

        $this->view->assign('sk', SessionUtil::getSessionKey(true));
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('publicLinkURL', PublicLinkService::getLinkForHash($publicLink[0]->getPublicLinkHash()));
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }
    }

    /**
     * Create action
     *
     * @param int $id
     */
    public function refreshAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_REFRESH)) {
            return;
        }

        try {
            $this->publicLinkService->refresh($id);

            $this->eventDispatcher->notifyEvent('edit.publicLink.refresh', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace actualizado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (CryptoException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Edit action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Enlace Público'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'publicLink/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.publicLink.edit', $this);
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
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_DELETE)) {
            return;
        }

        try {
//            $this->publicLinkService->logAction($id, ActionsInterface::PROFILE_DELETE);
            $this->publicLinkService->delete($id);

            $this->deleteCustomFieldsForItem(ActionsInterface::PUBLICLINK, $id);

            $this->eventDispatcher->notifyEvent('delete.publicLink', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace eliminado'));
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves create action
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::USER_CREATE)) {
            return;
        }

        try {
            $form = new PublicLinkForm();
            $form->validate(ActionsInterface::PUBLICLINK_CREATE);

            $this->publicLinkService->create($form->getItemData());
//            $this->publicLinkService->logAction($id, ActionsInterface::PUBLICLINK_CREATE);

            $this->eventDispatcher->notifyEvent('create.publicLink', $this);

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (CryptoException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (SPException $e) {
            debugLog($e->getMessage(), true);

            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {

    }

    /**
     * View action
     *
     * @param $id
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Enlace'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.publicLink', $this);
        } catch (\Exception $e) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        }

        $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * Initialize class
     */
    protected function initialize()
    {
        $this->checkLoggedIn();

        $this->publicLinkService = new PublicLinkService();
    }
}