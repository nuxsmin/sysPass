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

namespace SP\Modules\Web\Controllers\Client;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\ClientData;
use SP\Domain\Client\ClientServiceInterface;
use SP\Domain\CustomField\CustomFieldServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\ItemTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ClientViewBase
 */
abstract class ClientViewBase extends ControllerBase
{
    use ItemTrait;

    private ClientServiceInterface      $clientService;
    private CustomFieldServiceInterface $customFieldService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        ClientServiceInterface $clientService,
        CustomFieldServiceInterface $customFieldService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->clientService = $clientService;
        $this->customFieldService = $customFieldService;
    }

    /**
     * Sets view data for displaying client's data
     *
     * @param  int|null  $clientId
     *
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     * @throws SPException
     * @throws \SP\Domain\Common\Services\ServiceException
     */
    protected function setViewData(?int $clientId = null): void
    {
        $this->view->addTemplate('client', 'itemshow');

        $client = $clientId
            ? $this->clientService->getById($clientId)
            : new ClientData();

        $this->view->assign('client', $client);

        $this->view->assign(
            'nextAction',
            Acl::getActionRoute(ActionsInterface::ITEMS_MANAGE)
        );

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }

        $this->view->assign(
            'showViewCustomPass',
            $this->acl->checkUserAccess(ActionsInterface::CUSTOMFIELD_VIEW_PASS)
        );
        $this->view->assign(
            'customFields',
            $this->getCustomFieldsForItem(ActionsInterface::CLIENT, $clientId, $this->customFieldService)
        );
    }
}