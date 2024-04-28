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

namespace SP\Domain\Auth\Providers;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Common\Providers\EventsTrait;
use SP\Domain\Common\Providers\Provider;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Infrastructure\File\FileSystem;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * Class AclHandler
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

    private readonly string $events;

    public function __construct(
        Application                         $application,
        private readonly UserProfileService $userProfileService,
        private readonly UserGroupService   $userGroupService
    ) {
        parent::__construct($application);

        $this->setup();
    }

    /**
     * @return void
     */
    private function setup(): void
    {
        $this->events = $this->parseEventsToRegex(self::EVENTS);
    }

    /**
     * Return the events handled by this receiver in string format
     *
     * @return string
     */
    public function getEventsString(): string
    {
        return $this->events;
    }

    /**
     * Update from sources
     *
     * @param string $eventType event's type
     * @param Event $event event's source object
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

            $extra = $eventMessage->getExtra('userProfileId');

            if ($extra) {
                foreach ($this->userProfileService->getUsersForProfile($extra[0]) as $user) {
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
            if (FileSystem::rmdirRecursive(AccountAcl::ACL_PATH . $userId) === false) {
                logger(sprintf('Unable to delete %s directory', AccountAcl::ACL_PATH . $userId));

                return false;
            }

            return true;
        } catch (FileNotFoundException $e) {
            processException($e);
        }

        return false;
    }

    /**
     * @param Event $event
     */
    private function processUser(Event $event): void
    {
        try {
            $eventMessage = $event->getEventMessage();

            if (null === $eventMessage) {
                throw new SPException(__u('Unable to process event for user'));
            }

            $extra = $eventMessage->getExtra('userId');

            if ($extra) {
                foreach ($extra as $id) {
                    $this->clearAcl($id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }

    private function processUserGroup(Event $event): void
    {
        try {
            $eventMessage = $event->getEventMessage();

            if (null === $eventMessage) {
                throw new SPException(__u('Unable to process event for user group'));
            }

            $extra = $eventMessage->getExtra('userGroupId');

            if ($extra) {
                foreach ($this->userGroupService->getUsageByUsers($extra[0]) as $user) {
                    $this->clearAcl($user->id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }
}
