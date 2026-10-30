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

namespace SP\Modules\Web\Controllers\UserPassReset;

use Exception;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\UseCases\Dtos\RequestPasswordResetCommand;
use SP\Domain\User\UseCases\RequestPasswordResetUseCase;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\__u;
use function SP\processException;

/**
 * Class SaveRequestController
 */
final class SaveRequestController extends UserPassResetSaveBase
{
    public function __construct(
        WebControllerHelper $webControllerHelper,
        TrackService $trackService,
        private readonly RequestPasswordResetUseCase $requestPasswordResetUseCase
    ) {
        parent::__construct($webControllerHelper, $trackService);
    }

    #[Action(ResponseType::JSON)]
    public function saveRequestAction(): ActionResponse
    {
        try {
            $this->checkTracking();

            $login = $this->request->analyzeString('login');
            $email = $this->request->analyzeEmail('email');

            $this->requestPasswordResetUseCase->execute(
                new RequestPasswordResetCommand($login, $email, $this->uriContext->getWebUri())
            );

            return ActionResponse::ok(
                __u('Request sent'),
                [__('You will receive an email to complete the request shortly.')]
            );
        } catch (Exception $e) {
            processException($e);

            $this->addTracking();

            $this->eventDispatcher->notify('exception', new Event($e));

            return ActionResponse::error(
                $e->getMessage(),
                $e instanceof SPException ? $e->getHint() : null
            );
        }
    }
}
