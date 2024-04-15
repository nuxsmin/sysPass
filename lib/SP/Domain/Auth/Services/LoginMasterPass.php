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

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Dtos\UserLoginDto;
use SP\Domain\Auth\Ports\LoginMasterPassService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Crypt\Ports\TemporaryMasterPassService;
use SP\Domain\Http\RequestInterface;
use SP\Domain\Security\Ports\TrackService;
use SP\Domain\User\Dtos\UserDataDto;
use SP\Domain\User\Ports\UserMasterPassService;
use SP\Domain\User\Services\UserMasterPassStatus;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__u;

/**
 * Class LoginMasterPass
 */
final class LoginMasterPass extends LoginBase implements LoginMasterPassService
{
    public function __construct(
        Application                                 $application,
        TrackService                                $trackService,
        RequestInterface                            $request,
        private readonly UserMasterPassService      $userMasterPassService,
        private readonly TemporaryMasterPassService $temporaryMasterPassService,
    ) {
        parent::__construct($application, $trackService, $request);
    }

    /**
     * @inheritDoc
     */
    public function loadMasterPass(UserLoginDto $userLoginDto, UserDataDto $userDataDto): void
    {
        $masterPass = $this->request->analyzeEncrypted('mpass');
        $oldPass = $this->request->analyzeEncrypted('oldpass');

        if ($masterPass) {
            $this->checkMasterPass($masterPass, $userLoginDto);
        } elseif ($oldPass) {
            $this->loadMasterPassUsingOld($oldPass, $userLoginDto);
        } else {
            $this->loadCurrentMasterPass($userLoginDto, $userDataDto);
        }
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    private function checkMasterPass(string $masterPass, UserLoginDto $userLoginDto): void
    {
        try {
            if ($this->temporaryMasterPassService->checkTempMasterPass($masterPass)) {
                $this->eventDispatcher->notify(
                    'login.masterPass.temporary',
                    new Event($this, EventMessage::factory()->addDescription(__u('Using temporary password')))
                );

                $masterPass = $this->temporaryMasterPassService->getUsingKey($masterPass);
            }

            if ($this->userMasterPassService->updateOnLogin($masterPass, $userLoginDto)
                                            ->getUserMasterPassStatus() !== UserMasterPassStatus::Ok
            ) {
                $this->eventDispatcher->notify(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()->addDescription(__u('Wrong master password')))
                );

                $this->addTracking();

                throw AuthException::info(__u('Wrong master password'), null, LoginStatus::INVALID_MASTER_PASS->value);
            }

            $this->eventDispatcher->notify(
                'login.masterPass',
                new Event($this, EventMessage::factory()->addDescription(__u('Master password updated')))
            );
        } catch (NoSuchItemException|CryptException $e) {
            throw ServiceException::error('Internal error', __FUNCTION__, Service::STATUS_INTERNAL_ERROR, $e);
        }
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    private function loadMasterPassUsingOld(string $oldPass, UserLoginDto $userLoginDto): void
    {
        if ($this->userMasterPassService->updateFromOldPass($oldPass, $userLoginDto)
                                        ->getUserMasterPassStatus() !== UserMasterPassStatus::Ok
        ) {
            $this->eventDispatcher->notify(
                'login.masterPass',
                new Event($this, EventMessage::factory()->addDescription(__u('Wrong master password')))
            );

            $this->addTracking();

            throw AuthException::info(__u('Wrong master password'), null, LoginStatus::INVALID_MASTER_PASS->value);
        }

        $this->eventDispatcher->notify(
            'login.masterPass',
            new Event($this, EventMessage::factory()->addDescription(__u('Master password updated')))
        );
    }

    /**
     * @throws AuthException
     * @throws ServiceException
     */
    private function loadCurrentMasterPass(UserLoginDto $userLoginDto, UserDataDto $userDataDto): void
    {
        switch ($this->userMasterPassService->load($userLoginDto, $userDataDto)->getUserMasterPassStatus()) {
            case UserMasterPassStatus::CheckOld:
                throw AuthException::info(
                    __u('Your previous password is needed'),
                    null,
                    LoginStatus::OLD_PASS_REQUIRED->value
                );
            case UserMasterPassStatus::NotSet:
            case UserMasterPassStatus::Changed:
            case UserMasterPassStatus::Invalid:
                $this->addTracking();

                throw AuthException::info(
                    __u('The Master Password either is not saved or is wrong'),
                    null,
                    LoginStatus::INVALID_MASTER_PASS->value
                );
            case UserMasterPassStatus::Ok:
                $this->eventDispatcher->notify(
                    'login.masterPass',
                    new Event($this, EventMessage::factory()->addDescription(__u('Master password loaded')))
                );
                break;
        }
    }
}
