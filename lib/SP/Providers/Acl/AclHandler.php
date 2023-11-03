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

namespace SP\Providers\Acl;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\Core\Exceptions\FileNotFoundException;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\Services\AccountAclService;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Domain\User\Services\UserGroupService;
use SP\Domain\User\Services\UserProfileService;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;
use SP\Util\FileUtil;
use SplSubject;
use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class AclHandler
 *
 * @package SP\Providers\Acl
 */
final class AclHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    public const EVENTS = [
        'edit.userProfile',
        'edit.user',
        'edit.userGroup',
        'delete.user',
        'delete.user.selection',
    ];

    private string             $events;
    private UserProfileService $userProfileService;
    private UserGroupService   $userGroupService;

    public function __construct(
        Application $application,
        UserProfileServiceInterface $userProfileService,
        UserGroupServiceInterface $userGroupService
    ) {
        $this->userProfileService = $userProfileService;
        $this->userGroupService = $userGroupService;

        parent::__construct($application);
    }

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents(): array
    {
        return self::EVENTS;
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString(): string
    {
        return $this->events;
    }

    /**
     * Evento de actualización
     *
     * @param  string  $eventType  Nombre del evento
     * @param  Event  $event  Objeto del evento
     *
     * @throws SPException
     */
    public function update(string $eventType, Event $event): void
    {
        switch ($eventType) {
            case 'edit.userProfile':
                $this->processUserProfile($event);
                break;
            case 'edit.user':
            case 'delete.user':
            case 'delete.user.selection':
                $this->processUser($event);
                break;
            case 'edit.userGroup':
                $this->processUserGroup($event);
                break;
        }
    }

    private function processUserProfile(Event $event): void
    {
        try {
            $eventMessage = $event->getEventMessage();

            if (null === $eventMessage) {
                throw new SPException(__u('Unable to process event for user profile'));
            }

            $extra = $eventMessage->getExtra();

            if (isset($extra['userProfileId'])) {
                foreach ($this->userProfileService->getUsersForProfile($extra['userProfileId'][0]) as $user) {
                    $this->clearAcl($user->id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param $userId
     *
     * @return bool
     */
    private function clearAcl($userId): bool
    {
        logger(sprintf('Clearing ACL for user ID: %d', $userId));

        try {
            if (FileUtil::rmdirRecursive(AccountAclService::ACL_PATH.$userId) === false) {
                logger(sprintf('Unable to delete %s directory', AccountAclService::ACL_PATH.$userId));

                return false;
            }

            return true;
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return false;
    }

    /**
     * @throws SPException
     */
    private function processUser(Event $event): void
    {
        $eventMessage = $event->getEventMessage();

        if (null === $eventMessage) {
            throw new SPException(__u('Unable to process event for user'));
        }

        $extra = $eventMessage->getExtra();

        if (isset($extra['userId'])) {
            foreach ($extra['userId'] as $id) {
                $this->clearAcl($id);
            }
        }
    }

    private function processUserGroup(Event $event): void
    {
        try {
            $eventMessage = $event->getEventMessage();

            if (null === $eventMessage) {
                throw new SPException(__u('Unable to process event for user group'));
            }

            $extra = $eventMessage->getExtra();

            if (isset($extra['userGroupId'])) {
                foreach ($this->userGroupService->getUsageByUsers($extra['userGroupId'][0]) as $user) {
                    $this->clearAcl($user->id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }

    public function initialize(): void
    {
        $this->events = $this->parseEventsToRegex(self::EVENTS);
        $this->initialized = true;
    }
}
