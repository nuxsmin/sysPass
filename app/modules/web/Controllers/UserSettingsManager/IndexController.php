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

namespace SP\Modules\Web\Controllers\UserSettingsManager;

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventDispatcherInterface;
use SP\Core\Language;
use SP\DataModel\UserPreferencesData;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\Controller\ExtensibleTabControllerInterface;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\TemplateInterface;

/**
 * Class IndexController
 *
 * @package web\Controllers
 */
final class IndexController extends ControllerBase implements ExtensibleTabControllerInterface
{
    private TabsHelper $tabsHelper;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        TabsHelper $tabsHelper
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->tabsHelper = $tabsHelper;
    }

    public function indexAction(): void
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     */
    protected function getTabs(): void
    {
        $this->tabsHelper->addTab($this->getUserPreferences());

        $this->eventDispatcher->notify('show.userSettings', new Event($this));

        $this->tabsHelper->renderTabs(
            Acl::getActionRoute(ActionsInterface::USERSETTINGS),
            $this->request->analyzeInt('tabIndex', 0)
        );

        $this->view();
    }

    /**
     * @param  DataTab  $tab
     */
    public function addTab(DataTab $tab): void
    {
        $this->tabsHelper->addTab($tab);
    }

    /**
     * @return DataTab
     */
    private function getUserPreferences(): DataTab
    {
        $template = clone $this->view;
        $template->setBase('usersettings');
        $template->addTemplate('general');

        $userData = $this->session->getUserData();
        $userPreferences = $userData->getPreferences() ?? new UserPreferencesData();

        $template->assign(
            'langs',
            SelectItemAdapter::factory(Language::getAvailableLanguages())
                ->getItemsFromArraySelected(
                    [$userPreferences->getLang() ?: $this->configData->getSiteLang()]
                )
        );
        $template->assign(
            'themes',
            SelectItemAdapter::factory($this->theme->getThemesAvailable())
                ->getItemsFromArraySelected(
                    [$userPreferences->getTheme() ?: $this->configData->getSiteTheme()]
                )
        );
        $template->assign('userPreferences', $userPreferences);
        $template->assign('route', 'userSettingsGeneral/save');

        return new DataTab(__('Preferences'), $template);
    }

    /**
     * @return TemplateInterface
     */
    public function getView(): TemplateInterface
    {
        return $this->view;
    }

    /**
     * @return void
     */
    public function displayView(): void
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
}
