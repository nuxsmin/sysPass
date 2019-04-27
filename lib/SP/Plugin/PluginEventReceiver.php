<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Plugin;

use Psr\Container\ContainerInterface;
use SP\Core\Events\Event;
use SplObserver;

/**
 * Interface EventReceiver
 *
 * @package SP\Core\Events
 */
interface PluginEventReceiver extends SplObserver
{
    /**
     * Inicialización del observador
     *
     * @param ContainerInterface $dic
     */
    public function init(ContainerInterface $dic);

    /**
     * Evento de actualización
     *
     * @param string $eventType Nombre del evento
     * @param Event  $event     Objeto del evento
     */
    public function updateEvent($eventType, Event $event);

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