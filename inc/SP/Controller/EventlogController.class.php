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

defined('APP_ROOT') || die();

use SP\Core\ActionsInterface;
use SP\Core\Messages\LogMessage;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Html\DataGrid\DataGridActionSearch;
use SP\Html\DataGrid\DataGridActionType;
use SP\Html\DataGrid\DataGridPager;
use SP\Http\Request;
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
    const MAX_ROWS = 50;
    /**
     * @var
     */
    protected $limitStart;

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
     * Realizar las acciones del controlador
     *
     * @param mixed $type Tipo de acción
     */
    public function doAction($type = null)
    {
        $this->limitStart = Request::analyze('start', 0);

        $this->checkClear();
        $this->getEventlog();

        $this->EventDispatcher->notifyEvent('show.eventlog', $this);
    }

    /**
     * Comprobar si es necesario limpiar el registro de eventos
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \phpmailer\phpmailerException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function checkClear()
    {
        $clear = Request::analyze('clear', 0);

        if ($clear === 1
            && $this->view->sk
            && SessionUtil::checkSessionKey($this->view->sk)
        ) {
            Log::clearEvents();

            Log::writeNewLogAndEmail(__('Vaciar Eventos', false), __('Vaciar registro de eventos', false), null);

            Response::printJson(__('Registro de eventos vaciado', false), 0);
        }
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
        $GridActionSearch->setTitle(__('Buscar Evento'));
        $GridActionSearch->setOnSubmitFunction('eventlog/search');

        $this->view->assign('rowClass', 'row_even');
        $this->view->assign('isDemoMode', Checks::demoIsEnabled() || !$this->UserData->isUserIsAdminApp());
        $this->view->assign('limitStart', $this->limitStart);
        $this->view->assign('events', Log::getEvents($this->limitStart, self::MAX_ROWS));

        $Pager = $this->getPager($GridActionSearch);
        $Pager->setTotalRows(Log::$numRows);

        $this->view->assign('Pager', $Pager);
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
        $GridPager->setLimitStart($this->limitStart);
        $GridPager->setLimitCount(self::MAX_ROWS);
        $GridPager->setIconPrev($this->icons->getIconNavPrev());
        $GridPager->setIconNext($this->icons->getIconNavNext());
        $GridPager->setIconFirst($this->icons->getIconNavFirst());
        $GridPager->setIconLast($this->icons->getIconNavLast());

        return $GridPager;
    }
}