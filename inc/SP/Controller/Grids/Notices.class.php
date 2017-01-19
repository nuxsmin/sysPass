<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link http://syspass.org
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

namespace SP\Controller\Grids;

defined('APP_ROOT') || die();

use SP\Core\Init;
use SP\Html\DataGrid\DataGridAction;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridData;
use SP\Html\DataGrid\DataGridHeader;
use SP\Html\DataGrid\DataGridTab;

/**
 * Class Notices
 *
 * @package SP\Controller\Grids
 */
class Notices extends GridBase
{
    /**
     * @return DataGridTab
     * @throws \InvalidArgumentException
     */
    public function getNoticesGrid()
    {
        // Grid Header
        $GridHeaders = new DataGridHeader();
        $GridHeaders->addHeader(__('Fecha'));
        $GridHeaders->addHeader(__('Tipo'));
        $GridHeaders->addHeader(__('Componente'));
        $GridHeaders->addHeader(__('Descripción'));
        $GridHeaders->addHeader(__('Estado'));

        // Grid Data
        $GridData = new DataGridData();
        $GridData->setDataRowSourceId('notice_id');
        $GridData->addDataRowSource('notice_date');
        $GridData->addDataRowSource('notice_type');
        $GridData->addDataRowSource('notice_component');
        $GridData->addDataRowSource('notice_description');
        $GridData->addDataRowSourceWithIcon('notice_checked', $this->icons->getIconEnabled()->setTitle(__('Leída')));

        // Grid
        $Grid = new DataGridTab();
        $Grid->setId('tblNotices');
        $Grid->setDataRowTemplate('datagrid-rows', 'grid');
        $Grid->setDataPagerTemplate('datagrid-nav-full', 'grid');
        $Grid->setHeader($GridHeaders);
        $Grid->setData($GridData);
        $Grid->setTitle(__('Notificaciones'));
        $Grid->setTime(round(microtime() - $this->queryTimeStart, 5));

        // Grid Actions
        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_NOT_USER_SEARCH);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchNotice');
        $GridActionSearch->setTitle(__('Buscar Notificación'));
        $GridActionSearch->setOnSubmitFunction('notice/search');

        $Grid->setDataActions($GridActionSearch);
        $Grid->setPager($this->getPager($GridActionSearch));

        // Grid item's actions
        $GridActionNew = new DataGridAction();
        $GridActionNew->setId(self::ACTION_NOT_USER_VIEW);
        $GridActionNew->setType(DataGridActionType::VIEW_ITEM);
        $GridActionNew->setName(__('Ver Notificación'));
        $GridActionNew->setTitle(__('Ver Notificación'));
        $GridActionNew->setIcon($this->icons->getIconView());
        $GridActionNew->setOnClickFunction('notice/show');

        $Grid->setDataActions($GridActionNew);

        $GridActionCheck = new DataGridAction();
        $GridActionCheck->setId(self::ACTION_NOT_USER_CHECK);
        $GridActionCheck->setName(__('Marcar Notificación'));
        $GridActionCheck->setTitle(__('Marcar Notificación'));
        $GridActionCheck->setIcon($this->icons->getIconEnabled());
        $GridActionCheck->setOnClickFunction('notice/check');
        $GridActionCheck->setFilterRowSource('notice_checked', 1);

        $Grid->setDataActions($GridActionCheck);

        $GridActionDel = new DataGridAction();
        $GridActionDel->setId(self::ACTION_NOT_USER_DELETE);
        $GridActionDel->setType(DataGridActionType::DELETE_ITEM);
        $GridActionDel->setName(__('Eliminar Notificación'));
        $GridActionDel->setTitle(__('Eliminar Notificación'));
        $GridActionDel->setIcon($this->icons->getIconDelete());
        $GridActionDel->setOnClickFunction('appMgmt/delete');

        $Grid->setDataActions($GridActionDel);
        $Grid->setDataActions($GridActionDel, true);

        return $Grid;
    }
}