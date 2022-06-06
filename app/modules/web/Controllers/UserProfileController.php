<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Core\Exceptions\ValidationException;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\User\Services\UserProfileService;
use SP\Html\DataGrid\DataGridInterface;
use SP\Http\JsonResponse;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\Grid\UserProfileGrid;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Modules\Web\Forms\UserProfileForm;
use SP\Mvc\Controller\CrudControllerInterface;
use SP\Mvc\Controller\ItemTrait;

/**
 * Class UserProfileController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UserProfileController extends ControllerBase implements CrudControllerInterface
{
    use JsonTrait, ItemTrait;

    protected ?UserProfileService $userProfileService = null;

    /**
     * Search action
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function searchAction(): bool
    {
        if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_SEARCH)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('You don\'t have permission to do this operation')
            );
        }

        $this->view->addTemplate('datagrid-table', 'grid');
        $this->view->assign(
            'index',
            $this->request->analyzeInt('activetab', 0)
        );
        $this->view->assign('data', $this->getSearchGrid());

        return $this->returnJsonResponseData(['html' => $this->render()]);
    }

    /**
     * getSearchGrid
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ConstraintException
     * @throws QueryException
     */
    protected function getSearchGrid(): DataGridInterface
    {
        $itemSearchData = $this->getSearchData(
            $this->configData->getAccountCount(),
            $this->request
        );

        $userProfileGrid = $this->dic->get(UserProfileGrid::class);

        return $userProfileGrid->updatePager(
            $userProfileGrid->getGrid($this->userProfileService->search($itemSearchData)),
            $itemSearchData
        );
    }

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function createAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('New Profile'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'userProfile/saveCreate');

            $this->setViewData();

            $this->eventDispatcher->notifyEvent(
                'show.userProfile.create',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Sets view data for displaying user profile's data
     *
     * @param int|null $profileId
     *
     * @throws ConstraintException
     * @throws DependencyException
     * @throws NoSuchItemException
     * @throws NotFoundException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     */
    protected function setViewData(?int $profileId = null): void
    {
        $this->view->addTemplate('user_profile', 'itemshow');

        $profile = $profileId
            ? $this->userProfileService->getById($profileId)
            : new UserProfileData();

        $this->view->assign('profile', $profile);
        $this->view->assign(
            'profileData',
            $profile->getProfile() ?: new ProfileData()
        );

        $this->view->assign(
            'nextAction',
            Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE)
        );

        if ($this->view->isView === true) {
            $this->view->assign(
                'usedBy',
                $profileId
                    ? $this->userProfileService->getUsersForProfile($profileId)
                    : []
            );

            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign(
            'showViewCustomPass',
            $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS)
        );
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::PROFILE, $profileId)
        );
    }

    /**
     * Edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function editAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('Edit Profile'));
            $this->view->assign('isView', false);
            $this->view->assign('route', 'userProfile/saveEdit/' . $id);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.userProfile.edit',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Delete action
     *
     * @param int|null $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function deleteAction(?int $id = null): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_DELETE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            if ($id === null) {
                $this->userProfileService
                    ->deleteByIdBatch($this->getItemsIdFromRequest($this->request));

                $this->eventDispatcher->notifyEvent(
                    'delete.userProfile.selection',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('Profiles deleted'))
                    )
                );

                $this->deleteCustomFieldsForItem(
                    ActionsInterface::PROFILE,
                    $id
                );

                return $this->returnJsonResponse(
                    JsonResponse::JSON_SUCCESS,
                    __u('Profiles deleted')
                );
            }

            $this->userProfileService->delete($id);


            $this->eventDispatcher->notifyEvent(
                'delete.userProfile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Profile deleted'))
                        ->addDetail(__u('Profile'), $id)
                        ->addExtra('userProfileId', $id)
                )
            );

            $this->deleteCustomFieldsForItem(
                ActionsInterface::PROFILE,
                $id
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Profile deleted')
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function saveCreateAction(): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_CREATE)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new UserProfileForm($this->dic);
            $form->validate(ActionsInterface::PROFILE_CREATE);

            $profileData = $form->getItemData();

            $id = $this->userProfileService->create($profileData);

            $this->eventDispatcher->notifyEvent(
                'create.userProfile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Profile added'))
                        ->addDetail(__u('Name'), $profileData->getName())
                )
            );

            $this->addCustomFieldsForItem(
                ActionsInterface::PROFILE,
                $id,
                $this->request
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Profile added')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Saves edit action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function saveEditAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_EDIT)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $form = new UserProfileForm($this->dic, $id);
            $form->validate(ActionsInterface::PROFILE_EDIT);

            $profileData = $form->getItemData();

            $this->userProfileService->update($profileData);

            $this->eventDispatcher->notifyEvent(
                'edit.userProfile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Profile updated'))
                        ->addDetail(__u('Name'), $profileData->getName())
                        ->addExtra('userProfileId', $id)
                )
            );

            $this->updateCustomFieldsForItem(
                ActionsInterface::PROFILE,
                $id,
                $this->request
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Profile updated')
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponseException($e);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * View action
     *
     * @param int $id
     *
     * @return bool
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (!$this->acl->checkUserAccess(ActionsInterface::PROFILE_VIEW)) {
                return $this->returnJsonResponse(
                    JsonResponse::JSON_ERROR,
                    __u('You don\'t have permission to do this operation')
                );
            }

            $this->view->assign('header', __('View Profile'));
            $this->view->assign('isView', true);

            $this->setViewData($id);

            $this->eventDispatcher->notifyEvent(
                'show.userProfile',
                new Event($this)
            );

            return $this->returnJsonResponseData(['html' => $this->render()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * Initialize class
     *
     * @throws AuthException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checkLoggedIn();

        $this->userProfileService = $this->dic->get(UserProfileService::class);
    }
}