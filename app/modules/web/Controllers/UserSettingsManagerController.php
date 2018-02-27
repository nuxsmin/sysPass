<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Events\Event;
use SP\Core\Language;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\TabsHelper;
use SP\Mvc\View\Components\DataTab;
use SP\Mvc\View\Components\SelectItemAdapter;

/**
 * Class UserSettingsManagerController
 * @package web\Controllers
 */
class UserSettingsManagerController extends ControllerBase
{
    /**
     * @var TabsHelper
     */
    protected $tabsHelper;

    public function indexAction()
    {
        $this->getTabs();
    }

    /**
     * Returns a tabbed grid with items
     */
    protected function getTabs()
    {
        $this->tabsHelper = $this->dic->get(TabsHelper::class);

        $this->tabsHelper->addTab($this->getUserPreferences());

        $this->eventDispatcher->notifyEvent('show.userSettings', new Event($this));

        $this->tabsHelper->renderTabs(Acl::getActionRoute(ActionsInterface::USERSETTINGS), Request::analyze('tabIndex', 0));

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
        $template->assign('chkAccountLink', $userPreferences->isAccountLink() ? 'checked="checked"' : '');
        $template->assign('resultsPerPage', $userPreferences->getResultsPerPage() ?: $this->configData->getAccountCount());
        $template->assign('chkSortViews', $userPreferences->isSortViews() ? 'checked="checked"' : '');
        $template->assign('chkTopNavbar', $userPreferences->isTopNavbar() ? 'checked="checked"' : '');
        $template->assign('chkOptionalActions', $userPreferences->isOptionalActions() ? 'checked="checked"' : '');
        $template->assign('chkResultsAsCards', $userPreferences->isResultsAsCards() ? 'checked="checked"' : '');
        $template->assign('route', 'userSettingsGeneral/save');

        return new DataTab(__('Preferencias'), $template);
    }
}