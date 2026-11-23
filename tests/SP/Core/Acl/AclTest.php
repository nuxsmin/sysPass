<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use PHPUnit\Framework\Attributes\DataProvider;
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
    public function accountFileUploadRequiresTheAccountFilesProfilePermission(): void
    {
        $this->setUser(new ProfileData());
        self::assertFalse($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_FILE_UPLOAD));
        $this->setUser(new ProfileData(['accFiles' => true]));
        self::assertTrue($this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_FILE_UPLOAD));
    }

    public static function accountFileActions(): array
    {
        return [
            'file' => [AclActionsInterface::ACCOUNT_FILE],
            'view' => [AclActionsInterface::ACCOUNT_FILE_VIEW],
            'upload' => [AclActionsInterface::ACCOUNT_FILE_UPLOAD],
            'download' => [AclActionsInterface::ACCOUNT_FILE_DOWNLOAD],
            'delete' => [AclActionsInterface::ACCOUNT_FILE_DELETE],
            'search' => [AclActionsInterface::ACCOUNT_FILE_SEARCH],
            'list' => [AclActionsInterface::ACCOUNT_FILE_LIST],
        ];
    }

    #[Test]
    #[DataProvider('accountFileActions')]
    public function accountFileActionsRequireTheAccountFilesProfilePermission(int $action): void
    {
        $this->setUser(new ProfileData());
        self::assertFalse($this->acl->checkUserAccess($action));

        $this->setUser(new ProfileData(['accView' => true, 'accEdit' => true]));
        self::assertFalse($this->acl->checkUserAccess($action));

        $this->setUser(new ProfileData(['accFiles' => true]));
        self::assertTrue($this->acl->checkUserAccess($action));
    }

    #[Test]
    #[DataProvider('accountFileActions')]
    public function accountAdministratorsCanUseTheAccountFileActions(int $action): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: false, isAdminAcc: true));
        $this->context->setUserProfile(new ProfileData());

        self::assertTrue($this->acl->checkUserAccess($action));
    }

    #[Test]
    #[DataProvider('accountFileActions')]
    public function applicationAdministratorsCanUseTheAccountFileActions(int $action): void
    {
        $this->context->setUserData(new UserDto(id: 1, isAdminApp: true, isAdminAcc: false));
        $this->context->setUserProfile(new ProfileData());

        self::assertTrue($this->acl->checkUserAccess($action));
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
