<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Controller;

use SP\Controller\Grids\Notices;
use SP\Core\ActionsInterface;
use SP\Core\Exceptions\SPException;
use SP\Mgmt\Notices\Notice;

/**
 * Class NoticesController
 *
 * @package SP\Controller
 */
class NoticesController extends GridTabControllerBase implements ActionsInterface
{
    /**
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        try {
            $this->useTabs();
            $this->getUserNotices();

            $this->EventDispatcher->notifyEvent('show.itemlist.notices', $this);
        } catch (SPException $e) {
            $this->showError(self::ERR_EXCEPTION);
        }
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->Grids = new Notices();
        $this->view->addTemplate('datatabs-grid', 'grid');

        $this->view->assign('tabs', []);
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la pestaña de categorías
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getUserNotices()
    {
        $this->setAction(self::ACTION_NOT_USER);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getGrids()->getNoticesGrid();
        $Grid->getData()->setData(Notice::getItem()->getAllForUser());
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * @return Notices
     */
    public function getGrids()
    {
        return $this->Grids;
    }
}