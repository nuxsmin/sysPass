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

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridPager;
use SP\Http\Response;
use SP\Log\Log;
use SP\Util\Checks;

/**
 * Clase encargada de preparar la presentación del registro de eventos
 *
 * @package Controller
 */
class EventlogController extends ControllerBase implements ActionsInterface
{
    /**
     * Número de máximo de registros por página
     */
    const MAX_ROWS = 30;

    /**
     * Constructor
     *
     * @param $template Template con instancia de plantilla
     */
    public function __construct(Template $template = null)
    {
        parent::__construct($template);

        $this->view->assign('sk', SessionUtil::getSessionKey(true));
    }

    /**
     * Obtener los datos para la presentación de la tabla de eventos
     */
    public function getEventlog()
    {
        $this->setAction(self::ACTION_EVL);

        if (!$this->checkAccess()) {
            return;
        }

        $this->view->addTemplate('eventlog');

        $GridActionSearch = new DataGridActionSearch();
        $GridActionSearch->setId(self::ACTION_EVL);
        $GridActionSearch->setType(DataGridActionType::SEARCH_ITEM);
        $GridActionSearch->setName('frmSearchEvent');
        $GridActionSearch->setTitle(_('Buscar Evento'));
        $GridActionSearch->setOnSubmitFunction('eventlog/search');

        $this->view->assign('rowClass', 'row_even');
        $this->view->assign('isDemoMode', Checks::demoIsEnabled() || !$this->UserData->isUserIsAdminApp());
        $this->view->assign('limitStart', isset($this->view->limitStart) ? (int)$this->view->limitStart : 0);
        $this->view->assign('events', Log::getEvents($this->view->limitStart, self::MAX_ROWS));

        $Pager = $this->getPager($GridActionSearch);
        $Pager->setTotalRows(Log::$numRows);

        $this->view->assign('Pager', $Pager);
    }

    /**
     * Comprobar si es necesario limpiar el registro de eventos
     */
    public function checkClear()
    {
        if ($this->view->clear
            && $this->view->sk
            && SessionUtil::checkSessionKey($this->view->sk)
        ) {
            if (Log::clearEvents()) {
                Response::printJson(_('Registro de eventos vaciado'), 0, 'sysPassUtil.Common.doAction(' . ActionsInterface::ACTION_EVL . '); sysPassUtil.Common.scrollUp();');
            } else {
                Response::printJson(_('Error al vaciar el registro de eventos'));
            }
        }
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
        $GridPager->setOnClickFunction('eventlog/nav');
        $GridPager->setLimitStart($this->view->limitStart);
        $GridPager->setLimitCount(self::MAX_ROWS);
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }

    /**
     * Realizar las accione del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        $this->getEventlog();

        $this->EventDispatcher->notifyEvent('show.eventlog', $this);
    }
}