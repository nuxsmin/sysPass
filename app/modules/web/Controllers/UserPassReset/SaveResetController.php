<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class SaveResetController
 */
final class SaveResetController extends UserPassResetSaveBase
{
    use JsonTrait;

    /**
     * @return bool
     * @throws JsonException
     */
    public function saveResetAction(): bool
    {
        try {
            $this->checkTracking();

            $pass = $this->request->analyzeEncrypted('password');
            $passR = $this->request->analyzeEncrypted('password_repeat');

            if (!$pass || !$passR) {
                throw new ValidationException(__u('Password cannot be blank'));
            }

            if ($pass !== $passR) {
                throw new ValidationException(__u('Passwords do not match'));
            }

            $hash = $this->request->analyzeString('hash');

            $userId = $this->userPassRecoverService->getUserIdForHash($hash);

            $this->userPassRecoverService->toggleUsedByHash($hash);

            $this->userService->updatePass($userId, $pass);

            $user = $this->userService->getById($userId);

            $this->eventDispatcher->notify(
                'edit.user.password',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Password updated'))
                        ->addDetail(__u('User'), $user->getLogin())
                        ->addExtra('userId', $userId)
                        ->addExtra('email', $user->getEmail())
                )
            );

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Password updated'));
        } catch (Exception $e) {
            processException($e);

            $this->addTracking();

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }
}
