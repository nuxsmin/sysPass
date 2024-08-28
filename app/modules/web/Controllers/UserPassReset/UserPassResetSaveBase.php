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
use JsonException;
use SP\Core\Application;
use SP\Domain\Core\Exceptions\InvalidArgumentException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\Security\Dtos\TrackRequest;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Ports\UserPassRecoverService;
use SP\Domain\User\Ports\UserService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UserPassResetSaveBase
 */
abstract class UserPassResetSaveBase extends ControllerBase
{
    protected UserPassRecoverService $userPassRecoverService;
    protected UserService $userService;
    protected MailService $mailService;
    private TrackService  $trackService;
    private TrackRequest  $trackRequest;

    /**
     * @throws SessionTimeout
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    public function __construct(
        Application            $application,
        WebControllerHelper    $webControllerHelper,
        UserPassRecoverService $userPassRecoverService,
        UserService $userService,
        MailService            $mailService,
        TrackService           $trackService

    ) {
        parent::__construct($application, $webControllerHelper);
        $this->userPassRecoverService = $userPassRecoverService;
        $this->userService = $userService;
        $this->mailService = $mailService;
        $this->trackService = $trackService;

        $this->trackRequest = $this->trackService->buildTrackRequest($this->routeContextData->actionName);
    }

    /**
     * @throws SPException
     * @throws Exception
     */
    final protected function checkTracking(): void
    {
        if ($this->trackService->checkTracking($this->trackRequest)) {
            throw new SPException(__u('Attempts exceeded'), SPException::INFO);
        }
    }

    /**
     * Añadir un seguimiento
     */
    final protected function addTracking(): void
    {
        try {
            $this->trackService->add($this->trackRequest);
        } catch (Exception $e) {
            processException($e);
        }
    }
}
