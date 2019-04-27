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

namespace SP\Modules\Web\Controllers;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Acl\Acl;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Language;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\Controller\ExtensibleTabControllerInterface;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\Template;
use SP\Services\Auth\AuthException;

/**
 * Class UserSettingsManagerController
 *
 * @package web\Controllers
 */
final class UserSettingsManagerController extends ControllerBase implements ExtensibleTabControllerInterface
{
    /**
     * @var TabsHelper
     */
    protected $tabsHelper;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function indexAction()
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTabs()
    {
        $this->tabsHelper = $this->dic->get(TabsHelper::class);

        $this->tabsHelper->addTab($this->getUserPreferences());

        $this->eventDispatcher->notifyEvent('show.userSettings', new Event($this));

        $this->tabsHelper->renderTabs(Acl::getActionRoute(Acl::USERSETTINGS), $this->request->analyzeInt('tabIndex', 0));

        $this->view();
    }

    /**
     * @return DataTab
     */
    private function getUserPreferences()
    {
        $template = clone $this->view;
        $template->setBase('usersettings');
        $template->addTemplate('general');

        $userData = $this->session->getUserData();
        $userPreferences = $userData->getPreferences();

        $template->assign('langs', SelectItemAdapter::factory(Language::getAvailableLanguages())->getItemsFromArraySelected([$userPreferences->getLang() ?: $this->configData->getSiteLang()]));
        $template->assign('themes', SelectItemAdapter::factory($this->theme->getThemesAvailable())->getItemsFromArraySelected([$userPreferences->getTheme() ?: $this->configData->getSiteTheme()]));
        $template->assign('userPreferences', $userPreferences);
        $template->assign('route', 'userSettingsGeneral/save');

        return new DataTab(__('Preferences'), $template);
    }

    /**
     * @param DataTab $tab
     */
    public function addTab(DataTab $tab)
    {
        $this->tabsHelper->addTab($tab);
    }

    /**
     * @return Template
     */
    public function getView(): Template
    {
        return $this->view;
    }

    /**
     * @return void
     */
    public function displayView()
    {
        $this->view();
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws SessionTimeout
     * @throws AuthException
     */
    protected function initialize()
    {
        $this->checkLoggedIn();
    }
}