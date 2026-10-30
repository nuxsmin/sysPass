<?php
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

namespace SP\Modules\Web\Controllers\UserSettingsManager;

use SP\Core\Application;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Ports\LanguageList;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\User\Models\UserPreferences;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Presenters\Helpers\TabsHelper;
use SP\Modules\Web\Mvc\Controller\ExtensibleTabControllerInterface;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Mvc\View\Components\DataTab;
use SP\Modules\Web\Mvc\View\Components\SelectItemAdapter;
use SP\Modules\Web\Mvc\View\TemplateInterface;

use function SP\__;

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
        TabsHelper $tabsHelper,
        private readonly LanguageList $languageList
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->tabsHelper = $tabsHelper;
    }

    #[Action(ResponseType::PLAIN_TEXT)]
    public function indexAction(): ActionResponse
    {
        return ActionResponse::ok($this->getTabs());
    }

    /**
     * Returns a tabbed grid with items
     */
    protected function getTabs(): string
    {
        $this->tabsHelper->addTab($this->getUserPreferences());

        $this->eventDispatcher->notify('show.userSettings', new Event($this));

        $this->tabsHelper->renderTabsTo(
            $this->view,
            $this->acl->getRouteFor(AclActionsInterface::USERSETTINGS),
            $this->request->analyzeInt('tabIndex', 0)
        );

        return $this->render();
    }

    /**
     * @param DataTab $tab
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
        $template->addTemplate('general', 'usersettings');

        $userData = $this->session->getUserData();
        $userPreferences = $userData->preferences ?? new UserPreferences();

        $template->assign(
            'langs',
            SelectItemAdapter::factory($this->languageList->getAvailableLanguages())
                             ->getItemsFromArraySelected(
                                 [$userPreferences->getLang() ?: $this->configData->getSiteLang()]
                             )
        );
        $template->assign(
            'themes',
            SelectItemAdapter::factory($this->theme->getAvailable())
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
