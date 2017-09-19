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

namespace SP\Core\Events;

use SplObserver;

/**
 * Interface EventReceiver
 *
 * @package SP\Core\Events
 */
interface EventReceiver extends SplObserver
{
    /**
     * Inicialización del observador
     */
    public function init();

    /**
     * Evento de actualización
     *
     * @param string $event Nombre del evento
     * @param object $object Objeto del evento
     * @return
     */
    public function updateEvent($event, $object);

    /**
     * Devuelve los eventos que implementa el observador
     *
     * @return array
     */
    public function getEvents();

    /**
     * Devuelve los recursos Javascript necesarios para el plugin
     *
     * @return array
     */
    public function getJsResources();

    /**
     * Devuelve los recursos CSS necesarios para el plugin
     *
     * @return array
     */
    public function getCssResources();
}