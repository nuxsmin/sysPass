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
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\ItemsGridHelper;
use SP\Modules\Web\Controllers\Traits\ItemTrait;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\UserProfileForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Services\UserProfile\UserProfileService;

/**
 * Class UserProfileController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UserProfileController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    /**
     * @var UserProfileService
     */
    protected $userProfileService;

    /**
     * Search action
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction()
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_SEARCH)) {
            return;
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign('index', $this->request->analyzeInt('activetab', 0));
        $this->view->assign('data', $this->getSearchGrid());

        $this->returnJsonResponseData(['html' => $this->render()]);
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
        $itemsGridHelper = $this->dic->get(ItemsGridHelper::class);
        $itemSearchData = $this->getSearchData($this->configData->getAccountCount(), $this->request);

        return $itemsGridHelper->updatePager($itemsGridHelper->getUserProfilesGrid($this->userProfileService->search($itemSearchData)), $itemSearchData);
    }

    /**
     * Create action
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function createAction()
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_CREATE)) {
            return;
        }

        $this->view->assign(__FUNCTION__, 1);
        $this->view->assign('header', __('Nuevo Perfil'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userProfile/saveCreate');

        try {
            $this->setViewData();

            $this->eventDispatcher->notifyEvent('show.userProfile.create', new Event($this));

            $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user profile's data
     *
     * @param $profileId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Services\ServiceException
     */
    protected function setViewData($profileId = null)
    {
        $this->view->addTemplate('userprofile', 'itemshow');

        $profile = $profileId ? $this->userProfileService->getById($profileId) : new UserProfileData();

        $this->view->assign('profile', $profile);
        $this->view->assign('profileData', $profile->getProfile() ?: new ProfileData());

        $this->view->assign('sk', $this->session->generateSecurityKey());
        $this->view->assign('nextAction', Acl::getActionRoute(Acl::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('usedBy', $this->userProfileService->getUsersForProfile($profileId));

            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled');
            $this->view->assign('readonly');
        }

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(Acl::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign('customFields', $this->getCustomFieldsForItem(Acl::PROFILE, $profileId));
    }

    /**
     * Edit action
     *
     * @param $id
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function editAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_EDIT)) {
            return;
        }

        $this->view->assign('header', __('Editar Perfil'));
        $this->view->assign('isView', false);
        $this->view->assign('route', 'userProfile/saveEdit/' . $id);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userProfile.edit', new Event($this));

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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteAction($id = null)
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_DELETE)) {
            return;
        }

        try {
            if ($id === null) {
                $this->userProfileService->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->deleteCustomFieldsForItem(Acl::PROFILE, $id);

                $this->eventDispatcher->notifyEvent('delete.userProfile.selection',
                    new Event($this, EventMessage::factory()->addDescription(__u('Perfiles eliminados')))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Perfiles eliminados'));
            } else {
                $this->userProfileService->delete($id);

                $this->deleteCustomFieldsForItem(Acl::PROFILE, $id);

                $this->eventDispatcher->notifyEvent('delete.userProfile',
                    new Event($this, EventMessage::factory()
                        ->addDescription(__u('Perfil eliminado'))
                        ->addDetail(__u('Perfil'), $id))
                );

                $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Perfil eliminado'));
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
        if (!$this->acl->checkUserAccess(Acl::PROFILE_CREATE)) {
            return;
        }

        try {
            $form = new UserProfileForm($this->dic);
            $form->validate(Acl::PROFILE_CREATE);

            $profileData = $form->getItemData();

            $id = $this->userProfileService->create($profileData);

            $this->addCustomFieldsForItem(Acl::PROFILE, $id, $this->request);

            $this->eventDispatcher->notifyEvent('create.userProfile', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Perfil creado'));
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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function saveEditAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_EDIT)) {
            return;
        }

        try {
            $form = new UserProfileForm($this->dic, $id);
            $form->validate(Acl::PROFILE_EDIT);

            $profileData = $form->getItemData();

            $this->userProfileService->update($profileData);
//            $this->userProfileService->logAction($id, Acl::PROFILE_EDIT);

            $this->updateCustomFieldsForItem(Acl::PROFILE, $id, $this->request);

            $this->eventDispatcher->notifyEvent('edit.userProfile', new Event($this));

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Perfil actualizado'));
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
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function viewAction($id)
    {
        if (!$this->acl->checkUserAccess(Acl::PROFILE_VIEW)) {
            return;
        }

        $this->view->assign('header', __('Ver Perfil'));
        $this->view->assign('isView', true);

        try {
            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent('show.userProfile', new Event($this));

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

        $this->userProfileService = $this->dic->get(UserProfileService::class);
    }
}