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

namespace SP\Tests\Providers\Acl;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserGroupServiceInterface;
use SP\Domain\User\Ports\UserProfileServiceInterface;
use SP\Providers\Acl\AclHandler;
use SP\Tests\UnitaryTestCase;

/**
 * Class AclHandlerTest
 *
 * @group unitary
 */
class AclHandlerTest extends UnitaryTestCase
{
    private MockObject|UserProfileServiceInterface $userProfileService;
    private UserGroupServiceInterface|MockObject   $userGroupService;
    private AclHandler                             $aclHandler;

    public static function userEventProvider(): array
    {
        return [
            ['edit.user'],
            ['delete.user'],
            ['delete.user.selection']
        ];
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserProfileEvent()
    {
        $event = $this->createMock(Event::class);

        $event->expects(self::once())
              ->method('getEventMessage');

        $this->aclHandler->update('edit.userProfile', $event);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserProfileEventWithExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userProfileId')
                     ->willReturn([1]);

        $this->userProfileService->expects(self::once())
                                 ->method('getUsersForProfile')
                                 ->with(1)
                                 ->willReturn([self::$faker->randomNumber()]);

        $this->aclHandler->update('edit.userProfile', $event);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserProfileEventWithoutExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userProfileId')
                     ->willReturn(null);

        $this->userProfileService->expects(self::never())
                                 ->method('getUsersForProfile');

        $this->aclHandler->update('edit.userProfile', $event);
    }

    /**
     * @dataProvider userEventProvider
     *
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserEvent(string $userEvent)
    {
        $event = $this->createMock(Event::class);

        $event->expects(self::once())
              ->method('getEventMessage');

        $this->aclHandler->update($userEvent, $event);
    }

    /**
     * @dataProvider userEventProvider
     *
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserEventWithExtra(string $userEvent)
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userId')
                     ->willReturn([1]);

        $this->aclHandler->update($userEvent, $event);
    }

    /**
     * @dataProvider userEventProvider
     *
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserEventWithoutExtra(string $userEvent)
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userId')
                     ->willReturn(null);

        $this->aclHandler->update($userEvent, $event);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserGroupEvent()
    {
        $event = $this->createMock(Event::class);

        $event->expects(self::once())
              ->method('getEventMessage');

        $this->aclHandler->update('edit.userGroup', $event);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserGroupEventWithExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userGroupId')
                     ->willReturn([1]);

        $this->userGroupService->expects(self::once())
                                 ->method('getUsageByUsers')
                                 ->with(1)
                                 ->willReturn([self::$faker->randomNumber()]);

        $this->aclHandler->update('edit.userGroup', $event);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testUpdateWithUserGroupEventWithoutExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $event = new Event($this, $eventMessage);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userGroupId')
                     ->willReturn(null);

        $this->userGroupService->expects(self::never())
                               ->method('getUsageByUsers');

        $this->aclHandler->update('edit.userGroup', $event);
    }

    public function testInitialize()
    {
        $events = implode('|', array_map('preg_quote', AclHandler::EVENTS));
        $this->aclHandler->initialize();

        self::assertTrue($this->aclHandler->isInitialized());
        self::assertEquals($events, $this->aclHandler->getEventsString());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProfileService = $this->createMock(UserProfileServiceInterface::class);
        $this->userGroupService = $this->createMock(UserGroupServiceInterface::class);

        $this->aclHandler = new AclHandler($this->application, $this->userProfileService, $this->userGroupService);
    }

}
