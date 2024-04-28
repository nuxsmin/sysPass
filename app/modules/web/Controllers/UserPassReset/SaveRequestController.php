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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\User\Services\UserPassRecover;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class SaveRequestController
 */
final class SaveRequestController extends UserPassResetSaveBase
{
    use JsonTrait;

    /**
     * @return bool
     * @throws JsonException
     */
    public function saveRequestAction(): bool
    {
        try {
            $this->checkTracking();

            $login = $this->request->analyzeString('login');
            $email = $this->request->analyzeEmail('email');

            $userData = $this->userService->getByLogin($login);

            if ($userData->getEmail() !== $email) {
                throw new SPException(__u('Wrong data'), SPException::WARNING);
            }

            if ($userData->isDisabled() || $userData->isLdap()) {
                throw new SPException(
                    __u('Unable to reset the password'),
                    SPException::WARNING,
                    __u('Please contact to the administrator')
                );
            }

            $hash = $this->userPassRecoverService->requestForUserId($userData->getId());

            $this->eventDispatcher->notify(
                'request.user.passReset',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Password Recovery'))
                        ->addDetail(__u('Requested for'), sprintf('%s (%s)', $login, $email))
                )
            );

            $this->mailService->send(
                __('Password Change'),
                $email,
                UserPassRecover::getMailMessage($hash)
            );

            return $this->returnJsonResponse(
                JsonMessage::JSON_SUCCESS,
                __u('Request sent'),
                [__u('You will receive an email to complete the request shortly.')]
            );
        } catch (Exception $e) {
            processException($e);

            $this->addTracking();

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
