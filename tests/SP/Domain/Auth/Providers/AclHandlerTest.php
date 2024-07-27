<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\Auth\Providers;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Auth\Providers\AclHandler;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Ports\UserGroupService;
use SP\Domain\User\Ports\UserProfileService;
use SP\Tests\UnitaryTestCase;

/**
 * Class AclHandlerTest
 *
 */
#[Group('unitary')]
class AclHandlerTest extends UnitaryTestCase
{
    private MockObject|UserProfileService $userProfileService;
    private UserGroupService|MockObject   $userGroupService;
    private AclHandler                    $aclHandler;

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
     */
    public function testUpdateWithUserProfileEvent()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $eventMessage->expects($this->once())
                     ->method('getExtra')
                     ->with('userProfileId');

        $this->aclHandler->update('edit.userProfile', new Event($this, $eventMessage));
    }

    /**
     * @throws Exception
     */
    public function testUpdateWithUserProfileEventWithExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userProfileId')
                     ->willReturn([1]);

        $this->userProfileService->expects(self::once())
                                 ->method('getUsersForProfile')
                                 ->with(1)
                                 ->willReturn([self::$faker->randomNumber()]);

        $this->aclHandler->update('edit.userProfile', new Event($this, $eventMessage));
    }

    /**
     * @throws Exception
     */
    public function testUpdateWithUserProfileEventWithoutExtra()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $eventMessage->expects(self::once())
                     ->method('getExtra')
                     ->with('userProfileId')
                     ->willReturn(null);

        $this->userProfileService->expects(self::never())
                                 ->method('getUsersForProfile');

        $this->aclHandler->update('edit.userProfile', new Event($this, $eventMessage));
    }

    /**
     * @throws Exception
     */
    #[DataProvider('userEventProvider')]
    public function testUpdateWithUserEvent(string $userEvent)
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $eventMessage->expects($this->once())
                     ->method('getExtra')
                     ->with('userId');

        $this->aclHandler->update($userEvent, new Event($this, $eventMessage));
    }

    /**
     * @throws Exception
     */
    #[DataProvider('userEventProvider')]
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
     * @throws Exception
     * @throws SPException
     */
    #[DataProvider('userEventProvider')]
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
     */
    public function testUpdateWithUserGroupEvent()
    {
        $eventMessage = $this->createMock(EventMessage::class);

        $eventMessage->expects($this->once())
                     ->method('getExtra')
                     ->with('userGroupId');

        $this->aclHandler->update('edit.userGroup', new Event($this, $eventMessage));
    }

    /**
     * @throws Exception
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProfileService = $this->createMock(UserProfileService::class);
        $this->userGroupService = $this->createMock(UserGroupService::class);

        $this->aclHandler = new AclHandler(
            $this->application,
            $this->userProfileService,
            $this->userGroupService,
            $this->pathsContext
        );
    }

}
