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
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\UserPreferences;
use SP\Domain\User\Ports\UserService;
use SP\Domain\User\Services\User;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class SaveController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends SimpleControllerBase
{
    use JsonTrait;

    private User $userService;

    public function __construct(
        Application $application,
        SimpleControllerHelper $simpleControllerHelper,
        UserService $userService
    ) {
        parent::__construct($application, $simpleControllerHelper);

        $this->checks();

        $this->userService = $userService;
    }

    /**
     * @return bool
     * @throws JsonException
     */
    public function saveAction(): bool
    {
        try {
            $userData = $this->session->getUserData();

            $userPreferencesData = $this->getUserPreferencesData($userData);

            $this->userService->updatePreferencesById($userData->getId(), $userPreferencesData);

            // Save preferences in current session
            $userData->setPreferences($userPreferencesData);

            return $this->returnJsonResponse(JsonMessage::JSON_SUCCESS, __u('Preferences updated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param UserDto $userData
     *
     * @return UserPreferences
     */
    private function getUserPreferencesData(UserDto $userData): UserPreferences
    {
        $userPreferencesData = clone $userData->getPreferences();

        $userPreferencesData->setUserId($userData->getId());
        $userPreferencesData->setLang($this->request->analyzeString('userlang'));
        $userPreferencesData->setTheme($this->request->analyzeString('usertheme', 'material-blue'));
        $userPreferencesData->setResultsPerPage($this->request->analyzeInt('resultsperpage', 12));
        $userPreferencesData->setAccountLink($this->request->analyzeBool('account_link', false));
        $userPreferencesData->setSortViews($this->request->analyzeBool('sort_views', false));
        $userPreferencesData->setTopNavbar($this->request->analyzeBool('top_navbar', false));
        $userPreferencesData->setOptionalActions($this->request->analyzeBool('optional_actions', false));
        $userPreferencesData->setResultsAsCards($this->request->analyzeBool('resultsascards', false));
        $userPreferencesData->setCheckNotifications($this->request->analyzeBool('check_notifications', false));
        $userPreferencesData->setShowAccountSearchFilters(
            $this->request->analyzeBool('show_account_search_filters', false)
        );

        return $userPreferencesData;
    }
}
