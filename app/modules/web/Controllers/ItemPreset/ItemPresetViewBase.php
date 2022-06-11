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

namespace SP\Modules\Web\Controllers\ItemPreset;


use SP\Core\Acl\Acl;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Application;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\InvalidArgumentException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ItemPresetData;
use SP\Domain\ItemPreset\ItemPresetInterface;
use SP\Domain\ItemPreset\ItemPresetServiceInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Helpers\ItemPresetHelper;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class ItemPresetViewBase
 */
abstract class ItemPresetViewBase extends ControllerBase
{
    private ItemPresetServiceInterface $itemPresetService;
    private ItemPresetHelper           $itemPresetHelper;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        ItemPresetServiceInterface $itemPresetService,
        ItemPresetHelper $itemPresetHelper
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->itemPresetService = $itemPresetService;
        $this->itemPresetHelper = $itemPresetHelper;
    }

    /**
     * Sets view data for displaying permissions' data
     *
     * @param  int|null  $id
     * @param  string|null  $type
     *
     * @throws ConstraintException
     * @throws InvalidArgumentException
     * @throws NoSuchItemException
     * @throws NoSuchPropertyException
     * @throws QueryException
     */
    protected function setViewData(?int $id = null, ?string $type = null): void
    {
        $this->view->addTemplate('item_preset', 'itemshow');

        $itemPresetData = $id
            ? $this->itemPresetService->getById($id)
            : new ItemPresetData();

        $this->itemPresetHelper->setCommon($itemPresetData);

        if ($itemPresetData->getType() === null) {
            $itemPresetData->setType($type);
        }

        switch ($itemPresetData->getType()) {
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PERMISSION:
                $this->itemPresetHelper->makeAccountPermissionView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PRIVATE:
                $this->itemPresetHelper->makeAccountPrivateView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_SESSION_TIMEOUT:
                $this->itemPresetHelper->makeSessionTimeoutView($itemPresetData);
                break;
            case ItemPresetInterface::ITEM_TYPE_ACCOUNT_PASSWORD:
                $this->itemPresetHelper->makeAccountPasswordView($itemPresetData);
                break;
        }

        $this->view->assign('preset', $itemPresetData);
        $this->view->assign('nextAction', Acl::getActionRoute(ActionsInterface::ACCESS_MANAGE));

        if ($this->view->isView === true) {
            $this->view->assign('disabled', 'disabled');
            $this->view->assign('readonly', 'readonly');
        } else {
            $this->view->assign('disabled', false);
            $this->view->assign('readonly', false);
        }
    }
}