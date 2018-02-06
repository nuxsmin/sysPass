<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Modules\Web\Controllers;

use SP\Controller\ControllerBase;
use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Language;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\User\UserService;
use SP\Services\UserGroup\UserGroupService;
use SP\Services\UserProfile\UserProfileService;
use SP\Util\Checks;

/**
 * Class ConfigManagerController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigManagerController extends ControllerBase
{
    /**
     * @var TabsHelper
     */
    protected $tabsHelper;

    /**
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Dic\ContainerException
     */
    public function indexAction()
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     * @throws \SP\Core\Dic\ContainerException
     */
    protected function getTabs()
    {
        $this->tabsHelper = new TabsHelper($this->view, $this->config, $this->session, $this->eventDispatcher);

        if ($this->checkAccess(ActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->getConfigGeneral());
        }

        if ($this->checkAccess(ActionsInterface::ACCOUNT_CONFIG)) {
            $this->tabsHelper->addTab($this->getAccountConfig());
        }

        if ($this->checkAccess(ActionsInterface::WIKI_CONFIG)) {
            $this->tabsHelper->addTab($this->getWikiConfig());
        }

        if ($this->checkAccess(ActionsInterface::LDAP_CONFIG)) {
            $this->tabsHelper->addTab($this->getLdapConfig());
        }

        if ($this->checkAccess(ActionsInterface::MAIL_CONFIG)) {
            $this->tabsHelper->addTab($this->getMailConfig());
        }

        if ($this->checkAccess(ActionsInterface::ENCRYPTION_CONFIG)) {
//            $this->tabsHelper->addTab($this->getEncryptionConfig());
        }

        if ($this->checkAccess(ActionsInterface::BACKUP_CONFIG)) {
//            $this->tabsHelper->addTab($this->getBackupConfig());
        }

        if ($this->checkAccess(ActionsInterface::IMPORT_CONFIG)) {
//            $this->tabsHelper->addTab($this->getImportConfig());
        }


        $this->eventDispatcher->notifyEvent('show.config', $this);

        $this->tabsHelper->renderTabs(Acl::getActionRoute(ActionsInterface::CONFIG), Request::analyze('tabIndex', 0));

        $this->view();
    }

    /**
     * @return DataTab
     */
    protected function getConfigGeneral()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('general');

        $userData = $this->session->getUserData();

        $template->assign('langsAvailable', Language::getAvailableLanguages());
        $template->assign('themesAvailable', $this->theme->getThemesAvailable());
        $template->assign('isDemoMode', $this->configData->isDemoEnabled() && !$userData->getIsAdminApp());
        $template->assign('isDisabled', $this->configData->isDemoEnabled() && !$userData->getIsAdminApp() ? 'disabled' : '');
        $template->assign('configData', $this->configData);

        $template->assign('users', SelectItemAdapter::factory(UserService::getItemsBasic())->getItemsFromModel());
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('General'), $template);
    }

    /**
     * @return TabsHelper
     */
    public function getTabsHelper()
    {
        return $this->tabsHelper;
    }

    /**
     * @return DataTab
     */
    protected function getAccountConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('accounts');

        $template->assign('configData', $this->configData);

        return new DataTab(__('Cuentas'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getWikiConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('wiki');

        $template->assign('configData', $this->configData);

        return new DataTab(__('Wiki'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getLdapConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('ldap');

        $template->assign('ldapIsAvailable', Checks::ldapIsAvailable());
        $template->assign('configData', $this->configData);
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('LDAP'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getMailConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('mail');

        $template->assign('mailSecurity', ['SSL', 'TLS']);
        $template->assign('configData', $this->configData);
        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('Correo'), $template);
    }

    /**
     * @return DataTab
     */
    protected function getEncryptionConfig()
    {
        $template = clone $this->view;
        $template->setBase('config');
        $template->addTemplate('mail');

        $template->assign('mailSecurity', ['SSL', 'TLS']);
        $template->assign('configData', $this->configData);

        $this->view->assign('numAccounts', AccountUtil::getTotalNumAccounts());
        $this->view->assign('taskId', Task::genTaskId('masterpass'));

        $this->view->assign('lastUpdateMPass', isset($this->configDB['lastupdatempass']) ? $this->configDB['lastupdatempass'] : 0);
        $this->view->assign('tempMasterPassTime', isset($this->configDB['tempmaster_passtime']) ? $this->configDB['tempmaster_passtime'] : 0);
        $this->view->assign('tempMasterMaxTime', isset($this->configDB['tempmaster_maxtime']) ? $this->configDB['tempmaster_maxtime'] : 0);
        $this->view->assign('tempMasterAttempts', isset($this->configDB['tempmaster_attempts']) ? sprintf('%d/%d', $this->configDB['tempmaster_attempts'], CryptMasterPass::MAX_ATTEMPTS) : 0);
        $this->view->assign('tempMasterPass', SessionFactory::getTemporaryMasterPass());

        $template->assign('userGroups', SelectItemAdapter::factory(UserGroupService::getItemsBasic())->getItemsFromModel());
        $template->assign('userProfiles', SelectItemAdapter::factory(UserProfileService::getItemsBasic())->getItemsFromModel());

        return new DataTab(__('Encriptación'), $template);
    }
}