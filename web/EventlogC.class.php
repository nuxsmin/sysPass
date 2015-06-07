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

namespace Controller;

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Clase encargada de preparar la presentación del registro de eventos
 *
 * @package Controller
 */
class EventlogC extends \SP_Controller implements ActionsInterface
{
    /**
     * Constructor
     *
     * @param $template \SP_Template con instancia de plantilla
     */
    public function __construct(\SP_Template $template = null)
    {
        parent::__construct($template);
    }

    /**
     * Obtener los datos para la presentación de la tabla de eventos
     */
    public function getEventlog()
    {
        $this->setAction(self::ACTION_EVL);

        if (!$this->checkAccess()){
            return;
        }

        $this->view->addTemplate('eventlog');

        $this->view->assign('rowClass', 'row_even');
        $this->view->assign('isDemoMode', \SP_Util::demoIsEnabled());
        $this->view->assign('start', (isset($this->view->start)) ? (int)$this->view->start : 0);
        $this->view->assign('events', \SP_Log::getEvents($this->view->start));
        $this->view->assign('numRows', \SP_Log::$numRows);
    }

    /**
     * Comprobar si es necesario limpiar el registro de eventos
     */
    public function checkClear(){
        if ($this->view->clear && $this->view->sk && \SP_Common::checkSessionKey($this->view->sk)){
            if ( \SP_Log::clearEvents() ){
                \SP_Common::printJSON(_('Registro de eventos vaciado'), 0, "doAction('eventlog');scrollUp();");
            } else{
                \SP_Common::printJSON(_('Error al vaciar el registro de eventos'));
            }
        }
    }
}