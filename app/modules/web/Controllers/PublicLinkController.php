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
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\PublicLinkData;
use SP\DataModel\PublicLinkListData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Mgmt\PublicLinks\PublicLink;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
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
class PublicLinkController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var PublicLinkService
     */
    protected $publicLinkService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_SEARCH)) {
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

        return $itemsGridHelper->updatePager($itemsGridHelper->getPublicLinksGrid($this->publicLinkService->search($itemSearchData)), $itemSearchData);
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

            $this->eventDispatcher->notifyEvent('show.publicLink.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $publicLinkId
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws SPException
     */
    protected function setViewData($publicLinkId = null)
    {
        $this->view->addTemplate('publiclink', 'itemshow');

        $publicLink = $publicLinkId ? $this->publicLinkService->getById($publicLinkId) : new PublicLinkListData();

        $this->view->assign('publicLink', $publicLink);
        $this->view->assign('usageInfo', unserialize($publicLink->getUseInfo()));
        $this->view->assign('accounts', SelectItemAdapter::factory($this->dic->get(AccountService::class)->getForUser())->getItemsFromModelSelected([$publicLink->getItemId()]));

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

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
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function refreshAction($id)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_REFRESH)) {
            return;
        }

        try {
            $this->publicLinkService->refresh($id);

            $this->eventDispatcher->notifyEvent('edit.publicLink.refresh', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace actualizado'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
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

            $this->eventDispatcher->notifyEvent('show.publicLink.edit', new Event($this));

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
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->publicLinkService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::PUBLICLINK, $id);

                $this->eventDispatcher->notifyEvent('delete.publicLink.selection',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Enlaces eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlaces eliminados'));
            } else {
                $this->publicLinkService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::PUBLICLINK, $id);

                $this->eventDispatcher->notifyEvent('delete.publicLink',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Enlace eliminado'))
                        ->addDetail(__u('Enlace'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace eliminado'));
            }
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveCreateAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_CREATE)) {
            return;
        }

        try {
            $form = new PublicLinkForm();
            $form->validate(ActionsInterface::PUBLICLINK_CREATE);

            $this->publicLinkService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.publicLink', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace creado'));
        } catch (ValidationException $e) {
            $this->returnJsonResponseException($e);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves create action
     *
     * @param int $accountId
     * @param int $notify
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveCreateFromAccountAction($accountId, $notify)
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PUBLICLINK_CREATE)) {
            return;
        }

        try {
            $publicLinkData = new PublicLinkData();
            $publicLinkData->setTypeId(PublicLink::TYPE_ACCOUNT);
            $publicLinkData->setItemId($accountId);
            $publicLinkData->setNotify((bool)$notify);
            $publicLinkData->setHash(Util::generateRandomBytes());

            $this->publicLinkService->create($publicLinkData);

            $this->eventDispatcher->notifyEvent('create.publicLink.account', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Enlace creado'));
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
        throw new \RuntimeException('Not implemented');
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

            $this->eventDispatcher->notifyEvent('show.publicLink', new Event($this));

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

        $this->publicLinkService = $this->dic->get(PublicLinkService::class);
    }
}