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
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\TagData;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\TagForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\Tag\TagService;

/**
 * Class TagController
 *
 * @package SP\Modules\Web\Controllers
 */
class TagController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var TagService
     */
    protected $tagService;

    /**
     * Search action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_SEARCH)) {
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

        return $itemsGridHelper->updatePager($itemsGridHelper->getTagsGrid($this->tagService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nueva Etiqueta'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'tag/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.tag.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user's data
     *
     * @param $tagId
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function setViewData($tagId = null)
    {
        $this->view->addTemplate('tag', 'itemshow');

        $tag = $tagId ? $this->tagService->getById($tagId) : new TagData();

        $this->view->assign('tag', $tag);

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
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
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Etiqueta'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'tag/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.tag.edit', new Event($this));

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
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->tagService->deleteByIdBatch($this->getItemsIdFromRequest());

                $this->deleteCustomFieldsForItem(ActionsInterface::TAG, $id);

                $this->eventDispatcher->notifyEvent('delete.tag.selection', new Event($this));

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Etiquetas eliminadas'));
            } else {
                $this->tagService->delete($id);

                $this->deleteCustomFieldsForItem(ActionsInterface::TAG, $id);

                $this->eventDispatcher->notifyEvent('delete.tag', new Event($this));

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Etiqueta eliminada'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_CREATE)) {
            return;
        }

        try {
            $form = new TagForm();
            $form->validate(ActionsInterface::TAG_CREATE);

            $this->tagService->create($form->getItemData());

            $this->eventDispatcher->notifyEvent('create.tag', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Etiqueta creada'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_EDIT)) {
            return;
        }

        try {
            $form = new TagForm($id);
            $form->validate(ActionsInterface::TAG_EDIT);

            $this->tagService->update($form->getItemData());

            $this->eventDispatcher->notifyEvent('edit.tag', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Etiqueta actualizada'));
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
        if (!$this->acl->checkUserAccess(ActionsInterface::TAG_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Etiqueta'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.tag', new Event($this));

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

        $this->tagService = $this->dic->get(TagService::class);
    }
}