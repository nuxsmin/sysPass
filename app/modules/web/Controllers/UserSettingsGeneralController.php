<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Events\Event;
use SP\Core\Exceptions\SessionTimeout;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\User\UserService;

/**
 * Class UserSettingsGeneralController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UserSettingsGeneralController extends SimpleControllerBase
{
    use JsonTrait;

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * saveAction
     */
    public function saveAction()
    {
        try {
            $this->checkSecurityToken($this->previousSk, $this->request);

            $userData = $this->session->getUserData();

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
            $userPreferencesData->setShowAccountSearchFilters($this->request->analyzeBool('show_account_search_filters', false));

            $this->userService->updatePreferencesById($userData->getId(), $userPreferencesData);

            // Guardar las preferencias en la sesión
            $userData->setPreferences($userPreferencesData);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Preferences updated'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * initialize
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        $this->checks();

        $this->userService = $this->dic->get(UserService::class);
    }
}