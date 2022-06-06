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

namespace SP\Modules\Web\Controllers\AccountFavorite;

use Exception;
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Domain\Account\AccountToFavoriteServiceInterface;
use SP\Http\JsonResponse;
use SP\Http\RequestInterface;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class MarkController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UnmarkController extends SimpleControllerBase
{
    use JsonTrait;

    private AccountToFavoriteServiceInterface $accountToFavoriteService;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        AccountToFavoriteServiceInterface $accountToFavoriteService
    ) {
        parent::__construct($application, $theme, $router, $acl, $request, $extensionChecker);

        $this->accountToFavoriteService = $accountToFavoriteService;
    }


    /**
     * @param  int  $accountId
     *
     * @return bool
     * @throws \JsonException
     */
    public function unmarkAction(int $accountId): bool
    {
        try {
            $this->accountToFavoriteService->delete($accountId, $this->session->getUserData()->getId());

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Favorite deleted'));
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}