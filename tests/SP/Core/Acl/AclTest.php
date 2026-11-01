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

namespace SP\Tests\Core\Acl;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Exception;
use SP\Core\Acl\Acl;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\ActionsInterface;
use SP\Domain\User\Dtos\UserDto;
use SP\Domain\User\Models\ProfileData;
use SP\Tests\UnitaryTestCase;

/**
 * Class AclTest
 */
#[Group('unitary')]
class AclTest extends UnitaryTestCase
{
    private Acl $acl;

    #[Test]
    public function nonAdminWithAccViewCanSearchAccounts(): void
    {
        $this->setUser(new ProfileData(['accView' => true]));

        self::assertTrue($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_SEARCH));
    }

    #[Test]
    public function nonAdminWithoutAccViewCannotSearchAccounts(): void
    {
        $this->setUser(new ProfileData());

        self::assertFalse($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_SEARCH));
    }

    #[Test]
    public function nonAdminWithConfigBackupCanRunBackupAndExport(): void
    {
        $this->setUser(new ProfileData(['configBackup' => true]));

        self::assertTrue($this->acl->checkUserAccess(AclActionsInterface::CONFIG_BACKUP_RUN));
        self::assertTrue($this->acl->checkUserAccess(AclActionsInterface::CONFIG_EXPORT_RUN));
    }

    #[Test]
    public function nonAdminWithoutConfigBackupCannotRunBackupOrExport(): void
    {
        $this->setUser(new ProfileData());

        self::assertFalse($this->acl->checkUserAccess(AclActionsInterface::CONFIG_BACKUP_RUN));
        self::assertFalse($this->acl->checkUserAccess(AclActionsInterface::CONFIG_EXPORT_RUN));
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->acl = new Acl(
            $this->context,
            $this->eventDispatcherStub,
            $this->createMock(ActionsInterface::class)
        );
    }

    private function setUser(ProfileData $profile): void
    {
        $this->context->setUserData(
            new UserDto(id: 1, isAdminApp: false, isAdminAcc: false)
        );
        $this->context->setUserProfile($profile);
    }
}
