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

namespace SP\Tests\SP\Core\Acl;

use DI\DependencyException;
use DI\NotFoundException;
use PHPUnit\Framework\TestCase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\DataModel\ProfileData;
use SP\Services\User\UserLoginResponse;
use function SP\Tests\setupContext;

/**
 * Class AclTest
 *
 * @package SP\Tests\SP\Core\Acl
 */
class AclTest extends TestCase
{
    /**
     * @var ContextInterface
     */
    private $context;
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @dataProvider actionsProvider
     *
     * @param $id
     * @param $expected
     */
    public function testGetActionRoute($id, $expected)
    {
        $this->assertEquals($expected, Acl::getActionRoute($id));
    }

    /**
     * testGetActionRouteUnknown
     */
    public function testGetActionRouteUnknown()
    {
        $this->assertEmpty(Acl::getActionRoute(10000));
    }

    /**
     * @dataProvider actionsProvider
     *
     * @param $id
     */
    public function testCheckUserAccessAdminApp($id)
    {
        $this->assertTrue($this->acl->checkUserAccess($id));
    }

    /**
     * testCheckUserAccessAccountView
     */
    public function testCheckUserAccessAccountView()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccView(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_VIEW]);
    }

    /**
     * @param int[] $actionsId Masked action Id
     */
    private function checkUserAccess(array $actionsId)
    {
        $actionsMask = array_merge([
            ActionsInterface::ACCOUNT_REQUEST,
            ActionsInterface::NOTIFICATION,
            ActionsInterface::NOTIFICATION_VIEW,
            ActionsInterface::NOTIFICATION_SEARCH,
            ActionsInterface::NOTIFICATION_CHECK,
        ], $actionsId);

        $actionsFalse = array_filter($this->actionsProvider(), function ($action) use ($actionsMask) {
            return !in_array($action[0], $actionsMask);
        });

        $actionsTrue = array_filter($this->actionsProvider(), function ($action) use ($actionsMask) {
            return in_array($action[0], $actionsMask);
        });

        foreach ($actionsFalse as $action) {
            $this->assertFalse($this->acl->checkUserAccess($action[0]));
        }

        foreach ($actionsTrue as $action) {
            $this->assertTrue($this->acl->checkUserAccess($action[0]));
        }
    }

    /**
     * @return array
     */
    public function actionsProvider()
    {
        return [
            [2, 'account/search'],
            [1, 'account/index'],
            [20, 'account/listFile'],
            [12, 'account/requestAccess'],
            [30, 'favorite/index'],
            [1201, 'wiki/index'],
            [5001, 'itemManager/index'],
            [101, 'category/index'],
            [301, 'client/index'],
            [1001, 'authToken/index'],
            [401, 'customField/index'],
            [501, 'publicLink/index'],
            [601, 'file/index'],
            [1301, 'accountManager/index'],
            [201, 'tag/index'],
            [1101, 'plugin/index'],
            [5002, 'accessManager/index'],
            [701, 'user/index'],
            [801, 'group/index'],
            [901, 'profile/index'],
            [1701, 'eventlog/index'],
            [1702, 'eventlog/search'],
            [1703, 'eventlog/clear'],
            [3, 'account/view'],
            [4, 'account/create'],
            [5, 'account/edit'],
            [6, 'account/delete'],
            [7, 'account/viewPass'],
            [8, 'account/editPass'],
            [9, 'account/restore'],
            [10, 'account/copy'],
            [11, 'account/copyPass'],
            [21, 'accountFile/view'],
            [22, 'accountFile/upload'],
            [23, 'accountFile/download'],
            [24, 'accountFile/delete'],
            [25, 'accountFile/search'],
            [26, 'accountFile/list'],
            [31, 'favorite/view'],
            [32, 'accountFavorite/mark'],
            [33, 'accountFavorite/unmark'],
            [40, 'account/viewHistory'],
            [41, 'account/viewPassHistory'],
            [42, 'account/copyPassHistory'],
            [1203, 'wiki/view'],
            [1204, 'wiki/create'],
            [1205, 'wiki/edit'],
            [1206, 'wiki/delete'],
            [103, 'category/view'],
            [104, 'category/create'],
            [105, 'category/edit'],
            [106, 'category/delete'],
            [102, 'category/search'],
            [303, 'client/view'],
            [304, 'client/create'],
            [305, 'client/edit'],
            [306, 'client/delete'],
            [302, 'client/search'],
            [1004, 'authToken/create'],
            [1003, 'authToken/view'],
            [1005, 'authToken/edit'],
            [1006, 'authToken/delete'],
            [1002, 'authToken/search'],
            [404, 'customField/create'],
            [403, 'customField/view'],
            [405, 'customField/edit'],
            [406, 'customField/delete'],
            [402, 'customField/search'],
            [504, 'publicLink/create'],
            [503, 'publicLink/view'],
            [506, 'publicLink/delete'],
            [507, 'publicLink/refresh'],
            [502, 'publicLink/search'],
            [603, 'file/view'],
            [605, 'file/download'],
            [606, 'file/delete'],
            [604, 'file/upload'],
            [602, 'file/search'],
            [1303, 'accountManager/view'],
            [1304, 'accountManager/delete'],
            [1302, 'accountManager/search'],
            [204, 'tag/create'],
            [203, 'tag/view'],
            [205, 'tag/edit'],
            [206, 'tag/delete'],
            [202, 'tag/search'],
            [1104, 'plugin/create'],
            [1103, 'plugin/view'],
            [1102, 'plugin/search'],
            [1105, 'plugin/enable'],
            [1106, 'plugin/disable'],
            [1107, 'plugin/reset'],
            [703, 'user/view'],
            [704, 'user/create'],
            [705, 'user/edit'],
            [706, 'user/delete'],
            [707, 'user/editPass'],
            [702, 'user/search'],
            [803, 'userGroup/view'],
            [804, 'userGroup/create'],
            [805, 'userGroup/edit'],
            [806, 'userGroup/delete'],
            [802, 'userGroup/search'],
            [903, 'userProfile/view'],
            [904, 'userProfile/create'],
            [905, 'userProfile/edit'],
            [906, 'userProfile/delete'],
            [902, 'userProfile/search'],
            [5010, 'userSettingsManager/index'],
            [5011, 'userSettings/general'],
            [1401, 'notification/index'],
            [1501, 'configManager/index'],
            [1502, 'configManager/general'],
            [1510, 'account/config'],
            [1520, 'wiki/config'],
            [1530, 'encryption/config'],
            [1531, 'encryption/updateHash'],
            [1532, 'encryption/createTempPass'],
            [1540, 'backup/config'],
            [1541, 'backup/backup'],
            [1550, 'import/config'],
            [1551, 'import/csv'],
            [1552, 'import/xml'],
            [1560, 'export/config'],
            [1561, 'export/export'],
            [1570, 'mail/config'],
            [1580, 'ldap/config'],
            [1581, 'ldap/sync'],
            [1311, 'accountHistoryManager/index'],
            [1314, 'accountHistoryManager/delete'],
            [1312, 'accountHistoryManager/search'],
            [1315, 'accountHistoryManager/restore'],
            [1403, 'notification/view'],
            [1404, 'notification/create'],
            [1405, 'notification/edit'],
            [1406, 'notification/delete'],
            [1407, 'notification/check'],
            [1402, 'notification/search'],
            [1801, 'itemPreset/index'],
            [1802, 'itemPreset/search'],
            [1803, 'itemPreset/view'],
            [1804, 'itemPreset/create'],
            [1805, 'itemPreset/edit'],
            [1806, 'itemPreset/delete']
        ];
    }

    /**
     * testCheckUserAccessAdminAcc
     */
    public function testCheckUserAccessAdminAcc()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setIsAdminAcc(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile(new ProfileData());

        $this->checkUserAccess([
            ActionsInterface::ACCOUNT_VIEW,
            ActionsInterface::ACCOUNT_VIEW_PASS,
            ActionsInterface::ACCOUNT_HISTORY_VIEW,
            ActionsInterface::ACCOUNT_EDIT,
            ActionsInterface::ACCOUNT_EDIT_PASS,
            ActionsInterface::ACCOUNT_CREATE,
            ActionsInterface::ACCOUNT_COPY,
            ActionsInterface::ACCOUNT_DELETE,
            ActionsInterface::ACCOUNT_FILE,
            ActionsInterface::ACCOUNTMGR,
            ActionsInterface::ACCOUNTMGR_SEARCH,
            ActionsInterface::ACCOUNTMGR_HISTORY,
            ActionsInterface::ACCOUNTMGR_HISTORY_SEARCH,
            ActionsInterface::ITEMS_MANAGE
        ]);
    }

    /**
     * testCheckUserAccessAccountEdit
     */
    public function testCheckUserAccessAccountEdit()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccEdit(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_EDIT, ActionsInterface::ACCOUNT_VIEW]);
    }

    /**
     * testCheckUserAccessAccountEditPass
     */
    public function testCheckUserAccessAccountEditPass()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccEditPass(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_EDIT_PASS]);
    }

    /**
     * testCheckUserAccessAccountCreate
     */
    public function testCheckUserAccessAccountCreate()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccAdd(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_CREATE]);
    }

    /**
     * testCheckUserAccessAccountCopy
     */
    public function testCheckUserAccessAccountCopy()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccAdd(true);
        $userProfile->setAccView(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ACCOUNT_COPY,
            ActionsInterface::ACCOUNT_VIEW,
            ActionsInterface::ACCOUNT_CREATE
        ]);
    }

    /**
     * testCheckUserAccessAccountDelete
     */
    public function testCheckUserAccessAccountDelete()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccDelete(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_DELETE]);
    }

    /**
     * testCheckUserAccessAccountFile
     */
    public function testCheckUserAccessAccountFile()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccFiles(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_FILE]);
    }

    /**
     * testCheckUserAccessConfigGeneral
     */
    public function testCheckUserAccessConfigGeneral()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setConfigGeneral(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::CONFIG,
            ActionsInterface::CONFIG_GENERAL,
            ActionsInterface::PLUGIN,
            ActionsInterface::PLUGIN_SEARCH,
            ActionsInterface::PLUGIN_DISABLE,
            ActionsInterface::PLUGIN_ENABLE,
            ActionsInterface::PLUGIN_RESET,
            ActionsInterface::PLUGIN_VIEW,
            ActionsInterface::CONFIG_ACCOUNT,
            ActionsInterface::CONFIG_WIKI,
            ActionsInterface::CONFIG_LDAP,
            ActionsInterface::CONFIG_MAIL
        ]);
    }

    /**
     * testCheckUserAccessConfigImport
     */
    public function testCheckUserAccessConfigImport()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setConfigImport(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::CONFIG,
            ActionsInterface::CONFIG_IMPORT
        ]);
    }

    /**
     * testCheckUserAccessCategory
     */
    public function testCheckUserAccessCategory()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmCategories(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::CATEGORY,
            ActionsInterface::CATEGORY_SEARCH,
            ActionsInterface::CATEGORY_VIEW,
            ActionsInterface::CATEGORY_CREATE,
            ActionsInterface::CATEGORY_EDIT,
            ActionsInterface::CATEGORY_DELETE
        ]);
    }

    /**
     * testCheckUserAccessClient
     */
    public function testCheckUserAccessClient()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmCustomers(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::CLIENT,
            ActionsInterface::CLIENT_SEARCH,
            ActionsInterface::CLIENT_VIEW,
            ActionsInterface::CLIENT_CREATE,
            ActionsInterface::CLIENT_EDIT,
            ActionsInterface::CLIENT_DELETE
        ]);
    }

    /**
     * testCheckUserAccessCustomField
     */
    public function testCheckUserAccessCustomField()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmCustomFields(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::CUSTOMFIELD,
            ActionsInterface::CUSTOMFIELD_SEARCH,
            ActionsInterface::CUSTOMFIELD_VIEW,
            ActionsInterface::CUSTOMFIELD_CREATE,
            ActionsInterface::CUSTOMFIELD_EDIT,
            ActionsInterface::CUSTOMFIELD_DELETE
        ]);
    }

    /**
     * testCheckUserAccessPublicLink
     */
    public function testCheckUserAccessPublicLink()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmPublicLinks(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::PUBLICLINK,
            ActionsInterface::PUBLICLINK_SEARCH,
            ActionsInterface::PUBLICLINK_CREATE,
            ActionsInterface::PUBLICLINK_REFRESH,
            ActionsInterface::PUBLICLINK_VIEW,
            ActionsInterface::PUBLICLINK_EDIT,
            ActionsInterface::PUBLICLINK_DELETE
        ]);
    }

    /**
     * testCheckUserAccessPublicLinkCreate
     */
    public function testCheckUserAccessPublicLinkCreate()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccPublicLinks(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::PUBLICLINK_CREATE,
            ActionsInterface::PUBLICLINK_REFRESH
        ]);
    }

    /**
     * testCheckUserAccessAccount
     */
    public function testCheckUserAccessAccount()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmAccounts(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::ACCOUNTMGR,
            ActionsInterface::ACCOUNTMGR_SEARCH,
            ActionsInterface::ACCOUNTMGR_HISTORY,
            ActionsInterface::ACCOUNTMGR_HISTORY_SEARCH
        ]);
    }

    /**
     * testCheckUserAccessFile
     */
    public function testCheckUserAccessFile()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmFiles(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::FILE,
            ActionsInterface::FILE_SEARCH,
            ActionsInterface::FILE_DELETE,
            ActionsInterface::FILE_VIEW,
            ActionsInterface::FILE_DOWNLOAD
        ]);
    }

    /**
     * testCheckUserAccessTag
     */
    public function testCheckUserAccessTag()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmTags(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ITEMS_MANAGE,
            ActionsInterface::TAG,
            ActionsInterface::TAG_SEARCH,
            ActionsInterface::TAG_VIEW,
            ActionsInterface::TAG_CREATE,
            ActionsInterface::TAG_EDIT,
            ActionsInterface::TAG_DELETE
        ]);
    }

    /**
     * testCheckUserAccessConfigCrypt
     */
    public function testCheckUserAccessConfigCrypt()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setConfigEncryption(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::CONFIG,
            ActionsInterface::CONFIG_CRYPT
        ]);
    }

    /**
     * testCheckUserAccessConfigBackup
     */
    public function testCheckUserAccessConfigBackup()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setConfigBackup(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::CONFIG,
            ActionsInterface::CONFIG_BACKUP
        ]);
    }

    /**
     * testCheckUserAccessUser
     */
    public function testCheckUserAccessUser()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmUsers(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ACCESS_MANAGE,
            ActionsInterface::USER,
            ActionsInterface::USER_SEARCH,
            ActionsInterface::USER_VIEW,
            ActionsInterface::USER_CREATE,
            ActionsInterface::USER_EDIT,
            ActionsInterface::USER_DELETE,
            ActionsInterface::USER_EDIT_PASS
        ]);
    }

    /**
     * testCheckUserAccessUserGroup
     */
    public function testCheckUserAccessUserGroup()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmGroups(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ACCESS_MANAGE,
            ActionsInterface::GROUP,
            ActionsInterface::GROUP_SEARCH,
            ActionsInterface::GROUP_VIEW,
            ActionsInterface::GROUP_CREATE,
            ActionsInterface::GROUP_EDIT,
            ActionsInterface::GROUP_DELETE
        ]);
    }

    /**
     * testCheckUserAccessUserProfile
     */
    public function testCheckUserAccessUserProfile()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmProfiles(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ACCESS_MANAGE,
            ActionsInterface::PROFILE,
            ActionsInterface::PROFILE_SEARCH,
            ActionsInterface::PROFILE_VIEW,
            ActionsInterface::PROFILE_CREATE,
            ActionsInterface::PROFILE_EDIT,
            ActionsInterface::PROFILE_DELETE
        ]);
    }

    /**
     * testCheckUserAccessAuthToken
     */
    public function testCheckUserAccessAuthToken()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setMgmApiTokens(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::ACCESS_MANAGE,
            ActionsInterface::AUTHTOKEN,
            ActionsInterface::AUTHTOKEN_SEARCH,
            ActionsInterface::AUTHTOKEN_VIEW,
            ActionsInterface::AUTHTOKEN_CREATE,
            ActionsInterface::AUTHTOKEN_EDIT,
            ActionsInterface::AUTHTOKEN_DELETE
        ]);
    }

    /**
     * testCheckUserAccessEventlog
     */
    public function testCheckUserAccessEventlog()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setEvl(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([
            ActionsInterface::EVENTLOG,
            ActionsInterface::EVENTLOG_SEARCH,
            ActionsInterface::EVENTLOG_CLEAR
        ]);
    }

    /**
     * testCheckUserAccessAccountViewPass
     */
    public function testCheckUserAccessAccountViewPass()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccViewPass(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->checkUserAccess([ActionsInterface::ACCOUNT_VIEW_PASS, ActionsInterface::CUSTOMFIELD_VIEW_PASS]);
    }

    /**
     * testCheckUserAccessAccountHistoryView
     */
    public function testCheckUserAccessAccountHistoryView()
    {
        $userData = new UserLoginResponse();
        $userData->setId(2);

        $userProfile = new ProfileData();
        $userProfile->setAccViewHistory(true);

        $this->context->setUserData($userData);
        $this->context->setUserProfile($userProfile);

        $this->assertTrue($this->acl->checkUserAccess(ActionsInterface::ACCOUNT_HISTORY_VIEW));

        $this->checkUserAccess([ActionsInterface::ACCOUNT_HISTORY_VIEW]);
    }

    /**
     * @dataProvider actionsProvider
     *
     * @param $id
     */
    public function testGetActionInfo($id)
    {
        $this->assertNotEmpty(Acl::getActionInfo($id));
    }

    /**
     * testGetActionInfoUnknown
     */
    public function testGetActionInfoUnknown()
    {
        $this->assertEmpty(Acl::getActionInfo(10000));
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    protected function setUp()
    {
        $dic = setupContext();

        $this->acl = $dic->get(Acl::class);
        $this->context = $dic->get(ContextInterface::class);
    }
}
