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

namespace SP\Providers\Acl;

use DI\Container;
use Exception;
use Psr\Container\ContainerInterface;
use SP\Core\Events\Event;
use SP\Core\Events\EventReceiver;
use SP\Providers\EventsTrait;
use SP\Providers\Provider;
use SP\Services\Account\AccountAclService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SplSubject;

/**
 * Class AclHandler
 *
 * @package SP\Providers\Acl
 */
final class AclHandler extends Provider implements EventReceiver
{
    use EventsTrait;

    const EVENTS = [
        'edit.userProfile',
        'edit.user',
        'edit.userGroup',
        'delete.user',
        'delete.user.selection'
    ];

    /**
     * @var string
     */
    private $events;
    /**
     * @var ContainerInterface
     */
    private $dic;

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents()
    {
        return self::EVENTS;
    }

    /**
     * Devuelve los eventos que implementa el observador en formato cadena
     *
     * @return string
     */
    public function getEventsString()
    {
        return $this->events;
    }

    /**
     * Receive update from subject
     *
     * @link  https://php.net/manual/en/splobserver.update.php
     *
     * @param SplSubject $subject <p>
     *                            The <b>SplSubject</b> notifying the observer of an update.
     *                            </p>
     *
     * @return void
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        $this->updateEvent('update', new Event($subject));
    }

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event  $event     Objeto del evento
     */
    public function updateEvent($eventType, Event $event)
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

    /**
     * @param Event $event
     */
    private function processUserProfile(Event $event)
    {
        try {
            $eventMessage = $event->getEventMessage();
            $extra = $eventMessage->getExtra();

            if (isset($extra['userProfileId'])) {
                $userProfileService = $this->dic->get(UserProfileService::class);

                foreach ($userProfileService->getUsersForProfile($extra['userProfileId'][0]) as $user) {
                    AccountAclService::clearAcl($user->id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param Event $event
     */
    private function processUser(Event $event)
    {
        $eventMessage = $event->getEventMessage();
        $extra = $eventMessage->getExtra();

        if (isset($extra['userId'])) {
            foreach ($extra['userId'] as $id) {
                AccountAclService::clearAcl($id);
            }
        }
    }

    /**
     * @param Event $event
     */
    private function processUserGroup(Event $event)
    {
        try {
            $eventMessage = $event->getEventMessage();
            $extra = $eventMessage->getExtra();

            if (isset($extra['userGroupId'])) {
                $userGroupService = $this->dic->get(UserGroupService::class);

                foreach ($userGroupService->getUsageByUsers($extra['userGroupId'][0]) as $user) {
                    AccountAclService::clearAcl($user->id);
                }
            }
        } catch (Exception $e) {
            processException($e);
        }
    }

    /**
     * @param Container $dic
     */
    protected function initialize(Container $dic)
    {
        $this->dic = $dic;
        $this->events = $this->parseEventsToRegex(self::EVENTS);
    }
}