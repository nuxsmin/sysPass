<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\UserProfile;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\DataModel\ProfileData;
use SP\DataModel\UserProfileData;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UserProfileViewBase
 */
abstract class UserProfileViewBase extends ControllerBase
{
    use ItemTrait;

    private UserProfileServiceInterface $userProfileService;
    private CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        UserProfileServiceInterface $userProfileService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->userProfileService = $userProfileService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Sets view data for displaying user profile's data
     *
     * @param  int|null  $profileId
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     * @throws \SP\Infrastructure\Common\Repositories\NoSuchItemException
     */
    protected function setViewData(?int $profileId = null): void
    {
        $this->view->addTemplate('user_profile', 'itemshow');

        $profile = $profileId
            ? $this->userProfileService->getById($profileId)
            : new UserProfileData();

        $this->view->assign('profile', $profile);
        $this->view->assign('profileData', $profile->getProfile() ?: new ProfileData());

        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

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

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::PROFILE, $profileId, $this->customFieldService)
        );
    }
}
