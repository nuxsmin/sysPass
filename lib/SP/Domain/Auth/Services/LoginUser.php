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

namespace SP\Domain\Auth\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Dtos\LoginResponseDto;
use SP\Domain\Auth\Ports\LoginUserService;
use SP\Domain\Common\Providers\Password;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserPassRecoverService;

use function SP\__u;

/**
 * Class LoginUser
 */
final class LoginUser extends LoginBase implements LoginUserService
{
    public function __construct(
        Application                             $application,
        TrackService                            $trackService,
        RequestService $request,
        private readonly UserPassRecoverService $userPassRecoverService
    ) {
        parent::__construct($application, $trackService, $request);
    }

    /**
     * Check the user status
     *
     * @param UserDataDto $userDataDto
     * @return LoginResponseDto
     * @throws AuthException
     * @throws ServiceException
     */
    public function checkUser(UserDataDto $userDataDto): LoginResponseDto
    {
        try {
            if ($userDataDto->getIsDisabled()) {
                $this->eventDispatcher->notify(
                    'login.checkUser.disabled',
                    new Event(
                        $this,
                        EventMessage::factory()
                                    ->addDescription(__u('User disabled'))
                                    ->addDetail(__u('User'), $userDataDto->getLogin())
                    )
                );

                $this->addTracking();

                throw AuthException::info(__u('User disabled'), null, LoginStatus::USER_DISABLED->value);
            }

            if ($userDataDto->getIsChangePass()) {
                $this->eventDispatcher->notify(
                    'login.checkUser.changePass',
                    new Event($this, EventMessage::factory()->addDetail(__u('User'), $userDataDto->getLogin()))
                );

                $hash = Password::generateRandomBytes(16);

                $this->userPassRecoverService->add($userDataDto->getId(), $hash);

                return new LoginResponseDto(
                    LoginStatus::PASS_RESET_REQUIRED,
                    $this->getUriForRoute('userPassReset/reset/' . $hash)
                );
            }

            return new LoginResponseDto(LoginStatus::PASS);
        } catch (EnvironmentIsBrokenException|ConstraintException|QueryException $e) {
            throw ServiceException::error('Internal error', __FUNCTION__, Service::STATUS_INTERNAL_ERROR, $e);
        }
    }
}
