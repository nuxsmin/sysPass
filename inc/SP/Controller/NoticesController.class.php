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

use SP\Config\Config;
use SP\Core\ActionsInterface;
use SP\Core\Init;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridPager;
use SP\Html\DataGrid\DataGridTab;
use SP\Mgmt\Notices\Notice;

/**
 * Class NoticesController
 *
 * @package SP\Controller
 */
class NoticesController extends GridTabControllerBase implements ActionsInterface
{
    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function doAction($type = null)
    {
        $this->useTabs();
        $this->getUserNotices();
    }

    /**
     * Inicializar las plantillas para las pestañas
     */
    public function useTabs()
    {
        $this->view->addTemplate('datatabs-grid', 'grid');

        $this->view->assign('tabs', []);
        $this->view->assign('activeTab', 0);
        $this->view->assign('maxNumActions', self::MAX_NUM_ACTIONS);
    }

    /**
     * Obtener los datos para la pestaña de categorías
     *
     * @throws \InvalidArgumentException
     * @throws \SP\Core\Exceptions\SPException
     */
    public function getUserNotices()
    {
        $this->setAction(self::ACTION_NOT_USER);

        if (!$this->checkAccess()) {
            return;
        }

        $Grid = $this->getNoticesGrid();
        $Grid->getData()->setData(Notice::getItem()->getAllForUser());
        $Grid->updatePager();

        $this->view->append('tabs', $Grid);
    }

    /**
     * @return DataGridTab
     * @throws \InvalidArgumentException
     */
    protected function getNoticesGrid()
    {
        global $timeStart;

        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(_('Fecha'));
        $GridHeaders->addHeader(_('Tipo'));
        $GridHeaders->addHeader(_('Componente'));
        $GridHeaders->addHeader(_('Descripción'));
        $GridHeaders->addHeader(_('Estado'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('notice_id');
        $GridData->addDataRowSource('notice_date');
        $GridData->addDataRowSource('notice_type');
        $GridData->addDataRowSource('notice_component');
        $GridData->addDataRowSource('notice_description');
        $GridData->addDataRowSourceWithIcon('notice_checked', $this->icons->getIconEnabled());

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblNotices');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(_('Notificaciones'));
        $Grid->setTime(round(Init::microtime_float() - $timeStart, 5));

        // Grid Actions
//        $GridActionSearch = new DataGridActionSearch();
//        $GridActionSearch->setId(self::ACTION_MGM_CATEGORIES_SEARCH);
//        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
//        $GridActionSearch->setName('frmSearchCategory');
//        $GridActionSearch->setTitle(_('Buscar Categoría'));
//        $GridActionSearch->setOnSubmitFunction('appMgmt/search');

        $Grid->setPager($this->getPager(new DataGridActionSearch()));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_NOT_USER_VIEW);
        $GridActionNew->setType(DataGridActionType::VIEW_ITEM);
        $GridActionNew->setName(_('Ver Notificación'));
        $GridActionNew->setTitle(_('Ver Notificación'));
        $GridActionNew->setIcon($this->icons->getIconView());
        $GridActionNew->setOnClickFunction('appMgmt/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionCheck = new DataGridAction();
        $GridActionCheck->setId(self::ACTION_NOT_USER_CHECK);
        $GridActionCheck->setName(_('Marcar Notificación'));
        $GridActionCheck->setTitle(_('Marcar Notificación'));
        $GridActionCheck->setIcon($this->icons->getIconEnabled());
        $GridActionCheck->setOnClickFunction('notices/check');

        $Grid->setDataActions($GridActionCheck);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_NOT_USER_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(_('Eliminar Notificación'));
        $GridActionDel->setTitle(_('Eliminar Notificación'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }

    /**
     * Devolver el paginador por defecto
     *
     * @param DataGridActionSearch $sourceAction
     * @return DataGridPager
     */
    protected function getPager(DataGridActionSearch $sourceAction)
    {
        $GridPager = new DataGridPager();
        $GridPager->setSourceAction($sourceAction);
        $GridPager->setOnClickFunction('appMgmt/nav');
        $GridPager->setLimitStart(0);
        $GridPager->setLimitCount(Config::getConfig()->getAccountCount());
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }
}