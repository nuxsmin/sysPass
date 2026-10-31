<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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


use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\User\Models\ProfileData;
use SP\Domain\User\Models\UserProfile;
use SP\Domain\User\Ports\UserProfileService;
use SP\Domain\User\UseCases\Dtos\GetUserProfileCommand;
use SP\Domain\User\UseCases\GetUserProfileUseCase;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\ItemTrait;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

/**
 * Class UserProfileViewBase
 */
abstract class UserProfileViewBase extends ControllerBase
{
    use ItemTrait;

    private UserProfileService $userProfileService;
    private GetUserProfileUseCase $getUserProfileUseCase;
    private CustomFieldDataService $customFieldService;

    public function __construct(
        WebControllerHelper $webControllerHelper,
        UserProfileService  $userProfileService,
        GetUserProfileUseCase $getUserProfileUseCase,
        CustomFieldDataService $customFieldService
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();

        $this->userProfileService = $userProfileService;
        $this->getUserProfileUseCase = $getUserProfileUseCase;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Sets view data for displaying user profile's data
     *
     * @param  int|null  $profileId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     * @throws ServiceException
     * @throws NoSuchItemException
     */
    protected function setViewData(?int $profileId = null): void
    {
        $this->view->addTemplate('user_profile', 'itemshow');

        $profile = $profileId
            ? $this->getUserProfileUseCase->execute(new GetUserProfileCommand($profileId))->model
            : new UserProfile();

        $this->view->assign('profile', $profile);
        $this->view->assign('profileData', $profile->hydrate(ProfileData::class) ?? new ProfileData());

        $this->view->assign('nextAction', $this->acl->getRouteFor(AclActionsInterface::ACCESS_MANAGE));

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

        $this->view->assign('showViewCustomPass', $this->acl->checkUserAccess(AclActionsInterface::CUSTOMFIELD_VIEW_PASS));
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(AclActionsInterface::PROFILE, $profileId, $this->customFieldService)
        );
    }
}
