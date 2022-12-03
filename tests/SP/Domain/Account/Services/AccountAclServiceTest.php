<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Domain\Account\Services;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemData;
use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Services\AccountAcl;
use SP\Domain\Account\Services\AccountAclService;
use SP\Domain\Common\Adapters\SimpleModel;
use SP\Domain\User\Ports\UserToUserGroupServiceInterface;
use SP\Infrastructure\File\FileCacheInterface;
use SP\Infrastructure\File\FileException;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountAclServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountAclServiceTest extends UnitaryTestCase
{
    private const ACTIONS = [
        ActionsInterface::ACCOUNT_SEARCH,
        ActionsInterface::ACCOUNT_VIEW,
        ActionsInterface::ACCOUNT_VIEW_PASS,
        ActionsInterface::ACCOUNT_HISTORY_VIEW,
        ActionsInterface::ACCOUNT_CREATE,
        ActionsInterface::ACCOUNT_EDIT,
        ActionsInterface::ACCOUNT_EDIT_PASS,
        ActionsInterface::ACCOUNT_EDIT_RESTORE,
        ActionsInterface::ACCOUNT_COPY,
        ActionsInterface::ACCOUNT_COPY_PASS,
        ActionsInterface::ACCOUNT_DELETE,
    ];
    private static array      $accounts;
    private AccountAclService $accountAclService;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$accounts = [
            1 => [
                'userGroupId'        => 1,
                'userId'             => 1,
                'isPrivate'          => 0,
                'isPrivateGroup'     => 0,
                'otherUserGroupEdit' => 0,
                'otherUserEdit'      => 0,
                'users'              => [new ItemData(['id' => 3, 'isEdit' => 1])],
                'groups'             => [new ItemData(['id' => 2, 'isEdit' => 1])],

            ],
            2 => [
                'userGroupId'        => 1,
                'userId'             => 1,
                'isPrivate'          => 0,
                'isPrivateGroup'     => 0,
                'otherUserGroupEdit' => 0,
                'otherUserEdit'      => 0,
                'users'              => [],
                'groups'             => [
                    new ItemData(['id' => 2, 'isEdit' => 1]),
                    new ItemData(['id' => 3, 'isEdit' => 1]),
                ],
            ],
            3 => [
                'userGroupId'        => 3,
                'userId'             => 3,
                'isPrivate'          => 1,
                'isPrivateGroup'     => 0,
                'otherUserGroupEdit' => 0,
                'otherUserEdit'      => 0,
                'users'              => [],
                'groups'             => [],
            ],
            4 => [
                'userGroupId'        => 3,
                'userId'             => 3,
                'isPrivate'          => 0,
                'isPrivateGroup'     => 1,
                'otherUserGroupEdit' => 0,
                'otherUserEdit'      => 0,
                'users'              => [],
                'groups'             => [],
            ],
        ];
    }

    /**
     * @group acl:admin
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testGetAclForAdminApp(): void
    {
        $this->context
            ->getUserProfile()
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccFiles(true)
            ->setAccDelete(true)
            ->setAccPermission(true)
            ->setAccViewHistory(true);

        $this->checkForUserByExample(
            $this->setUpAccountEnvironment(
                self::$faker->numberBetween(1, 4),
                self::$faker->randomNumber(),
                self::$faker->randomNumber(),
                1,
                0
            ),
            $this->getExampleAclForAdmin()
        );
    }

    /**
     * @param  \SP\Domain\Account\Dtos\AccountAclDto  $accountAclDto  The ACL dto to compile the ACL for the user
     * @param  AccountAcl  $example  An example ACL to test against the compiled ACL
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkForUserByExample(AccountAclDto $accountAclDto, AccountAcl $example): void
    {
        foreach (self::ACTIONS as $action) {
            $example->setActionId($action);

            $aclUnderTest = $this->accountAclService->getAcl($action, $accountAclDto);

            $this->assertTrue($aclUnderTest->isCompiledAccountAccess());
            $this->assertTrue($aclUnderTest->isCompiledShowAccess());

            $this->assertEquals($example->isResultView(), $aclUnderTest->isResultView());
            $this->assertEquals($example->isResultEdit(), $aclUnderTest->isResultEdit());
            $this->assertEquals($example->isShowPermission(), $aclUnderTest->isShowPermission());

            if ($action !== ActionsInterface::ACCOUNT_CREATE
                && $action !== ActionsInterface::ACCOUNT_COPY_PASS
            ) {
                $this->assertEquals($example->checkAccountAccess($action), $aclUnderTest->checkAccountAccess($action));
            }

            if ($action === ActionsInterface::ACCOUNT_VIEW
                || $action === ActionsInterface::ACCOUNT_HISTORY_VIEW
                || $action === ActionsInterface::ACCOUNT_DELETE
            ) {
                $this->assertEquals($example->isShowDetails(), $aclUnderTest->isShowDetails());
            }

            if ($action === ActionsInterface::ACCOUNT_CREATE
                || $action === ActionsInterface::ACCOUNT_COPY
            ) {
                $this->assertEquals($example->isShowPass(), $aclUnderTest->isShowPass());
            }

            if ($action === ActionsInterface::ACCOUNT_EDIT
                || $action === ActionsInterface::ACCOUNT_VIEW
                || $action === ActionsInterface::ACCOUNT_HISTORY_VIEW
            ) {
                $this->assertEquals($example->isShowFiles(), $aclUnderTest->isShowFiles());
            }

            if ($action === ActionsInterface::ACCOUNT_SEARCH
                || $action === ActionsInterface::ACCOUNT_VIEW
                || $action === ActionsInterface::ACCOUNT_VIEW_PASS
                || $action === ActionsInterface::ACCOUNT_HISTORY_VIEW
                || $action === ActionsInterface::ACCOUNT_EDIT
            ) {
                $this->assertEquals($example->isShowViewPass(), $aclUnderTest->isShowViewPass());
            }

            if ($action === ActionsInterface::ACCOUNT_EDIT
                || $action === ActionsInterface::ACCOUNT_CREATE
                || $action === ActionsInterface::ACCOUNT_COPY
            ) {
                $this->assertEquals($example->isShowSave(), $aclUnderTest->isShowSave());
            }

            if ($action === ActionsInterface::ACCOUNT_SEARCH
                || $action === ActionsInterface::ACCOUNT_VIEW
            ) {
                $this->assertEquals($example->isShowEdit(), $aclUnderTest->isShowEdit());
            }

            if ($action === ActionsInterface::ACCOUNT_EDIT
                || $action === ActionsInterface::ACCOUNT_VIEW
            ) {
                $this->assertEquals($example->isShowEditPass(), $aclUnderTest->isShowEditPass());
            }

            if ($action === ActionsInterface::ACCOUNT_SEARCH
                || $action === ActionsInterface::ACCOUNT_DELETE
                || $action === ActionsInterface::ACCOUNT_EDIT
            ) {
                $this->assertEquals($example->isShowDelete(), $aclUnderTest->isShowDelete());
            }

            if ($action === ActionsInterface::ACCOUNT_HISTORY_VIEW) {
                $this->assertEquals($example->isShowRestore(), $aclUnderTest->isShowRestore());
            }

            $this->assertEquals($example->isShowLink(), $aclUnderTest->isShowLink());

            if ($action === ActionsInterface::ACCOUNT_VIEW
                || $action === ActionsInterface::ACCOUNT_HISTORY_VIEW
            ) {
                $this->assertEquals($example->isShowHistory(), $aclUnderTest->isShowHistory());
            }

            if ($action === ActionsInterface::ACCOUNT_SEARCH
                || $action === ActionsInterface::ACCOUNT_VIEW
                || $action === ActionsInterface::ACCOUNT_EDIT
            ) {
                $this->assertEquals($example->isShowCopy(), $aclUnderTest->isShowCopy());
            }
        }
    }

    /**
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     *
     * @param  bool  $isAdminApp
     * @param  bool|int  $isAdminAcc
     *
     * @return AccountAclDto
     */
    private function setUpAccountEnvironment(
        int $accountId,
        int $userId,
        int $groupId,
        bool $isAdminApp = false,
        bool $isAdminAcc = false
    ): AccountAclDto {
        $this->context
            ->getUserData()
            ->setId($userId)
            ->setUserGroupId($groupId)
            ->setIsAdminApp($isAdminApp)
            ->setIsAdminAcc($isAdminAcc);

        return new AccountAclDto(
            $accountId,
            self::$accounts[$accountId]['userId'],
            self::$accounts[$accountId]['users'],
            self::$accounts[$accountId]['userGroupId'],
            self::$accounts[$accountId]['groups'],
            self::$faker->unixTime
        );
    }

    /**
     * @group acl:admin
     *
     * @return \SP\Domain\Account\Services\AccountAcl
     */
    private function getExampleAclForAdmin(): AccountAcl
    {
        return (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView(true)
            ->setResultEdit(true)
            ->setResultView(true)
            ->setResultEdit(true)
            ->setShowCopy(true)
            ->setShowPermission(true)
            ->setShowLink(true)
            ->setShowView(true)
            ->setShowViewPass(true)
            ->setShowRestore(true)
            ->setShowHistory(true)
            ->setShowDelete(true)
            ->setShowEdit(true)
            ->setShowEditPass(true)
            ->setShowFiles(true)
            ->setShowDetails(true)
            ->setShowPass(true);
    }

    /**
     * @group acl:admin
     *
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     */
    public function testGetAclForAdminAcc(): void
    {
        $this->context
            ->getUserProfile()
            ->setAccAdd(true)
            ->setAccView(true)
            ->setAccViewPass(true)
            ->setAccEdit(true)
            ->setAccEditPass(true)
            ->setAccFiles(true)
            ->setAccDelete(true)
            ->setAccPermission(true)
            ->setAccViewHistory(true);

        $exampleAcl = $this->getExampleAclForAdmin()->setShowLink(false);

        $this->checkForUserByExample(
            $this->setUpAccountEnvironment(
                self::$faker->numberBetween(1, 4),
                self::$faker->randomNumber(),
                self::$faker->randomNumber(),
                0,
                1
            ),
            $exampleAcl
        );
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckViewPass(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccViewPass($shouldView);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowViewPass($shouldView);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckDelete(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccDelete($shouldView);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowDelete($shouldEdit);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testEditPass(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccEditPass($shouldEdit);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowEditPass($shouldEdit);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testEditAndRestore(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccEdit($shouldEdit);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowEdit($shouldEdit)
            ->setShowRestore($shouldEdit);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckPermission(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccPermission($shouldEdit);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowPermission($shouldEdit);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testViewFiles(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccFiles($shouldEdit);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowFiles($shouldView);

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @group acl:action
     * @dataProvider accountPropertiesProvider
     *
     * @param  int  $accountId
     * @param  int  $userId
     * @param  int  $groupId
     * @param  bool  $shouldView
     * @param  bool  $shouldEdit
     *
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCheckView(
        int $accountId,
        int $userId,
        int $groupId,
        bool $shouldView = true,
        bool $shouldEdit = true
    ): void {
        $shouldViewOrEdit = $shouldView || $shouldEdit;

        $this->context
            ->getUserProfile()
            ->setAccView($shouldViewOrEdit)
            ->setAccEdit($shouldEdit);

        $example = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($shouldViewOrEdit)
            ->setResultEdit($shouldEdit)
            ->setShowView($shouldViewOrEdit)
            ->setShowEdit($shouldEdit)
            ->setShowRestore($shouldEdit)
            ->setShowDetails($shouldViewOrEdit)
            ->setShowPermission(
                AccountAclService::getShowPermission($this->context->getUserData(), $this->context->getUserProfile())
            );

        $this->checkForUserByExample($this->setUpAccountEnvironment($accountId, $userId, $groupId), $example);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCacheIsUsedWithHit(): void
    {
        $dto = new AccountAclDto(
            self::$faker->randomNumber(),
            1,
            [],
            self::$faker->randomNumber(),
            [],
            self::$faker->unixTime
        );

        $userToUserGroupService = $this->createMock(UserToUserGroupServiceInterface::class);
        $userToUserGroupService->method('getGroupsForUser')->willReturn([]);
        $fileCache = $this->createMock(FileCacheInterface::class);

        $accountAclService = new AccountAclService(
            $this->application,
            new Acl($this->context, $this->application->getEventDispatcher()),
            $userToUserGroupService,
            $fileCache
        );

        $this->context->getUserData()->setLastUpdate($dto->getDateEdit() + 10);

        $acl = new AccountAcl(self::$faker->randomNumber());
        $acl->setTime($dto->getDateEdit() + 10);

        $fileCache->expects(self::once())
            ->method('load')
            ->with(self::callback((static fn($path) => is_string($path))))
            ->willReturn($acl);

        $accountAclService->getAcl(self::$faker->randomNumber(), $dto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCacheIsUsedWithMiss(): void
    {
        $dto = new AccountAclDto(
            self::$faker->randomNumber(),
            1,
            [],
            self::$faker->randomNumber(),
            [],
            self::$faker->unixTime
        );

        $userToUserGroupService = $this->createMock(UserToUserGroupServiceInterface::class);
        $userToUserGroupService->method('getGroupsForUser')->willReturn([]);
        $fileCache = $this->createMock(FileCacheInterface::class);

        $accountAclService = new AccountAclService(
            $this->application,
            new Acl($this->context, $this->application->getEventDispatcher()),
            $userToUserGroupService,
            $fileCache
        );

        $acl = new AccountAcl(self::$faker->randomNumber());

        $fileCache->expects(self::once())
            ->method('load')
            ->with(self::callback((static fn($path) => is_string($path))))
            ->willReturn($acl);

        $fileCache->expects(self::once())
            ->method('save')
            ->with(
                self::callback((static fn($acl) => $acl instanceof AccountAcl)),
                self::callback((static fn($path) => is_string($path)))
            );

        $accountAclService->getAcl(self::$faker->randomNumber(), $dto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCacheLoadThrowsExceptionAndLogged(): void
    {
        $dto = new AccountAclDto(
            self::$faker->randomNumber(),
            1,
            [],
            self::$faker->randomNumber(),
            [],
            self::$faker->unixTime
        );

        $userToUserGroupService = $this->createMock(UserToUserGroupServiceInterface::class);
        $userToUserGroupService->method('getGroupsForUser')->willReturn([]);
        $fileCache = $this->createMock(FileCacheInterface::class);

        $accountAclService = new AccountAclService(
            $this->application,
            new Acl($this->context, $this->application->getEventDispatcher()),
            $userToUserGroupService,
            $fileCache
        );

        $fileCache->expects(self::once())
            ->method('load')
            ->with(self::callback((static fn($path) => is_string($path))))
            ->willThrowException(new FileException('test'));

        $accountAclService->getAcl(self::$faker->randomNumber(), $dto);
    }

    /**
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function testCacheSaveThrowsExceptionAndLogged(): void
    {
        $dto = new AccountAclDto(
            self::$faker->randomNumber(),
            1,
            [],
            self::$faker->randomNumber(),
            [],
            self::$faker->unixTime
        );

        $userToUserGroupService = $this->createMock(UserToUserGroupServiceInterface::class);
        $userToUserGroupService->method('getGroupsForUser')->willReturn([]);
        $fileCache = $this->createMock(FileCacheInterface::class);

        $accountAclService = new AccountAclService(
            $this->application,
            new Acl($this->context, $this->application->getEventDispatcher()),
            $userToUserGroupService,
            $fileCache
        );

        $fileCache->expects(self::once())
            ->method('save')
            ->with(
                self::callback((static fn($acl) => $acl instanceof AccountAcl)),
                self::callback((static fn($path) => is_string($path)))
            )
            ->willThrowException(new FileException('test'));

        $accountAclService->getAcl(self::$faker->randomNumber(), $dto);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $acl = new Acl($this->context, $this->application->getEventDispatcher());
        $userToUserGroupService = $this->createMock(UserToUserGroupServiceInterface::class);
        $userToUserGroupService->method('getGroupsForUser')
            ->willReturnMap([
                [1, [new SimpleModel(['userGroupId' => 2])]],
                [2, [new SimpleModel(['userGroupId' => 1])]],
                [3, [new SimpleModel(['userGroupId' => 2])]],
                [4, []],
            ]);

        $this->accountAclService = new AccountAclService(
            $this->application,
            $acl,
            $userToUserGroupService
        );
    }

    private function accountPropertiesProvider(): array
    {
        /**
         * Account |View      |Edit
         * 1       |u=3 g=1,2 |u=3 g=1,2
         * 2       |g=1,2,3   |g=1,2,3
         * 3       |u=3 g=3   |u=3 g=3
         * 4       |u=3       |u=3
         *
         * User | Group
         * 1    | 2
         * 2    | 1
         * 3    | 2
         * 4    | None
         * Matrix: Account | UserId | GroupId | ShouldView | ShouldEdit
         */
        return [
            [1, 2, 2, true, true],
            [1, 3, 0, true, true],
            [1, 3, 1, true, true],
            [1, 3, 2, true, true],
            [1, 4, 0, false, false],
            [1, 4, 3, false, false],
            [1, 4, 4, false, false],
            [2, 2, 1, true, true],
            [2, 2, 2, true, true],
            [2, 2, 3, true, true],
            [2, 3, 0, false, false],
            [2, 3, 3, true, true],
            [2, 4, 0, false, false],
            [2, 4, 1, true, true],
            [2, 4, 2, true, true],
            [2, 4, 3, true, true],
            [2, 4, 4, false, false],
            [3, 1, 1, false, false],
            [3, 1, 2, false, false],
            [3, 1, 3, true, true],
            [3, 2, 1, false, false],
            [3, 2, 2, false, false],
            [3, 2, 3, true, true],
            [3, 3, 0, true, true],
            [3, 3, 1, true, true],
            [3, 3, 2, true, true],
            [3, 3, 3, true, true],
            [4, 3, 0, true, true],
            [4, 3, 1, true, true],
            [4, 3, 2, true, true],
            [4, 3, 3, true, true],
        ];
    }
}
