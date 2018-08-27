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

namespace SP\Modules\Api\Controllers;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\ItemSearchData;
use SP\DataModel\TagData;
use SP\Modules\Api\Controllers\Help\TagHelp;
use SP\Services\Api\ApiResponse;
use SP\Services\Tag\TagService;

/**
 * Class TagController
 *
 * @package SP\Modules\Api\Controllers
 */
final class TagController extends ControllerBase
{
    /**
     * @var TagService
     */
    private $tagService;

    /**
     * viewAction
     */
    public function viewAction()
    {
        try {
            $this->setupApi(ActionsInterface::TAG_VIEW);

            $id = $this->apiService->getParamInt('id', true);
            $tagData = $this->tagService->getById($id);

            $this->eventDispatcher->notifyEvent('show.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Etiqueta visualizada'))
                    ->addDetail(__u('Etiqueta'), $tagData->getName()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $id));
        } catch (\Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * createAction
     */
    public function createAction()
    {
        try {
            $this->setupApi(ActionsInterface::TAG_CREATE);

            $tagData = new TagData();
            $tagData->setName($this->apiService->getParamString('name', true));

            $tagId = $this->tagService->create($tagData);

            $this->eventDispatcher->notifyEvent('create.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Etiqueta creada'))
                    ->addDetail(__u('Etiqueta'), $tagData->getName()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $tagId, __('Etiqueta creada')));
        } catch (\Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * editAction
     */
    public function editAction()
    {
        try {
            $this->setupApi(ActionsInterface::TAG_EDIT);

            $tagData = new TagData();
            $tagData->setId($this->apiService->getParamInt('id', true));
            $tagData->setName($this->apiService->getParamString('name', true));

            $this->tagService->update($tagData);

            $this->eventDispatcher->notifyEvent('edit.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Etiqueta actualizada'))
                    ->addDetail(__u('Etiqueta'), $tagData->getName())
                    ->addDetail(__u('ID'), $tagData->getId()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $tagData->getId(), __('Etiqueta actualizada')));
        } catch (\Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * deleteAction
     */
    public function deleteAction()
    {
        try {
            $this->setupApi(ActionsInterface::TAG_DELETE);

            $id = $this->apiService->getParamInt('id', true);

            $tagData = $this->tagService->getById($id);

            $this->tagService->delete($id);

            $this->eventDispatcher->notifyEvent('edit.tag',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Etiqueta eliminada'))
                    ->addDetail(__u('Etiqueta'), $tagData->getName()))
            );

            $this->returnResponse(ApiResponse::makeSuccess($tagData, $id, __('Etiqueta eliminada')));
        } catch (\Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * searchAction
     */
    public function searchAction()
    {
        try {
            $this->setupApi(ActionsInterface::TAG_SEARCH);

            $itemSearchData = new ItemSearchData();
            $itemSearchData->setSeachString($this->apiService->getParamString('text'));
            $itemSearchData->setLimitCount($this->apiService->getParamInt('count', false, self::SEARCH_COUNT_ITEMS));

            $this->eventDispatcher->notifyEvent('search.tag', new Event($this));

            $this->returnResponse(ApiResponse::makeSuccess($this->tagService->search($itemSearchData)->getDataAsArray()));
        } catch (\Exception $e) {
            processException($e);

            $this->returnResponseException($e);
        }
    }

    /**
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    protected function initialize()
    {
        $this->tagService = $this->dic->get(TagService::class);
        $this->apiService->setHelpClass(TagHelp::class);
    }
}