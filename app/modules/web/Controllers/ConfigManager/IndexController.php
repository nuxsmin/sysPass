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

namespace SP\Modules\Web\Controllers\ConfigManager;

use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Presenters\Helpers\TabsHelper;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;
use SP\Modules\Web\Presenters\ConfigManagerPresenter;

/**
 * Class ConfigManagerController
 */
final class IndexController extends ControllerBase
{
    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        WebControllerHelper     $webControllerHelper,
        protected TabsHelper    $tabsHelper,
        private readonly ConfigManagerPresenter $presenter
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    #[Action(ResponseType::PLAIN_TEXT)]
    public function indexAction(): ActionResponse
    {
        return ActionResponse::ok($this->getGridTabs());
    }

    /**
     * Returns a tabbed grid with items
     */
    protected function getGridTabs(): string
    {
        if ($this->checkAccess(AclActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->presenter->getGeneral());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_ACCOUNT)) {
            $this->tabsHelper->addTab($this->presenter->getAccounts());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_WIKI)) {
            $this->tabsHelper->addTab($this->presenter->getWiki());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_LDAP)) {
            $this->tabsHelper->addTab($this->presenter->getLdap());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_MAIL)) {
            $this->tabsHelper->addTab($this->presenter->getMail());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_CRYPT)) {
            $this->tabsHelper->addTab($this->presenter->getEncryption());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_BACKUP)) {
            $this->tabsHelper->addTab($this->presenter->getBackup());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_IMPORT)) {
            $this->tabsHelper->addTab($this->presenter->getImport());
        }

        if ($this->checkAccess(AclActionsInterface::CONFIG_GENERAL)) {
            $this->tabsHelper->addTab($this->presenter->getInfo());
        }

        $this->eventDispatcher->notify(
            'show.config',
            new Event($this)
        );

        $this->tabsHelper->renderTabs(
            $this->acl->getRouteFor(AclActionsInterface::CONFIG),
            $this->request->analyzeInt('tabIndex', 0)
        );

        return $this->render();
    }
}