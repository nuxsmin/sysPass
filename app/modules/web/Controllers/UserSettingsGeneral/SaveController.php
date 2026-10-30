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

namespace SP\Modules\Web\Controllers\UserSettingsGeneral;

use Exception;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class SaveController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends SimpleControllerBase
{
    private UserService $userService;

    public function __construct(
        SimpleControllerHelper $simpleControllerHelper,
        UserService $userService
    ) {
        parent::__construct($simpleControllerHelper);

        $this->checks();

        $this->userService = $userService;
    }

    #[Action(ResponseType::JSON)]
    public function saveAction(): ActionResponse
    {
        try {
            $userData = $this->session->getUserData();

            $userPreferencesData = $this->getUserPreferencesData($userData);

            $this->userService->updatePreferencesById($userData->id, $userPreferencesData);

            // Save preferences in current session
            $this->session->setUserData($userData->mutate(['preferences' => $userPreferencesData]));

            return ActionResponse::ok(__u('Preferences updated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error(
                $e->getMessage(),
                $e instanceof SPException ? $e->getHint() : null
            );
        }
    }

    /**
     * @param UserDto $userData
     *
     * @return UserPreferences
     */
    private function getUserPreferencesData(UserDto $userData): UserPreferences
    {
        return ($userData->preferences ?? new UserPreferences())->mutate(
            [
                'user_id' => $userData->id,
                'lang' => $this->request->analyzeString('userlang'),
                'theme' => $this->request->analyzeString('usertheme', 'material-blue'),
                'resultsPerPage' => $this->request->analyzeInt('resultsperpage', 12),
                'accountLink' => $this->request->analyzeBool('account_link', false),
                'sortViews' => $this->request->analyzeBool('sort_views', false),
                'topNavbar' => $this->request->analyzeBool('top_navbar', false),
                'optionalActions' => $this->request->analyzeBool('optional_actions', false),
                'resultsAsCards' => $this->request->analyzeBool('resultsascards', false),
                'checkNotifications' => $this->request->analyzeBool('check_notifications', false),
                'showAccountSearchFilters' => $this->request->analyzeBool('show_account_search_filters', false),
            ]
        );
    }
}
