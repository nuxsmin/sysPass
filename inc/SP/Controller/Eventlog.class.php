<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP\Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

use SP\Core\ActionsInterface;
use SP\Core\Session;
use SP\Core\SessionUtil;
use SP\Core\Template;
use SP\Http\Response;
use SP\Log\Log;
use SP\Util\Checks;
use SP\Util\Util;


/**
 * Clase encargada de preparar la presentación del registro de eventos
 *
 * @package Controller
 */
class Eventlog extends Controller implements ActionsInterface
{
    /**
     * Número de máximo de registros por página
     */
    const MAX_ROWS = 50;

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

        $this->view->assign('rowClass', 'row_even');
        $this->view->assign('isDemoMode', Checks::demoIsEnabled() || !Session::getUserIsAdminApp());
        $this->view->assign('limitStart', (isset($this->view->limitStart)) ? (int)$this->view->limitStart : 0);
        $this->view->assign('events', Log::getEvents($this->view->limitStart));
        $this->view->assign('totalRows', Log::$numRows);
        $this->view->assign('firstPage', ceil(($this->view->limitStart + 1) / self::MAX_ROWS));
        $this->view->assign('lastPage', ceil(Log::$numRows / self::MAX_ROWS));

        $limitLast = (Log::$numRows % self::MAX_ROWS == 0) ? Log::$numRows - self::MAX_ROWS : floor(Log::$numRows / self::MAX_ROWS) * self::MAX_ROWS;

        $this->view->assign('pagerOnnClick', array(
            'first' => 'sysPassUtil.Common.navLog(0,' . $this->view->limitStart . ')',
            'last' => 'sysPassUtil.Common.navLog(' . $limitLast . ',' . $this->view->limitStart . ')',
            'prev' => 'sysPassUtil.Common.navLog(' . ($this->view->limitStart - self::MAX_ROWS) . ',' . $this->view->limitStart . ')',
            'next' => 'sysPassUtil.Common.navLog(' . ($this->view->limitStart + self::MAX_ROWS) . ',' . $this->view->limitStart . ')',
        ));
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
                Response::printJSON(_('Registro de eventos vaciado'), 0, "sysPassUtil.Common.doAction(" . ActionsInterface::ACTION_EVL . "); sysPassUtil.Common.scrollUp();");
            } else {
                Response::printJSON(_('Error al vaciar el registro de eventos'));
            }
        }
    }
}