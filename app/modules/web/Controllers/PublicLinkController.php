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
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\Grid\PublicLinkGrid;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\PublicLinkForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Account\AccountService;
use SP\Services\PublicLink\PublicLinkService;
use SP\Util\Util;

/**
 * Class PublicLinkController
 *
 * @package SP\Modules\Web\Controllers
 */
final class PublicLinkController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var PublicLinkService
     */
    protected $publicLinkService;

    /**
     * Search action
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_SEARCH)) {
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    protected function getSearchGrid()
    {
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        $publicLinkGrid = $this->dic->get(PublicLinkGrid::class);

        return $publicLinkGrid->updatePager($publicLinkGrid->getGrid($this->publicLinkService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Enlace Público'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'publicLink/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.publicLink.create', new Event($this));

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying public link's data
     *
     * @param $publicLinkId
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws SPException
     */
    protected function setViewData($publicLinkId = null)
    {
        $this->view->addTemplate('public_link', 'itemshow');

        $publicLink = $publicLinkId ? $this->publicLinkService->getById($publicLinkId) : new PublicLinkListData();

        $this->view->assign('publicLink', $publicLink);
        $this->view->assign('usageInfo', unserialize($publicLink->getUseInfo()));
        $this->view->assign('accounts', SelectItemAdapter::factory($this->dic->get(AccountService::class)->getForUser())->getItemsFromModelSelected([$publicLink->getItemId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('publicLinkURL', PublicLinkService::getLinkForHash($publicLink->getHash()));
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
     *
     * @return bool
     */
    public function refreshAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_REFRESH)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $this->publicLinkService->refresh($id);

            $this->eventDispatcher->notifyEvent('edit.publicLink.refresh', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace actualizado'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
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
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_EDIT)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Editar Enlace Público'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'publicLink/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.publicLink.edit', new Event($this));

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
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_DELETE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            if ($id === null) {
                $this->publicLinkService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::PUBLICLINK, $id);

                $this->eventDispatcher->notifyEvent('delete.publicLink.selection',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Enlaces eliminados')))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlaces eliminados'));
            } else {
                $this->publicLinkService->delete($id);

                $this->deleteCustomFieldsForItem(Acl::PUBLICLINK, $id);

                $this->eventDispatcher->notifyEvent('delete.publicLink',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Enlace eliminado'))
                        ->addDetail(__u('Enlace'), $id))
                );

                return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace eliminado'));
            }
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
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $form = new PublicLinkForm($this->dic);
            $form->validate(Acl::PUBLICLINK_CREATE);

            $this->publicLinkService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.publicLink', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace creado'));
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     *
     * @param int $accountId
     * @param int $notify
     *
     * @return bool
     */
    public function saveCreateFromAccountAction($accountId, $notify)
    {
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_CREATE)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        try {
            $publicLinkData = new PublicLinkData();
            $publicLinkData->setTypeId(PublicLinkService::TYPE_ACCOUNT);
            $publicLinkData->setItemId($accountId);
            $publicLinkData->setNotify((bool)$notify);
            $publicLinkData->setHash(Util::generateRandomBytes());

            $this->publicLinkService->create($publicLinkData);

            $this->eventDispatcher->notifyEvent('create.publicLink.account', new Event($this));

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace creado'));
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id)
    {
        throw new \RuntimeException('Not implemented');
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
        if (!$this->acl->checkUserAccess(Acl::PUBLICLINK_VIEW)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('No tiene permisos para realizar esta operación'));
        }

        $this->view->assign('header', __('Ver Enlace'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.publicLink', new Event($this));

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

        $this->publicLinkService = $this->dic->get(PublicLinkService::class);
    }
}