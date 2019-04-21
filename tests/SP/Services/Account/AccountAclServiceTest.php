<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Services\Account;

use Closure;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\StatelessContext;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\Dto\AccountAclDto;
use SP\DataModel\Dto\AccountDetailsResponse;
use SP\Repositories\NoSuchItemException;
use SP\Services\Account\AccountAcl;
use SP\Services\Account\AccountAclService;
use SP\Services\Account\AccountService;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountAclServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountAclServiceTest extends DatabaseTestCase
{
    /**
     * @var Closure
     */
    private static $service;
    /**
     * @var AccountService
     */
    private static $accountService;
    /**
     * @var StatelessContext
     */
    private static $context;
    /**
     * @var array
     */
    private static $actions = [
        Acl::ACCOUNT_SEARCH,
        Acl::ACCOUNT_VIEW,
        Acl::ACCOUNT_VIEW_PASS,
        Acl::ACCOUNT_HISTORY_VIEW,
        Acl::ACCOUNT_CREATE,
        Acl::ACCOUNT_EDIT,
        Acl::ACCOUNT_EDIT_PASS,
        Acl::ACCOUNT_EDIT_RESTORE,
        Acl::ACCOUNT_COPY,
        Acl::ACCOUNT_COPY_PASS,
        Acl::ACCOUNT_DELETE
    ];
    /**
     * @var AccountDetailsResponse
     */
    protected $account;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_accountAcl.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        self::$context = $dic->get(ContextInterface::class);

        // Es necesario utilizar una función anónima para evitar la fijación
        // de los datos del contexto
        self::$service = function () use ($dic) {
            return new AccountAclService($dic);
        };

        self::$accountService = $dic->get(AccountService::class);
    }

    /**
     * testSaveAclInCache
     */
    public function testSaveAclInCache()
    {
        /** @var AccountAclService $service */
        $service = self::$service->call($this);

        $accountAcl = new AccountAcl(10);
        $accountAcl->setAccountId(1);
        $accountAcl->setCompiledAccountAccess(true);
        $accountAcl->setCompiledShowAccess(true);
        $accountAcl->setResultView(true);
        $accountAcl->setResultEdit(true);
        $accountAcl->setShowCopy(true);
        $accountAcl->setShowPermission(true);
        $accountAcl->setShowLink(false);
        $accountAcl->setShowView(true);
        $accountAcl->setShowViewPass(true);
        $accountAcl->setShowRestore(true);
        $accountAcl->setShowHistory(true);
        $accountAcl->setShowDelete(true);
        $accountAcl->setShowEdit(true);
        $accountAcl->setShowEditPass(true);
        $accountAcl->setShowFiles(true);
        $accountAcl->setShowDetails(true);
        $accountAcl->setShowPass(true);

        $service->saveAclInCache($accountAcl);

        $result = $service->getAclFromCache(1, 10);

        $this->assertInstanceOf(AccountAcl::class, $result);
        $this->assertEquals($accountAcl, $result);

        $accountAcl->reset();

        $this->assertNotEquals($accountAcl, $result);
    }

    /**
     * testClearAcl
     */
    public function testClearAcl()
    {
        /** @var AccountAclService $service */
        $service = self::$service->call($this);

        $accountAcl = new AccountAcl(10);
        $accountAcl->setAccountId(1);

        $service->saveAclInCache($accountAcl);

        $this->assertTrue(AccountAclService::clearAcl(1));

        $this->assertFalse(AccountAclService::clearAcl(2));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetAclAdmin()
    {
        $this->checkAllowAll($this->setUpAccountEnvironment(1, 1, 1, 1));
        $this->checkAllowAll($this->setUpAccountEnvironment(2, 1, 1, 1));

        $accountAcl = new AccountAcl(0);
        $accountAcl->setCompiledAccountAccess(true);
        $accountAcl->setCompiledShowAccess(true);
        $accountAcl->setResultView(true);
        $accountAcl->setResultEdit(true);
        $accountAcl->setShowCopy(true);
        $accountAcl->setShowPermission(true);
        $accountAcl->setShowLink(false);
        $accountAcl->setShowView(true);
        $accountAcl->setShowViewPass(true);
        $accountAcl->setShowRestore(true);
        $accountAcl->setShowHistory(true);
        $accountAcl->setShowDelete(true);
        $accountAcl->setShowEdit(true);
        $accountAcl->setShowEditPass(true);
        $accountAcl->setShowFiles(true);
        $accountAcl->setShowDetails(true);
        $accountAcl->setShowPass(true);

        $this->checkForUserByExample($this->setUpAccountEnvironment(1, 1, 1, 0, 1), $accountAcl);
        $this->checkForUserByExample($this->setUpAccountEnvironment(2, 1, 1, 0, 1), $accountAcl);
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param bool          $should
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkAllowAll(AccountAclDto $accountAclDto, $should = true)
    {
        self::$context
            ->getUserProfile()
            ->reset()
            ->setAccAdd($should)
            ->setAccView($should)
            ->setAccViewPass($should)
            ->setAccEdit($should)
            ->setAccEditPass($should)
            ->setAccFiles($should)
            ->setAccDelete($should)
            ->setAccPermission($should)
            ->setAccViewHistory($should);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should)
            ->setResultEdit($should)
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

        $this->checkForUserByExample($accountAclDto, $accountAcl);
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param AccountAcl    $accountAclExample
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkForUserByExample(AccountAclDto $accountAclDto, AccountAcl $accountAclExample)
    {
        /** @var AccountAclService $service */
        $service = self::$service->call($this);

        foreach (self::$actions as $action) {
            $accountAclExample->setActionId($action);

            $accountAcl = $service->getAcl($action, $accountAclDto);

            $this->assertInstanceOf(AccountAcl::class, $accountAcl);
            $this->assertTrue($accountAcl->isCompiledAccountAccess());
            $this->assertTrue($accountAcl->isCompiledShowAccess());

            $this->assertEquals($accountAclExample->isResultView(), $accountAcl->isResultView());
            $this->assertEquals($accountAclExample->isResultEdit(), $accountAcl->isResultEdit());

            if ($action !== Acl::ACCOUNT_CREATE
                && $action !== Acl::ACCOUNT_COPY_PASS
            ) {
                $this->assertEquals($accountAclExample->checkAccountAccess($action), $accountAcl->checkAccountAccess($action));
            }

            if ($action === Acl::ACCOUNT_VIEW
                || $action === Acl::ACCOUNT_HISTORY_VIEW
                || $action === Acl::ACCOUNT_DELETE
            ) {
                $this->assertEquals($accountAclExample->isShowDetails(), $accountAcl->isShowDetails());
            }

            if ($action === Acl::ACCOUNT_CREATE
                || $action === Acl::ACCOUNT_COPY
            ) {
                $this->assertEquals($accountAclExample->isShowPass(), $accountAcl->isShowPass());
            }

            if ($action === Acl::ACCOUNT_EDIT
                || $action === Acl::ACCOUNT_VIEW
                || $action === Acl::ACCOUNT_HISTORY_VIEW
            ) {
                $this->assertEquals($accountAclExample->isShowFiles(), $accountAcl->isShowFiles());
            }

            if ($action === Acl::ACCOUNT_SEARCH
                || $action === Acl::ACCOUNT_VIEW
                || $action === Acl::ACCOUNT_VIEW_PASS
                || $action === Acl::ACCOUNT_HISTORY_VIEW
                || $action === Acl::ACCOUNT_EDIT
            ) {
                $this->assertEquals($accountAclExample->isShowViewPass(), $accountAcl->isShowViewPass());
            }

            if ($action === Acl::ACCOUNT_EDIT
                || $action === Acl::ACCOUNT_CREATE
                || $action === Acl::ACCOUNT_COPY
            ) {
                $this->assertEquals($accountAclExample->isShowSave(), $accountAcl->isShowSave());
            }

            if ($action === Acl::ACCOUNT_SEARCH
                || $action === Acl::ACCOUNT_VIEW
            ) {
                $this->assertEquals($accountAclExample->isShowEdit(), $accountAcl->isShowEdit());
            }

            if ($action === Acl::ACCOUNT_EDIT
                || $action === Acl::ACCOUNT_VIEW
            ) {
                $this->assertEquals($accountAclExample->isShowEditPass(), $accountAcl->isShowEditPass());
            }

            if ($action === Acl::ACCOUNT_SEARCH
                || $action === Acl::ACCOUNT_DELETE
                || $action === Acl::ACCOUNT_EDIT
            ) {
                $this->assertEquals($accountAclExample->isShowDelete(), $accountAcl->isShowDelete());
            }

            if ($action === Acl::ACCOUNT_HISTORY_VIEW) {
                $this->assertEquals($accountAclExample->isShowRestore(), $accountAcl->isShowRestore());
            }

            $this->assertEquals($accountAclExample->isShowLink(), $accountAcl->isShowLink());

            if ($action === Acl::ACCOUNT_VIEW
                || $action === Acl::ACCOUNT_HISTORY_VIEW
            ) {
                $this->assertEquals($accountAclExample->isShowHistory(), $accountAcl->isShowHistory());
            }

            if ($action === Acl::ACCOUNT_SEARCH
                || $action === Acl::ACCOUNT_VIEW
                || $action === Acl::ACCOUNT_EDIT
            ) {
                $this->assertEquals($accountAclExample->isShowCopy(), $accountAcl->isShowCopy());
            }
        }
    }

    /**
     * @param     $accountId
     * @param     $userId
     * @param     $groupId
     *
     * @param int $isAdminApp
     * @param int $isAdminAcc
     *
     * @return AccountAclDto
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    private function setUpAccountEnvironment($accountId, $userId, $groupId, $isAdminApp = 0, $isAdminAcc = 0)
    {
        AccountAclService::$useCache = false;

        if ($this->account === null || $this->account->getId() !== $accountId) {
            $this->account = self::$accountService->getById($accountId);
            self::$accountService->withUsersById($this->account);
            self::$accountService->withUserGroupsById($this->account);
        }

        $userData = new UserLoginResponse();
        $userData->setId($userId);
        $userData->setIsAdminApp($isAdminApp);
        $userData->setIsAdminAcc($isAdminAcc);
        $userData->setUserGroupId($groupId);

        self::$context->setUserData($userData);

        return AccountAclDto::makeFromAccount($this->account);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function testGetAclUser()
    {
        $accountAclDto = $this->setUpAccountEnvironment(1, 2, 2);

        $this->checkView($accountAclDto);
        $this->checkViewPass($accountAclDto);
        $this->checkDelete($accountAclDto);
        $this->checkEditPass($accountAclDto);
        $this->checkEditAndRestore($accountAclDto);
        $this->checkPermissions($accountAclDto);
        $this->checkViewFiles($accountAclDto);

        $accountAclDto = $this->setUpAccountEnvironment(1, 3, 3);

        $this->checkView($accountAclDto);
        $this->checkViewPass($accountAclDto);
        $this->checkDelete($accountAclDto);
        $this->checkEditPass($accountAclDto);
        $this->checkEditAndRestore($accountAclDto);
        $this->checkPermissions($accountAclDto);
        $this->checkViewFiles($accountAclDto);

        $accountAclDto = $this->setUpAccountEnvironment(1, 4, 3);
        $should = ['view' => false, 'edit' => false];

        $this->checkView($accountAclDto, $should);
        $this->checkViewPass($accountAclDto, $should);
        $this->checkDelete($accountAclDto, $should);
        $this->checkEditPass($accountAclDto, $should);
        $this->checkEditAndRestore($accountAclDto, $should);
        $this->checkPermissions($accountAclDto, $should);
        $this->checkViewFiles($accountAclDto, $should);

        $accountAclDto = $this->setUpAccountEnvironment(2, 1, 1);

        $this->checkView($accountAclDto);
        $this->checkViewPass($accountAclDto);
        $this->checkDelete($accountAclDto);
        $this->checkEditPass($accountAclDto);
        $this->checkEditAndRestore($accountAclDto);
        $this->checkPermissions($accountAclDto);
        $this->checkViewFiles($accountAclDto);

        $accountAclDto = $this->setUpAccountEnvironment(2, 2, 2);

        $this->checkView($accountAclDto);
        $this->checkViewPass($accountAclDto);
        $this->checkDelete($accountAclDto);
        $this->checkEditPass($accountAclDto);
        $this->checkEditAndRestore($accountAclDto);
        $this->checkPermissions($accountAclDto);
        $this->checkViewFiles($accountAclDto);

        $accountAclDto = $this->setUpAccountEnvironment(2, 3, 3);
        $should = ['view' => true, 'edit' => false];

        $this->checkView($accountAclDto, $should);
        $this->checkViewPass($accountAclDto, $should);
        $this->checkDelete($accountAclDto, $should, true, false);
        $this->checkEditPass($accountAclDto, $should, true, false);
        $this->checkEditAndRestore($accountAclDto, $should, true, false);
        $this->checkPermissions($accountAclDto, $should);
        $this->checkViewFiles($accountAclDto, $should);


        $accountAclDto = $this->setUpAccountEnvironment(2, 4, 3);
        $should = ['view' => true, 'edit' => false];

        $this->checkView($accountAclDto, $should);
        $this->checkViewPass($accountAclDto, $should);
        $this->checkDelete($accountAclDto, $should, true, false);
        $this->checkEditPass($accountAclDto, $should, true, false);
        $this->checkEditAndRestore($accountAclDto, $should, true, false);
        $this->checkPermissions($accountAclDto, $should);
        $this->checkViewFiles($accountAclDto, $should);

        $accountAclDto = $this->setUpAccountEnvironment(2, 5, 4);
        $should = ['view' => false, 'edit' => false];

        $this->checkView($accountAclDto, $should);
        $this->checkViewPass($accountAclDto, $should);
        $this->checkDelete($accountAclDto, $should);
        $this->checkEditPass($accountAclDto, $should);
        $this->checkEditAndRestore($accountAclDto, $should);
        $this->checkPermissions($accountAclDto, $should);
        $this->checkViewFiles($accountAclDto, $should);

        $accountAclDto = $this->setUpAccountEnvironment(2, 1, 1);

        $this->checkView($accountAclDto);
        $this->checkViewPass($accountAclDto);
        $this->checkDelete($accountAclDto);
        $this->checkEditPass($accountAclDto);
        $this->checkEditAndRestore($accountAclDto);
        $this->checkPermissions($accountAclDto);
        $this->checkViewFiles($accountAclDto);
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should  Sets view and edit status
     * @param bool          $profile Sets profile action status
     * @param bool          $acl     Sets ACL expected result
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkView(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccView($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowDetails($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccView(true);
            $accountAcl->setShowDetails(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccView(false);
            $accountAcl->setShowDetails(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkViewPass(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccViewPass($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowViewPass($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccViewPass(true);
            $accountAcl->setShowViewPass(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true && $acl === true) { // Checks ACL when profile action is denied
            $userProfile->setAccViewPass(false);
            $accountAcl->setShowViewPass(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkDelete(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccDelete($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowDelete($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccDelete(true);
            $accountAcl->setShowDelete(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccDelete(false);
            $accountAcl->setShowDelete(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkEditPass(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccEditPass($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowEditPass($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccEditPass(true);
            $accountAcl->setShowEditPass(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccEditPass(false);
            $accountAcl->setShowEditPass(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkEditAndRestore(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccEdit($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowEdit($acl)
            ->setShowRestore($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccEdit(true);
            $accountAcl->setShowEdit(false)
                ->setShowRestore(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccEdit(false);
            $accountAcl->setShowEdit(false)
                ->setShowRestore(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkPermissions(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccPermission($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowPermission($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccPermission(true);
            $accountAcl->setShowPermission(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccPermission(false);
            $accountAcl->setShowPermission(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * @param AccountAclDto $accountAclDto
     *
     * @param array         $should
     *
     * @param bool          $profile
     * @param bool          $acl
     *
     * @throws ConstraintException
     * @throws QueryException
     */
    private function checkViewFiles(AccountAclDto $accountAclDto, $should = ['view' => true, 'edit' => true], $profile = true, $acl = true)
    {
        $userProfile = self::$context
            ->getUserProfile()
            ->reset()
            ->setAccFiles($profile);

        $accountAcl = (new AccountAcl(0))
            ->setCompiledAccountAccess(true)
            ->setCompiledShowAccess(true)
            ->setResultView($should['view'])
            ->setResultEdit($should['edit'])
            ->setShowFiles($acl);

        $this->checkForUserByExample($accountAclDto, $accountAcl);

        // Checks if ACL returns false when profile action is granted and account access is denied
        if ($should['view'] === false && $should['edit'] === false) {
            $userProfile->setAccFiles(true);
            $accountAcl->setShowFiles(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        } elseif ($profile === true) { // Checks ACL when profile action is denied
            $userProfile->setAccFiles(false);
            $accountAcl->setShowFiles(false);

            $this->checkForUserByExample($accountAclDto, $accountAcl);
        }
    }

    /**
     * testGetAclFromCache
     */
    public function testGetAclFromCache()
    {
        /** @var AccountAclService $service */
        $service = self::$service->call($this);

        $this->assertNull($service->getAclFromCache(1, 10));
    }
}
